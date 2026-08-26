<?php

namespace local_sendwelcomemsg\task;

defined('MOODLE_INTERNAL') || die();

class send_welcome_emails extends \core\task\scheduled_task
{
    public function get_name()
    {
        return get_string('pluginname', 'local_sendwelcomemsg');
    }

    public function execute()
    {
        global $DB;
        $config = get_config('local_sendwelcomemsg');

        $records = $DB->get_records('local_sendwelcomemsg_queue');
        foreach ($records as $record) {
            $user = $DB->get_record('user', ['id' => $record->userid]);
            $userid = $user ? (int) $user->id : (int) $record->userid;
            $useremail = ($user && !empty($user->email)) ? $user->email : '(нет email / пользователь не найден)';

            mtrace("local_sendwelcomemsg: проверка пользователя id={$userid} email={$useremail}, запись очереди id={$record->id}");

            $userdomain = self::extract_email_domain($user ? ($user->email ?? '') : '');
            $mailerror = false;
            for ($i = 1; $i <= 5; $i++) {
                $enabled = !empty($config->{"enable{$i}"});
                if (!$enabled) {
                    mtrace("local_sendwelcomemsg: шаблон {$i}: нет (письмо отключено)");
                    continue;
                }

                $targetdomain = strtolower(trim((string)($config->{"domain{$i}"} ?? '')));
                $targetdomain = ltrim($targetdomain, '@');
                if ($targetdomain !== '' && ($userdomain === '' || $userdomain !== $targetdomain)) {
                    mtrace("local_sendwelcomemsg: шаблон {$i}: нет (домен не совпадает, нужен {$targetdomain} а у пользователя {$userdomain})");
                    continue;
                }

                if (!$user || empty($user->email)) {
                    mtrace("local_sendwelcomemsg: шаблон {$i}: нет (нет пользователя или email)");
                    continue;
                }

                try {
                    self::send_mail($user, $i, $config);
                    mtrace("local_sendwelcomemsg: шаблон {$i}: отправлено");
                } catch (\Throwable $e) {
                    $mailerror = true;
                    mtrace("local_sendwelcomemsg: шаблон {$i}: нет (ошибка отправки: " . $e->getMessage() . ')');
                }
            }

            if ($mailerror) {
                mtrace("local_sendwelcomemsg: запись id={$record->id} оставлена в очереди из-за ошибки почты");
                continue;
            }

            $DB->delete_records('local_sendwelcomemsg_queue', ['id' => $record->id]);
            mtrace("local_sendwelcomemsg: пользователь id={$userid} удалён из очереди (запись id={$record->id})");
        }
    }

    private static function extract_email_domain($email)
    {
        if (empty($email)) {
            return '';
        }
        $email = strtolower(trim($email));
        $pos = strrpos($email, '@');
        if ($pos === false) {
            return '';
        }
        return substr($email, $pos + 1);
    }

    private static function send_mail($user, $index, $config)
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->Host = $config->{"smtp{$index}_host"};
        $mail->Port = (int)$config->{"smtp{$index}_port"};
        if (!empty($config->{"smtp{$index}_host"})) {
            $mail->isSMTP();
            $mail->SMTPAuth = !empty($config->{"smtp{$index}_user"});
            if ($mail->SMTPAuth) {
                $mail->Username = $config->{"smtp{$index}_user"};
                $mail->Password = $config->{"smtp{$index}_pass"};
            }
            $mail->SMTPSecure = $config->{"smtp{$index}_secure"};

            if (self::is_local_smtp_without_cert_check($mail->Host, $mail->Port)) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
                mtrace("local_sendwelcomemsg: шаблон {$index}: localhost:25, проверка SSL-сертификата отключена");
            }
        }

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($config->{"from{$index}_email"}, $config->{"from{$index}_name"});
        $mail->addAddress($user->email, fullname($user));
        $mail->Subject = $config->{"subject$index"};

        $mail->Body = \html_writer::div($config->{"body$index"}, '', ['style' => $config->{"style$index"}]);
        $mail->AltBody = html_to_text($config->{"body$index"});
        $mail->isHTML(true);


        $fs = get_file_storage();
        $context = \context_system::instance();
        $files = $fs->get_area_files($context->id, 'local_sendwelcomemsg', "attachment{$index}", 0, 'itemid, filepath, filename', false);
        foreach ($files as $file) {
            $temp = $file->copy_content_to_temp();
            $mail->addAttachment($temp, $file->get_filename());
        }

        try {
            $mail->send();
        } catch (\Throwable $e) {
            if (!self::is_cert_verify_error($e->getMessage())) {
                throw $e;
            }

            // Fallback for SMTP servers with invalid/self-signed certificate chain.
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            mtrace("local_sendwelcomemsg: шаблон {$index}: повторная попытка с отключенной проверкой SSL-сертификата");
            $mail->send();
        }
    }

    private static function is_cert_verify_error($message)
    {
        $message = strtolower((string)$message);
        return (strpos($message, 'certificate verify failed') !== false)
            || (strpos($message, 'stream_socket_enable_crypto') !== false)
            || (strpos($message, 'could not connect to smtp host') !== false);
    }

    private static function is_local_smtp_without_cert_check($host, $port)
    {
        $host = strtolower(trim((string)$host));
        $port = (int)$port;
        return $port === 25 && ($host === 'localhost' || $host === '127.0.0.1');
    }
}
