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
 * Admin settings for local_sendwelcomemsg.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_sendwelcomemsg',
        get_string('pluginname', 'local_sendwelcomemsg')
    );

    $encryptionchoices = [
        '' => get_string('smtpencryptionnone', 'local_sendwelcomemsg'),
        'ssl' => get_string('smtpencryptionssl', 'local_sendwelcomemsg'),
        'tls' => get_string('smtpencryptiontls', 'local_sendwelcomemsg'),
    ];

    for ($i = 1; $i <= \local_sendwelcomemsg\task\send_welcome_emails::TEMPLATES; $i++) {
        // The "expanded" flag exists only to drive hide_if below; the settings.js
        // module hides it and lets the heading be clicked instead.
        $settings->add(new admin_setting_configcheckbox(
            "local_sendwelcomemsg/expanded{$i}",
            get_string('expandsection', 'local_sendwelcomemsg') . " {$i}",
            '',
            0
        ));

        $headinghtml = html_writer::tag(
            'span',
            get_string('emailsetting', 'local_sendwelcomemsg') . " {$i} "
                . html_writer::tag('span', '', ['class' => 'local-sendwelcomemsg-status']),
            [
                'class' => 'local-sendwelcomemsg-toggle',
                'data-target' => "id_s_local_sendwelcomemsg_expanded{$i}",
                'data-enable' => "id_s_local_sendwelcomemsg_enable{$i}",
                'role' => 'button',
                'tabindex' => '0',
                'aria-expanded' => 'false',
            ]
        );
        $settings->add(new \local_sendwelcomemsg\admin_setting_accordion(
            "local_sendwelcomemsg/mail{$i}",
            $headinghtml,
            ''
        ));

        $settings->add(new admin_setting_configcheckbox(
            "local_sendwelcomemsg/enable{$i}",
            get_string('enableemail', 'local_sendwelcomemsg') . " {$i}",
            get_string('enableemail_desc', 'local_sendwelcomemsg'),
            0
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/domain{$i}",
            get_string('targetdomain', 'local_sendwelcomemsg') . " {$i}",
            get_string('targetdomain_desc', 'local_sendwelcomemsg'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/from{$i}_email",
            get_string('fromemail', 'local_sendwelcomemsg') . " {$i}",
            '',
            'noreply@example.com',
            PARAM_EMAIL
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/from{$i}_name",
            get_string('fromname', 'local_sendwelcomemsg') . " {$i}",
            '',
            ''
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/subject{$i}",
            get_string('emailsubject', 'local_sendwelcomemsg') . " {$i}",
            '',
            get_string('emailsubject_default', 'local_sendwelcomemsg')
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/style{$i}",
            get_string('emailstyle', 'local_sendwelcomemsg') . " {$i}",
            get_string('emailstyle_desc', 'local_sendwelcomemsg'),
            'max-width:700px; margin:0 auto;'
        ));
        $settings->add(new admin_setting_confightmleditor(
            "local_sendwelcomemsg/body{$i}",
            get_string('emailbody', 'local_sendwelcomemsg') . " {$i}",
            '',
            ''
        ));
        $settings->add(new admin_setting_configstoredfile(
            "local_sendwelcomemsg/attachment{$i}",
            get_string('emailattachment', 'local_sendwelcomemsg') . " {$i}",
            '',
            "attachment{$i}"
        ));

        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/smtp{$i}_host",
            get_string('smtphost', 'local_sendwelcomemsg'),
            get_string('smtphost_desc', 'local_sendwelcomemsg'),
            ''
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/smtp{$i}_port",
            get_string('smtpport', 'local_sendwelcomemsg'),
            '',
            587,
            PARAM_INT
        ));
        $settings->add(new admin_setting_configselect(
            "local_sendwelcomemsg/smtp{$i}_secure",
            get_string('smtpencryption', 'local_sendwelcomemsg'),
            '',
            'tls',
            $encryptionchoices
        ));
        $settings->add(new admin_setting_configtext(
            "local_sendwelcomemsg/smtp{$i}_user",
            get_string('smtplogin', 'local_sendwelcomemsg'),
            '',
            ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            "local_sendwelcomemsg/smtp{$i}_pass",
            get_string('smtppassword', 'local_sendwelcomemsg'),
            '',
            ''
        ));
        $settings->add(new admin_setting_configcheckbox(
            "local_sendwelcomemsg/smtp{$i}_allowinsecure",
            get_string('smtpallowinsecure', 'local_sendwelcomemsg'),
            get_string('smtpallowinsecure_desc', 'local_sendwelcomemsg'),
            0
        ));

        foreach (
            [
            'enable', 'domain', 'from%d_email', 'from%d_name', 'subject', 'style', 'body',
            'attachment', 'smtp%d_host', 'smtp%d_port', 'smtp%d_secure', 'smtp%d_user',
            'smtp%d_pass', 'smtp%d_allowinsecure',
            ] as $name
        ) {
            $suffix = strpos($name, '%d') === false ? $name . $i : sprintf($name, $i);
            $settings->hide_if(
                "local_sendwelcomemsg/{$suffix}",
                "local_sendwelcomemsg/expanded{$i}",
                'notchecked'
            );
        }
    }

    $ADMIN->add('localplugins', $settings);
}
