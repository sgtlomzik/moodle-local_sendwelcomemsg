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
 * Event observers for local_sendwelcomemsg.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg;

defined('MOODLE_INTERNAL') || die();

/**
 * Puts newly created users on the welcome message queue.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Queue a welcome message for a newly created user.
     *
     * The message itself is sent by the scheduled task, so that a slow or
     * unreachable SMTP server never holds up account creation.
     *
     * @param \core\event\user_created $event The user creation event.
     */
    public static function user_created(\core\event\user_created $event): void {
        global $DB;

        $userid = (int)$event->objectid;

        if (empty($userid) || isguestuser($userid)) {
            return;
        }

        if ($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $userid])) {
            return;
        }

        $DB->insert_record('local_sendwelcomemsg_queue', (object)[
            'userid' => $userid,
            'timecreated' => time(),
        ]);
    }
}
