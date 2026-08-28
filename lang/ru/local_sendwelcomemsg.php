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
 * Russian strings for local_sendwelcomemsg.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['emailattachment'] = 'Вложение';
$string['emailbody'] = 'Текст письма';
$string['emailsetting'] = 'Шаблон письма';
$string['emailstyle'] = 'Стиль письма';
$string['emailstyle_desc'] = 'Встроенный CSS для обёртки вокруг текста письма, например «max-width:700px; margin:0 auto;».';
$string['emailsubject'] = 'Тема письма';
$string['emailsubject_default'] = 'Добро пожаловать';
$string['enableemail'] = 'Включить шаблон';
$string['enableemail_desc'] = 'Отправлять этот шаблон новым пользователям. Отправляются все включённые шаблоны с подходящим доменом, поэтому пользователь может получить несколько писем.';
$string['expandsection'] = 'Развернуть шаблон';
$string['fromemail'] = 'Email отправителя';
$string['fromname'] = 'Имя отправителя';
$string['pluginname'] = 'Отправка приветственных сообщений';
$string['privacy:metadata:queue'] = 'Список новых пользователей, которым ещё не отправлено приветственное письмо. Записи удаляются сразу после отправки.';
$string['privacy:metadata:queue:timecreated'] = 'Время добавления пользователя в очередь.';
$string['privacy:metadata:queue:userid'] = 'ID пользователя, ожидающего приветственное письмо.';
$string['privacy:metadata:smtp'] = 'Приветственные письма отправляются через SMTP-сервер, указанный в шаблоне; он может принадлежать третьей стороне.';
$string['privacy:metadata:smtp:email'] = 'Адрес электронной почты, на который отправляется письмо.';
$string['privacy:metadata:smtp:fullname'] = 'Полное имя получателя, используемое в конверте письма.';
$string['privacy:queuepath'] = 'Ожидающие приветственные письма';
$string['smtpallowinsecure'] = 'Разрешить недоверенные сертификаты';
$string['smtpallowinsecure_desc'] = 'Не проверять TLS-сертификат этого SMTP-сервера. Соединение становится уязвимым к перехвату, поэтому включайте настройку только для сервера в доверенной сети, самоподписанный сертификат которого нельзя заменить.';
$string['smtpencryption'] = 'Шифрование';
$string['smtpencryptionnone'] = 'Нет';
$string['smtpencryptionssl'] = 'SSL';
$string['smtpencryptiontls'] = 'TLS';
$string['smtphost'] = 'SMTP хост';
$string['smtphost_desc'] = 'SMTP-сервер для этого шаблона. Оставьте пустым, чтобы отправлять письмо средствами почтового транспорта, настроенного на веб-сервере.';
$string['smtplogin'] = 'SMTP логин';
$string['smtppassword'] = 'SMTP пароль';
$string['smtpport'] = 'SMTP порт';
$string['smtpsettings'] = 'Настройки SMTP';
$string['statusactive'] = 'активно';
$string['statusinactive'] = 'не активно';
$string['targetdomain'] = 'Домен email (для шаблона)';
$string['targetdomain_desc'] = 'Отправлять шаблон только пользователям с адресом в этом домене, например «example.com». Оставьте пустым, чтобы отправлять всем.';
$string['taskname'] = 'Отправка приветственных сообщений из очереди';
