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
 * Unit tests for the local_sendwelcomemsg library functions.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/sendwelcomemsg/lib.php');
require_once($CFG->libdir . '/adminlib.php');

/**
 * Tests for the file serving callback and the settings accordion heading.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_sendwelcomemsg_pluginfile
 * @covers     \local_sendwelcomemsg\admin_setting_accordion
 */
final class lib_test extends \advanced_testcase {
    /**
     * Template attachments are never served over the web.
     *
     * They are read server-side when a message is composed, so handing them out
     * over pluginfile.php would publish them to anybody who guessed the URL.
     */
    public function test_pluginfile_refuses_every_request(): void {
        $this->resetAfterTest();

        $context = \context_system::instance();

        $this->assertFalse(
            local_sendwelcomemsg_pluginfile(null, null, $context, 'attachment1', ['1', 'terms.pdf'], true)
        );
        $this->assertFalse(
            local_sendwelcomemsg_pluginfile(null, null, $context, 'attachment1', [], false, [])
        );
        $this->assertFalse(
            local_sendwelcomemsg_pluginfile(null, null, $context, 'made_up_area', ['x'], false)
        );
    }

    /**
     * The accordion heading renders and asks for its behaviour once per page.
     */
    public function test_the_accordion_heading_loads_its_script_once(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        // The flag is static, so it survives the reset between tests.
        $loaded = new \ReflectionProperty(admin_setting_accordion::class, 'jsloaded');
        $loaded->setValue(null, false);

        $PAGE->set_url('/admin/settings.php', ['section' => 'local_sendwelcomemsg']);

        $heading = new admin_setting_accordion(
            'local_sendwelcomemsg/mail1',
            'Message template 1',
            ''
        );

        $html = $heading->output_html('');
        $this->assertStringContainsString('Message template 1', $html);
        $this->assertTrue($loaded->getValue());

        // A second heading on the same page must not request the module again.
        $this->assertStringContainsString(
            'Message template 2',
            (new admin_setting_accordion('local_sendwelcomemsg/mail2', 'Message template 2', ''))->output_html('')
        );

        $loaded->setValue(null, false);
    }
}
