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
 * Heading that turns the message templates into an accordion.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg;

defined('MOODLE_INTERNAL') || die();

/**
 * A settings heading that loads the accordion behaviour when it is rendered.
 *
 * Requiring the module from output_html() rather than from settings.php matters:
 * settings.php is executed while the admin tree is built, including during cron
 * and upgrades, where there is no page to attach JavaScript to.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_accordion extends \admin_setting_heading {

    /** @var bool Whether the AMD module has already been requested for this page. */
    protected static $jsloaded = false;

    /**
     * Render the heading and make sure the accordion script is loaded once.
     *
     * @param mixed $data Unused, headings hold no value.
     * @param string $query Search term the settings page was filtered by.
     * @return string The rendered heading.
     */
    public function output_html($data, $query = '') {
        global $PAGE;

        if (!self::$jsloaded) {
            $PAGE->requires->js_call_amd('local_sendwelcomemsg/settings', 'init', [
                get_string('statusactive', 'local_sendwelcomemsg'),
                get_string('statusinactive', 'local_sendwelcomemsg'),
            ]);
            self::$jsloaded = true;
        }

        return parent::output_html($data, $query);
    }
}
