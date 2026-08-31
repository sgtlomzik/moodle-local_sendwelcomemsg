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
 * Unit tests for the welcome message delivery task.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sendwelcomemsg\task;

/**
 * Tests for how the scheduled task walks the queue.
 *
 * No message is ever delivered from these tests. Templates are either switched
 * off, filtered out by their target domain, or pointed at a closed local port so
 * that delivery fails at once, which is what the retry handling needs.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_sendwelcomemsg\task\send_welcome_emails
 */
final class send_welcome_emails_test extends \advanced_testcase {
    /**
     * Run the task, capturing the progress it writes to the cron log.
     *
     * @return string Everything the task printed.
     */
    private function run_task(): string {
        ob_start();
        (new send_welcome_emails())->execute();

        return (string)ob_get_clean();
    }

    /**
     * Configure one template that can never deliver anything.
     *
     * The host is a closed port on the loopback interface, so the connection is
     * refused straight away rather than waiting for a network timeout.
     *
     * @param int $index The 1-based template number.
     * @param string $domain Target domain for the template, or '' for everybody.
     */
    private function set_failing_template(int $index, string $domain = ''): void {
        set_config("enable{$index}", 1, 'local_sendwelcomemsg');
        set_config("domain{$index}", $domain, 'local_sendwelcomemsg');
        set_config("from{$index}_email", 'noreply@example.com', 'local_sendwelcomemsg');
        set_config("from{$index}_name", 'Example site', 'local_sendwelcomemsg');
        set_config("subject{$index}", 'Welcome', 'local_sendwelcomemsg');
        set_config("body{$index}", '<p>Welcome aboard.</p>', 'local_sendwelcomemsg');
        set_config("style{$index}", '', 'local_sendwelcomemsg');
        set_config("smtp{$index}_host", '127.0.0.1', 'local_sendwelcomemsg');
        set_config("smtp{$index}_port", 1, 'local_sendwelcomemsg');
        set_config("smtp{$index}_secure", '', 'local_sendwelcomemsg');
        set_config("smtp{$index}_user", '', 'local_sendwelcomemsg');
        set_config("smtp{$index}_pass", '', 'local_sendwelcomemsg');
    }

    /**
     * Backdate a user's queue entry.
     *
     * @param int $userid The queued user.
     * @param int $age How long ago the entry was created, in seconds.
     */
    private function age_queue_entry(int $userid, int $age): void {
        global $DB;

        $DB->set_field('local_sendwelcomemsg_queue', 'timecreated', time() - $age, ['userid' => $userid]);
    }

    /**
     * The task is named from a language string rather than a hardcoded label.
     */
    public function test_get_name(): void {
        $this->resetAfterTest();

        $this->assertSame(
            get_string('taskname', 'local_sendwelcomemsg'),
            (new send_welcome_emails())->get_name()
        );
    }

    /**
     * The task is registered in db/tasks.php and is picked up by core.
     */
    public function test_the_task_is_scheduled(): void {
        $this->resetAfterTest();

        $task = \core\task\manager::get_scheduled_task(send_welcome_emails::class);

        $this->assertInstanceOf(send_welcome_emails::class, $task);
        $this->assertSame('local_sendwelcomemsg', $task->get_component());
    }

    /**
     * The number of configurable templates matches what settings.php builds.
     */
    public function test_the_template_count_is_exposed(): void {
        $this->resetAfterTest();

        $this->assertGreaterThan(0, send_welcome_emails::TEMPLATES);
        $this->assertTrue(
            get_string_manager()->string_exists('emailsetting', 'local_sendwelcomemsg')
        );
    }

    /**
     * Email domains are read from the address, case insensitively.
     *
     * @param string $email The address to read.
     * @param string $expected The domain the task should extract.
     * @dataProvider email_domain_provider
     */
    public function test_extract_email_domain(string $email, string $expected): void {
        $method = new \ReflectionMethod(send_welcome_emails::class, 'extract_email_domain');

        $this->assertSame($expected, $method->invoke(null, $email));
    }

    /**
     * Data provider for {@see test_extract_email_domain()}.
     *
     * @return array[] Address and the domain expected from it.
     */
    public static function email_domain_provider(): array {
        return [
            'plain address' => ['learner@example.com', 'example.com'],
            'mixed case' => ['Learner@Example.COM', 'example.com'],
            'padded' => ['  learner@example.com  ', 'example.com'],
            'subdomain' => ['learner@mail.example.com', 'mail.example.com'],
            'quoted local part with an at sign' => ['"odd@name"@example.com', 'example.com'],
            'not an address' => ['learner', ''],
            'empty' => ['', ''],
        ];
    }

    /**
     * A template aimed at another domain is skipped, and the entry is cleared.
     */
    public function test_a_template_for_another_domain_is_skipped(): void {
        global $DB;

        $this->resetAfterTest();

        // Pointed at a dead host, so a delivery attempt would fail and keep the
        // entry queued. It is cleared, which proves no attempt was made.
        $this->set_failing_template(1, 'partner.example.org');

        $user = $this->getDataGenerator()->create_user(['email' => 'learner@example.com']);

        $output = $this->run_task();

        $this->assertStringContainsString('template 1 skipped', $output);
        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * The target domain is matched without regard to case or a leading at sign.
     *
     * @param string $configured The domain as an administrator typed it.
     * @dataProvider matching_domain_provider
     */
    public function test_the_target_domain_is_matched_leniently(string $configured): void {
        global $DB;

        $this->resetAfterTest();

        $this->set_failing_template(1, $configured);

        $user = $this->getDataGenerator()->create_user(['email' => 'learner@example.com']);

        $output = $this->run_task();

        // The domain matched, so delivery was attempted and failed against the
        // closed port, which keeps the entry queued for the next run.
        $this->assertStringContainsString('template 1 failed', $output);
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * Data provider for {@see test_the_target_domain_is_matched_leniently()}.
     *
     * @return array[] Ways of writing the same target domain.
     */
    public static function matching_domain_provider(): array {
        return [
            'plain' => ['example.com'],
            'with an at sign' => ['@example.com'],
            'upper case' => ['EXAMPLE.COM'],
            'padded' => ['  example.com '],
        ];
    }

    /**
     * A template with no target domain is sent to everybody.
     */
    public function test_a_template_without_a_domain_targets_everybody(): void {
        $this->resetAfterTest();

        $this->set_failing_template(1, '');
        $this->getDataGenerator()->create_user(['email' => 'learner@somewhere.else']);

        $this->assertStringContainsString('template 1 failed', $this->run_task());
    }

    /**
     * A disabled template is never attempted, however it is configured.
     */
    public function test_a_disabled_template_is_never_attempted(): void {
        global $DB;

        $this->resetAfterTest();

        $this->set_failing_template(1);
        set_config('enable1', 0, 'local_sendwelcomemsg');

        $user = $this->getDataGenerator()->create_user();

        $output = $this->run_task();

        $this->assertStringNotContainsString('template 1', $output);
        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * A failed delivery keeps the entry queued so the next run retries it.
     */
    public function test_a_failed_delivery_is_retried(): void {
        global $DB;

        $this->resetAfterTest();

        $this->set_failing_template(1);
        $user = $this->getDataGenerator()->create_user();

        $output = $this->run_task();

        $this->assertStringContainsString('kept for the next run', $output);
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));

        // And again on the run after that, as long as the entry is still young.
        $this->run_task();
        $this->assertTrue($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * An entry that has been failing for too long is given up on.
     */
    public function test_an_entry_is_dropped_once_it_is_too_old(): void {
        global $DB;

        $this->resetAfterTest();

        $this->set_failing_template(1);
        $user = $this->getDataGenerator()->create_user();
        $this->age_queue_entry($user->id, 8 * DAYSECS);

        $output = $this->run_task();

        $this->assertStringContainsString('days of failed delivery attempts', $output);
        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * An old entry that delivers cleanly is not reported as given up on.
     */
    public function test_an_old_entry_without_failures_is_cleared_quietly(): void {
        global $DB;

        $this->resetAfterTest();

        // No template is enabled, so there is nothing to fail.
        $user = $this->getDataGenerator()->create_user();
        $this->age_queue_entry($user->id, 8 * DAYSECS);

        $output = $this->run_task();

        $this->assertStringNotContainsString('days of failed delivery attempts', $output);
        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * A queue entry for a user who no longer exists is dropped.
     */
    public function test_an_entry_for_a_missing_user_is_dropped(): void {
        global $DB;

        $this->resetAfterTest();

        $this->set_failing_template(1);

        $id = $DB->insert_record('local_sendwelcomemsg_queue', (object)[
            'userid' => -1,
            'timecreated' => time(),
        ]);

        $output = $this->run_task();

        $this->assertStringContainsString('is missing, deleted or has no email address', $output);
        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['id' => $id]));
    }

    /**
     * A user without an email address is dropped rather than retried forever.
     */
    public function test_an_entry_for_a_user_without_an_email_is_dropped(): void {
        global $DB;

        $this->resetAfterTest();

        $this->set_failing_template(1);

        $user = $this->getDataGenerator()->create_user();
        $DB->set_field('user', 'email', '', ['id' => $user->id]);

        $output = $this->run_task();

        $this->assertStringContainsString('is missing, deleted or has no email address', $output);
        $this->assertFalse($DB->record_exists('local_sendwelcomemsg_queue', ['userid' => $user->id]));
    }

    /**
     * Every enabled template is considered for one user.
     */
    public function test_every_enabled_template_is_considered(): void {
        $this->resetAfterTest();

        $this->set_failing_template(1, 'example.com');
        $this->set_failing_template(2, 'partner.example.org');

        $this->getDataGenerator()->create_user(['email' => 'learner@example.com']);

        $output = $this->run_task();

        $this->assertStringContainsString('template 1 failed', $output);
        $this->assertStringContainsString('template 2 skipped', $output);
    }

    /**
     * The queue is worked oldest first.
     */
    public function test_the_queue_is_processed_oldest_first(): void {
        $this->resetAfterTest();

        $newer = $this->getDataGenerator()->create_user();
        $older = $this->getDataGenerator()->create_user();

        $this->age_queue_entry($older->id, 2 * DAYSECS);
        $this->age_queue_entry($newer->id, DAYSECS);

        $output = $this->run_task();

        $this->assertLessThan(
            strpos($output, "processing user {$newer->id}"),
            strpos($output, "processing user {$older->id}")
        );
    }

    /**
     * An empty queue is a quiet no-op.
     */
    public function test_an_empty_queue_does_nothing(): void {
        global $DB;

        $this->resetAfterTest();

        $DB->delete_records('local_sendwelcomemsg_queue');

        $this->assertSame('', $this->run_task());
    }
}
