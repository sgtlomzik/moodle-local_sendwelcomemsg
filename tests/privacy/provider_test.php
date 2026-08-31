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
 * Unit tests for the local_sendwelcomemsg privacy provider.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types\database_table;
use core_privacy\local\metadata\types\external_location;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for the privacy provider covering the pending welcome message queue.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_sendwelcomemsg\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Create a user, who is queued by the plugin's own event observer.
     *
     * @return \stdClass The new user.
     */
    private function create_queued_user(): \stdClass {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));

        return $user;
    }

    /**
     * The queue table and the SMTP hand-off are both declared.
     */
    public function test_get_metadata_describes_the_queue_and_the_mail_server(): void {
        $this->resetAfterTest();

        $collection = provider::get_metadata(new collection('local_sendwelcomemsg'));
        $items = $collection->get_collection();

        $this->assertCount(2, $items);

        $table = null;
        $external = null;
        foreach ($items as $item) {
            if ($item instanceof database_table) {
                $table = $item;
            } else if ($item instanceof external_location) {
                $external = $item;
            }
        }

        $this->assertNotNull($table, 'The queue table is not declared.');
        $this->assertSame('local_sendwelcomemsg_queue', $table->get_name());
        $this->assertEqualsCanonicalizing(
            ['userid', 'timecreated'],
            array_keys($table->get_privacy_fields())
        );

        $this->assertNotNull($external, 'The mail server is not declared.');
        $this->assertSame('smtp', $external->get_name());
    }

    /**
     * Every metadata string the provider names is actually defined.
     */
    public function test_metadata_strings_exist(): void {
        $this->resetAfterTest();

        $collection = provider::get_metadata(new collection('local_sendwelcomemsg'));

        foreach ($collection->get_collection() as $item) {
            $this->assertTrue(
                get_string_manager()->string_exists($item->get_summary(), 'local_sendwelcomemsg'),
                "Missing language string {$item->get_summary()}"
            );

            foreach ($item->get_privacy_fields() as $field => $identifier) {
                $this->assertTrue(
                    get_string_manager()->string_exists($identifier, 'local_sendwelcomemsg'),
                    "Missing language string {$identifier} for field {$field}"
                );
            }
        }
    }

    /**
     * A queued user's data lives in their own user context.
     */
    public function test_get_contexts_for_userid(): void {
        global $DB;

        $this->resetAfterTest();

        $queued = $this->create_queued_user();
        $notqueued = $this->getDataGenerator()->create_user();
        $DB->delete_records('local_sendwelcomemsg_queue', ['userid' => $notqueued->id]);

        $contextlist = provider::get_contexts_for_userid($queued->id);

        $this->assertCount(1, $contextlist);
        $this->assertEquals(
            \context_user::instance($queued->id)->id,
            $contextlist->get_contextids()[0]
        );

        $this->assertCount(0, provider::get_contexts_for_userid($notqueued->id));
    }

    /**
     * Only the owner of a user context is reported as holding data in it.
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $other = $this->create_queued_user();

        $userlist = new userlist(\context_user::instance($user->id), 'local_sendwelcomemsg');
        provider::get_users_in_context($userlist);

        $this->assertSame([(int)$user->id], array_map('intval', $userlist->get_userids()));
        $this->assertNotContains((int)$other->id, array_map('intval', $userlist->get_userids()));
    }

    /**
     * No user is reported for a context that is not a user context.
     */
    public function test_get_users_in_context_ignores_other_context_levels(): void {
        $this->resetAfterTest();

        $this->create_queued_user();
        $course = $this->getDataGenerator()->create_course();

        $userlist = new userlist(\context_course::instance($course->id), 'local_sendwelcomemsg');
        provider::get_users_in_context($userlist);

        $this->assertCount(0, $userlist);
    }

    /**
     * The export lists the pending welcome message.
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $context = \context_user::instance($user->id);

        provider::export_user_data(new approved_contextlist(
            $user,
            'local_sendwelcomemsg',
            [$context->id]
        ));

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data([get_string('privacy:queuepath', 'local_sendwelcomemsg')]);
        $this->assertCount(1, $data->queued);
        $this->assertNotEmpty($data->queued[0]->timecreated);
    }

    /**
     * Somebody else's user context exports nothing.
     */
    public function test_export_user_data_ignores_another_users_context(): void {
        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $other = $this->create_queued_user();
        $othercontext = \context_user::instance($other->id);

        provider::export_user_data(new approved_contextlist(
            $user,
            'local_sendwelcomemsg',
            [$othercontext->id]
        ));

        $this->assertFalse(writer::with_context($othercontext)->has_any_data());
    }

    /**
     * Deleting a user context clears that user's queue entry only.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $other = $this->create_queued_user();

        provider::delete_data_for_all_users_in_context(\context_user::instance($user->id));

        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $other->id]));
    }

    /**
     * Deleting a context that is not a user context clears nothing.
     */
    public function test_delete_data_for_all_users_in_context_ignores_other_levels(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $course = $this->getDataGenerator()->create_course();

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * Deleting one user leaves everybody else queued.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $other = $this->create_queued_user();

        provider::delete_data_for_user(new approved_contextlist(
            $user,
            'local_sendwelcomemsg',
            [\context_user::instance($user->id)->id]
        ));

        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $other->id]));
    }

    /**
     * Approving somebody else's context deletes nothing.
     */
    public function test_delete_data_for_user_ignores_another_users_context(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $other = $this->create_queued_user();

        provider::delete_data_for_user(new approved_contextlist(
            $user,
            'local_sendwelcomemsg',
            [\context_user::instance($other->id)->id]
        ));

        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $other->id]));
    }

    /**
     * Deleting an approved list of users removes exactly those users.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $other = $this->create_queued_user();

        provider::delete_data_for_users(new approved_userlist(
            \context_user::instance($user->id),
            'local_sendwelcomemsg',
            [$user->id]
        ));

        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $other->id]));
    }

    /**
     * An approved userlist in another context level deletes nothing.
     */
    public function test_delete_data_for_users_ignores_other_context_levels(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();
        $course = $this->getDataGenerator()->create_course();

        provider::delete_data_for_users(new approved_userlist(
            \context_course::instance($course->id),
            'local_sendwelcomemsg',
            [$user->id]
        ));

        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * An empty approved userlist deletes nothing.
     */
    public function test_delete_data_for_users_with_an_empty_list(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->create_queued_user();

        provider::delete_data_for_users(new approved_userlist(
            \context_user::instance($user->id),
            'local_sendwelcomemsg',
            []
        ));

        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }
}
