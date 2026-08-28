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
 * English strings for local_sendwelcomemsg.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['emailattachment'] = 'Attachment';
$string['emailbody'] = 'Message body';
$string['emailsetting'] = 'Message template';
$string['emailstyle'] = 'Body style';
$string['emailstyle_desc'] = 'Inline CSS applied to the wrapper around the message body, for example "max-width:700px; margin:0 auto;".';
$string['emailsubject'] = 'Subject';
$string['emailsubject_default'] = 'Welcome';
$string['enableemail'] = 'Enable template';
$string['enableemail_desc'] = 'Send this template to newly created users. Every enabled template whose target domain matches is sent, so a user can receive more than one message.';
$string['expandsection'] = 'Expand template';
$string['fromemail'] = 'Sender email';
$string['fromname'] = 'Sender name';
$string['pluginname'] = 'Send welcome messages';
$string['privacy:metadata:queue'] = 'A list of newly created users whose welcome message has not been sent yet. Entries are removed as soon as the message is delivered.';
$string['privacy:metadata:queue:timecreated'] = 'The time the user was added to the queue.';
$string['privacy:metadata:queue:userid'] = 'The ID of the user waiting for a welcome message.';
$string['privacy:metadata:smtp'] = 'Welcome messages are delivered through the SMTP server configured for each template, which may be operated by a third party.';
$string['privacy:metadata:smtp:email'] = 'The email address the welcome message is sent to.';
$string['privacy:metadata:smtp:fullname'] = 'The full name of the recipient, used in the message envelope.';
$string['privacy:queuepath'] = 'Pending welcome messages';
$string['smtpallowinsecure'] = 'Allow untrusted certificates';
$string['smtpallowinsecure_desc'] = 'Skip TLS certificate verification for this SMTP server. This makes the connection vulnerable to interception, so only enable it for a server on a trusted network whose self-signed certificate you cannot replace.';
$string['smtpencryption'] = 'Encryption';
$string['smtpencryptionnone'] = 'None';
$string['smtpencryptionssl'] = 'SSL';
$string['smtpencryptiontls'] = 'TLS';
$string['smtphost'] = 'SMTP host';
$string['smtphost_desc'] = 'The SMTP server used for this template. Leave empty to hand the message to the mail transport configured on the web server instead.';
$string['smtplogin'] = 'SMTP username';
$string['smtppassword'] = 'SMTP password';
$string['smtpport'] = 'SMTP port';
$string['smtpsettings'] = 'SMTP settings';
$string['statusactive'] = 'active';
$string['statusinactive'] = 'inactive';
$string['targetdomain'] = 'Target email domain';
$string['targetdomain_desc'] = 'Only send this template to users whose email address is in this domain, for example "example.com". Leave empty to send it to everyone.';
$string['taskname'] = 'Send queued welcome messages';
