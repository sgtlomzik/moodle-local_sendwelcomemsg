<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Scheduled task that delivers queued welcome messages.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg\task;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends the configured welcome messages to users waiting on the queue.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_welcome_emails extends \core\task\scheduled_task {
    /** @var int Number of configurable message templates. */
    public const TEMPLATES = 5;

    /** @var int Maximum queue entries handled in one run. */
    protected const BATCH_SIZE = 100;

    /** @var int Give up on a queue entry that has been failing for this long. */
    protected const MAX_AGE = 7 * DAYSECS;

    /**
     * Get a descriptive name for the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskname', 'local_sendwelcomemsg');
    }

    /**
     * Deliver the messages queued since the previous run.
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->libdir . '/phpmailer/moodle_phpmailer.php');

        $config = get_config('local_sendwelcomemsg');

        $records = $DB->get_records(
            'local_sendwelcomemsg_queue',
            null,
            'timecreated ASC, id ASC',
            '*',
            0,
            self::BATCH_SIZE
        );

        foreach ($records as $record) {
            $user = \core_user::get_user($record->userid);
            $expired = (time() - (int)$record->timecreated) > self::MAX_AGE;

            if (!$user || $user->deleted || empty($user->email)) {
                mtrace("local_sendwelcomemsg: queue entry {$record->id} dropped, "
                    . "user {$record->userid} is missing, deleted or has no email address.");
                $DB->delete_records('local_sendwelcomemsg_queue', ['id' => $record->id]);
                continue;
            }

            mtrace("local_sendwelcomemsg: processing user {$user->id} (queue entry {$record->id}).");

            $userdomain = self::extract_email_domain($user->email);
            $mailerror = false;

            for ($i = 1; $i <= self::TEMPLATES; $i++) {
                if (empty($config->{"enable{$i}"})) {
                    continue;
                }

                $targetdomain = ltrim(\core_text::strtolower(trim((string)($config->{"domain{$i}"} ?? ''))), '@');
                if ($targetdomain !== '' && $userdomain !== $targetdomain) {
                    mtrace("local_sendwelcomemsg: template {$i} skipped, "
                        . "it targets the domain {$targetdomain}.");
                    continue;
                }

                try {
                    self::send_mail($user, $i, $config);
                    mtrace("local_sendwelcomemsg: template {$i} sent.");
                } catch (\Throwable $e) {
                    $mailerror = true;
                    mtrace("local_sendwelcomemsg: template {$i} failed: " . $e->getMessage());
                }
            }

            if ($mailerror && !$expired) {
                mtrace("local_sendwelcomemsg: queue entry {$record->id} kept for the next run.");
                continue;
            }

            if ($mailerror) {
                mtrace("local_sendwelcomemsg: queue entry {$record->id} dropped after "
                    . (self::MAX_AGE / DAYSECS) . ' days of failed delivery attempts.');
            }

            $DB->delete_records('local_sendwelcomemsg_queue', ['id' => $record->id]);
        }
    }

    /**
     * Extract the domain part of an email address.
     *
     * @param string $email The address to read.
     * @return string The lower-cased domain, or an empty string.
     */
    protected static function extract_email_domain(string $email): string {
        $email = \core_text::strtolower(trim($email));
        $pos = strrpos($email, '@');

        return $pos === false ? '' : substr($email, $pos + 1);
    }

    /**
     * Send one template to one user.
     *
     * @param \stdClass $user The recipient.
     * @param int $index The 1-based template number.
     * @param \stdClass $config The plugin configuration.
     * @throws \Exception When PHPMailer cannot deliver the message.
     */
    protected static function send_mail(\stdClass $user, int $index, \stdClass $config): void {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $host = trim((string)($config->{"smtp{$index}_host"} ?? ''));

        if ($host !== '') {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = (int)$config->{"smtp{$index}_port"};
            $mail->SMTPSecure = (string)($config->{"smtp{$index}_secure"} ?? '');
            $mail->SMTPAuth = !empty($config->{"smtp{$index}_user"});

            if ($mail->SMTPAuth) {
                $mail->Username = (string)$config->{"smtp{$index}_user"};
                $mail->Password = (string)$config->{"smtp{$index}_pass"};
            }

            // Certificate verification is only ever relaxed when an administrator has
            // explicitly asked for it on this template. Earlier releases silently
            // disabled it whenever a connection failed, which turned any outage into a
            // downgrade that nobody was told about.
            if (!empty($config->{"smtp{$index}_allowinsecure"})) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }
        }

        $body = (string)($config->{"body{$index}"} ?? '');
        $style = (string)($config->{"style{$index}"} ?? '');

        $mail->setFrom(
            (string)$config->{"from{$index}_email"},
            (string)$config->{"from{$index}_name"}
        );
        $mail->addAddress($user->email, fullname($user));
        $mail->Subject = (string)($config->{"subject{$index}"} ?? '');
        $mail->isHTML(true);
        $mail->Body = \html_writer::div($body, '', ['style' => $style]);
        $mail->AltBody = html_to_text($body);

        foreach (self::get_attachments($index) as $file) {
            $mail->addAttachment($file->copy_content_to_temp(), $file->get_filename());
        }

        $mail->send();
    }

    /**
     * Get the files attached to a template.
     *
     * @param int $index The 1-based template number.
     * @return \stored_file[] The stored files, without directory records.
     */
    protected static function get_attachments(int $index): array {
        $fs = get_file_storage();
        $context = \context_system::instance();

        return $fs->get_area_files(
            $context->id,
            'local_sendwelcomemsg',
            "attachment{$index}",
            0,
            'itemid, filepath, filename',
            false
        );
    }
}
