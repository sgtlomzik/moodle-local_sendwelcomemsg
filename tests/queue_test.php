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

    /**
     * The observer is registered for the user creation event.
     */
    public function test_the_observer_is_registered(): void {
        $this->resetAfterTest();

        $observers = \core\event\manager::get_all_observers();

        $this->assertArrayHasKey('\\core\\event\\user_created', $observers);

        $callbacks = array_map(static function ($observer) {
            return $observer->callable;
        }, $observers['\\core\\event\\user_created']);

        $this->assertContains('\\local_sendwelcomemsg\\observer::user_created', $callbacks);
    }

    /**
     * The queue entry records when the user was created.
     */
    public function test_the_queue_entry_records_the_time(): void {
        global $DB;

        $this->resetAfterTest();

        $before = time();
        $user = $this->getDataGenerator()->create_user();

        $record = $DB->get_record('local_sendwelcomemsg_queue', ['userid' => $user->id], '*', MUST_EXIST);

        $this->assertGreaterThanOrEqual($before, (int)$record->timecreated);
        $this->assertLessThanOrEqual(time(), (int)$record->timecreated);
    }

    /**
     * The guest account is never sent a welcome message.
     */
    public function test_the_guest_account_is_not_queued(): void {
        global $CFG, $DB;

        $this->resetAfterTest();

        $DB->delete_records('local_sendwelcomemsg_queue', ['userid' => $CFG->siteguest]);

        observer::user_created(\core\event\user_created::create_from_userid($CFG->siteguest));

        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $CFG->siteguest]));
    }

    /**
     * Every new user gets their own queue entry.
     */
    public function test_each_new_user_is_queued_separately(): void {
        global $DB;

        $this->resetAfterTest();

        $DB->delete_records('local_sendwelcomemsg_queue');

        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();

        $this->assertEquals(2, $DB->count_records('local_sendwelcomemsg_queue'));
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $first->id]));
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $second->id]));
    }

    /**
     * The task leaves other users on the queue when it clears one of them.
     */
    public function test_the_task_clears_the_whole_queue_when_nothing_is_enabled(): void {
        global $DB;

        $this->resetAfterTest();

        $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user();

        ob_start();
        (new send_welcome_emails())->execute();
        ob_end_clean();

        $this->assertEquals(0, $DB->count_records('local_sendwelcomemsg_queue'));
    }
}
