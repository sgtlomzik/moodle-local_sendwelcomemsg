<?php
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_sendwelcomemsg', get_string('pluginname', 'local_sendwelcomemsg'));
    for ($i = 1; $i <= 5; $i++) {
        $headingtitle = get_string('emailsetting', 'local_sendwelcomemsg') . ' ' . $i;
        $headinghtml = html_writer::tag(
            'span',
            $headingtitle . ' ' . html_writer::tag('span', '', ['class' => 'local-sendwelcomemsg-status']),
            [
                'class' => 'local-sendwelcomemsg-toggle',
                'data-target' => "id_s_local_sendwelcomemsg_expanded{$i}",
                'data-enable' => "id_s_local_sendwelcomemsg_enable{$i}",
                'role' => 'button',
                'tabindex' => '0',
                'style' => 'cursor:pointer;'
            ]
        );

        $settings->add(new admin_setting_configcheckbox(
            "local_sendwelcomemsg/expanded{$i}",
            get_string('expandsection', 'local_sendwelcomemsg') . " {$i}",
            '',
            0
        ));

        $settings->add(new admin_setting_heading('local_sendwelcomemsg/mail' . $i, $headinghtml, ''));

        $settings->add(new admin_setting_configcheckbox("local_sendwelcomemsg/enable{$i}", get_string('enableemail', 'local_sendwelcomemsg')." {$i}", '', 0));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/domain{$i}", get_string('targetdomain', 'local_sendwelcomemsg') . " {$i}", '', ''));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/from{$i}_email", get_string("fromemail", "local_sendwelcomemsg") . " {$i}", '', 'noreply@example.com'));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/from{$i}_name", get_string("fromname", "local_sendwelcomemsg") . " {$i}", '', 'Moodle Admin'));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/subject{$i}", get_string("emailsubject", "local_sendwelcomemsg") . " {$i}", '', 'Добро пожаловать'));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/style{$i}", get_string("emailstyle", "local_sendwelcomemsg") . " {$i}", '', 'max-width:700px; margin:0 auto;'));
        $settings->add(new admin_setting_confightmleditor("local_sendwelcomemsg/body{$i}", get_string("emailbody", "local_sendwelcomemsg") . " {$i}", '', ''));

        $settings->add(new admin_setting_configstoredfile("local_sendwelcomemsg/attachment{$i}", get_string("emailattachment", "local_sendwelcomemsg") . " {$i}", '', "attachment{$i}"));

        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/smtp{$i}_host", get_string("smtphost", "local_sendwelcomemsg"), '', 'smtp.example.com'));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/smtp{$i}_port", get_string("smtpport", "local_sendwelcomemsg"), '', '587'));
        $settings->add(new admin_setting_configselect("local_sendwelcomemsg/smtp{$i}_secure", get_string("smtpencryption", "local_sendwelcomemsg") , '', 'tls', [
            '' => 'Нет', 'ssl' => 'SSL', 'tls' => 'TLS'
        ]));
        $settings->add(new admin_setting_configtext("local_sendwelcomemsg/smtp{$i}_user", get_string("smtplogin", "local_sendwelcomemsg"), '', ''));
        $settings->add(new admin_setting_configpasswordunmask("local_sendwelcomemsg/smtp{$i}_pass", get_string("smtppassword", "local_sendwelcomemsg"), '', ''));

        $expandsetting = "local_sendwelcomemsg/expanded{$i}";
        $sectionsettings = [
            "local_sendwelcomemsg/enable{$i}",
            "local_sendwelcomemsg/domain{$i}",
            "local_sendwelcomemsg/from{$i}_email",
            "local_sendwelcomemsg/from{$i}_name",
            "local_sendwelcomemsg/subject{$i}",
            "local_sendwelcomemsg/style{$i}",
            "local_sendwelcomemsg/body{$i}",
            "local_sendwelcomemsg/attachment{$i}",
            "local_sendwelcomemsg/smtp{$i}_host",
            "local_sendwelcomemsg/smtp{$i}_port",
            "local_sendwelcomemsg/smtp{$i}_secure",
            "local_sendwelcomemsg/smtp{$i}_user",
            "local_sendwelcomemsg/smtp{$i}_pass",
        ];
        foreach ($sectionsettings as $settingname) {
            $settings->hide_if($settingname, $expandsetting, 'notchecked');
        }
    }

    $settings->add(new admin_setting_heading(
        'local_sendwelcomemsg/accordionjs',
        '',
        html_writer::script("
            (function() {
                var activeLabel = " . json_encode(get_string('statusactive', 'local_sendwelcomemsg')) . ";
                var inactiveLabel = " . json_encode(get_string('statusinactive', 'local_sendwelcomemsg')) . ";
                var toggles = document.querySelectorAll('.local-sendwelcomemsg-toggle');

                var updateStatus = function(toggle) {
                    var enableId = toggle.getAttribute('data-enable');
                    var enableCheckbox = document.getElementById(enableId);
                    var status = toggle.querySelector('.local-sendwelcomemsg-status');
                    if (!enableCheckbox || !status) {
                        return;
                    }
                    status.textContent = enableCheckbox.checked ? ' (' + activeLabel + ')' : ' (' + inactiveLabel + ')';
                };

                toggles.forEach(function(toggle) {
                    updateStatus(toggle);
                    var onToggle = function() {
                        var targetId = toggle.getAttribute('data-target');
                        var checkbox = document.getElementById(targetId);
                        if (!checkbox) {
                            return;
                        }
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change', {bubbles: true}));
                    };
                    toggle.addEventListener('click', onToggle);
                    toggle.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            onToggle();
                        }
                    });

                    var enableId = toggle.getAttribute('data-enable');
                    var enableCheckbox = document.getElementById(enableId);
                    if (enableCheckbox) {
                        enableCheckbox.addEventListener('change', function() {
                            updateStatus(toggle);
                        });
                    }
                });

                var checkboxes = document.querySelectorAll('input[id^=\"id_s_local_sendwelcomemsg_expanded\"]');
                checkboxes.forEach(function(checkbox) {
                    var formItem = checkbox.closest('.form-item');
                    if (formItem) {
                        formItem.style.display = 'none';
                    }
                });
            })();
        ")
    ));

    $ADMIN->add('localplugins', $settings);
}