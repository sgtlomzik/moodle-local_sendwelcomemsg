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
 * Tests for the welcome message queue.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg;

use local_sendwelcomemsg\task\send_welcome_emails;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering how users reach and leave the queue.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_sendwelcomemsg\observer
 * @covers     \local_sendwelcomemsg\task\send_welcome_emails
 */
final class queue_test extends \advanced_testcase {

    /**
     * Creating a user queues a welcome message.
     */
    public function test_creating_a_user_queues_a_welcome_message(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * A user is only queued once.
     */
    public function test_a_user_is_only_queued_once(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        // Replay the event; the queue must not gain a second entry.
        \core\event\user_created::create_from_userid($user->id)->trigger();

        $this->assertSame(1, $DB->count_records('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * The task clears the queue when no template is enabled.
     */
    public function test_the_task_clears_the_queue_when_no_template_is_enabled(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));

        ob_start();
        (new send_welcome_emails())->execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * The task drops deleted users.
     */
    public function test_the_task_drops_deleted_users(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        delete_user($user);

        ob_start();
        (new send_welcome_emails())->execute();
        ob_end_clean();

        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }
}
