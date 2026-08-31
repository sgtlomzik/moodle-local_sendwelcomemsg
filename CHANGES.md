# Changelog

All notable changes to this plugin are documented in this file.

## [1.1.1] - 2026-08-31

### Fixed
- **The privacy provider raised a fatal error.** `get_metadata()` called
  `collection::add_user_data_external_location_link()`, which is not part of the
  Privacy API and never has been, so every request for the plugin's metadata
  died with `Call to undefined method`. That took down the plugin privacy
  compliance report, the data registry and any data request touching this
  plugin. It now calls `add_external_location_link()`, which is the real method.

### Added
- Privacy provider tests covering metadata, the contexts and users a person's
  data is found in, the export, and all three deletion paths, including the
  context levels the provider has to ignore. These are what found the fatal
  error above.
- Tests for the delivery task: its name and registration, the email domain
  parsing, target domain matching, disabled templates, the retry of a failed
  delivery, giving up on an entry that has been failing for a week, dropping
  entries for missing users and users without an address, and the oldest-first
  order the queue is worked in.
- Tests for `local_sendwelcomemsg_pluginfile` refusing every request and for the
  settings accordion heading loading its script once per page.
- Observer tests for the registration of the user creation event, the recorded
  queue time, and the guest account never being queued.

## [1.1.0] - 2026-08-28

### Security
- **Removed `in.php`.** It was a web-reachable script that ran the whole delivery task
  synchronously on request. It checked `is_siteadmin()` but had no sesskey check, so any page an
  administrator could be lured into visiting could fire the mail run; it was referenced from
  nowhere and its own header comment named a different file.
- **Removed the silent TLS downgrade.** When a send failed, the task retried it with
  `verify_peer`, `verify_peer_name` and `allow_self_signed` disabled, and did the same
  unconditionally for `localhost:25`. The trigger list included "could not connect to smtp host",
  so an ordinary outage was enough to retry the message over an unverified connection with the
  SMTP credentials attached. Certificate verification is now relaxed only when an administrator
  ticks the new per-template **Allow untrusted certificates** setting, which is off by default.

### Added
- GPLv3 boilerplate, `@package`/`@copyright`/`@license` docblocks on every file and a `COPYING`
  file with the licence text.
- A full Privacy API provider (metadata, export, delete, userlist) for the queue table and the
  external SMTP delivery. The plugin previously declared nothing at all despite storing user ids.
- `db/upgrade.php`, adding indexes on `userid` and `timecreated`.
- Descriptions and help text for every setting, and a dedicated name for the scheduled task
  instead of reusing the plugin name.
- Moodle plugin CI workflow and `$plugin->supported`.

### Changed
- All log output and comments translated from Russian to English, including the `mtrace()`
  messages the task writes to the cron log and the comments in `db/install.xml`.
- Settings page JavaScript moved out of an inline `html_writer::script()` block into the
  `local_sendwelcomemsg/settings` AMD module, loaded from a settings class when the page is
  actually rendered, and the inline `style` attributes moved into `styles.css`.
- Encryption choices and the default subject come from language strings instead of hardcoded
  Russian text.
- `smtp*_port` is `PARAM_INT` and `from*_email` is `PARAM_EMAIL`.
- Minimum requirement raised to Moodle 4.5 (LTS).

### Fixed
- A queue entry whose delivery kept failing was retried every five minutes forever. Entries are
  now dropped after seven days, with a line in the cron log saying so.
- The task read the entire queue in one go; it now takes 100 entries per run, oldest first.
- Users are looked up with `core_user::get_user()`, and deleted users or accounts with no email
  address are dropped from the queue instead of being reprocessed on every run.
- The observer no longer queues guests, and will not add a second entry for a user who is already
  queued.
- Null template values no longer reach `html_writer::div()` and `html_to_text()`, which raised
  deprecation notices on PHP 8.1 and later.
- `moodle_phpmailer.php` is required before `PHPMailer` is used rather than relying on the class
  happening to be autoloaded.
- The SMTP host, port and encryption are only applied when a host is configured, instead of being
  set on the mailer and then partly ignored.
