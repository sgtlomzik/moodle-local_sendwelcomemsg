# Send welcome messages for Moodle

[![Moodle plugin CI](https://github.com/sgtlomzik/moodle-local_sendwelcomemsg/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/sgtlomzik/moodle-local_sendwelcomemsg/actions/workflows/moodle-ci.yml)

Sends a designed HTML welcome email to every newly created Moodle account, with up to five
templates that can be routed by email domain and delivered through their own SMTP servers.

Moodle's own "new account" email is a plain confirmation message with one wording for the whole
site. This plugin is for sites that need something else: a branded onboarding email, with an
attachment, worded differently for staff and for external learners, and sent from the address
that matches each audience.

## Requirements

- Moodle 4.5 (LTS) or later.
- Moodle cron running regularly — messages are queued and delivered by a scheduled task,
  not during account creation.
- An SMTP server per template, or a working mail transport on the web server.

## Installation

### From the ZIP file

1. Download the ZIP of this repository.
2. Go to **Site administration → Plugins → Install plugins** and upload the ZIP.
3. Follow the on-screen upgrade steps.

### From Git

```bash
cd /path/to/moodle
git clone https://github.com/sgtlomzik/moodle-local_sendwelcomemsg.git local/sendwelcomemsg
```

Then visit **Site administration → Notifications** (or run `php admin/cli/upgrade.php`) to
complete the installation.

## How it works

1. When a user account is created, an observer adds the user id to the
   `local_sendwelcomemsg_queue` table. Nothing is sent at this point, so a slow or unreachable
   SMTP server can never hold up account creation.
2. Every five minutes the **Send queued welcome messages** scheduled task takes up to 100 queued
   users and, for each of them, sends every enabled template whose target domain matches the
   user's email domain.
3. The queue entry is removed once the run completes without a delivery error. If a send fails,
   the entry is left in place and retried on the next run; after seven days of failures it is
   dropped so the queue cannot grow forever.

Because every matching template is sent, a user can receive more than one message. Use the
target domain field to keep templates from overlapping.

## Configuration

**Site administration → Plugins → Local plugins → Send welcome messages**

The page holds five identical template blocks. Click a template heading to expand it.

| Setting | Description |
| --- | --- |
| Enable template | Whether this template is sent at all. |
| Target email domain | Send only to users in this domain, e.g. `example.com`. Empty means everyone. |
| Sender email / Sender name | The `From` header of the message. |
| Subject | The subject line. |
| Body style | Inline CSS for the wrapper around the body, e.g. `max-width:700px; margin:0 auto;`. |
| Message body | The HTML body, edited with the standard Moodle editor. |
| Attachment | One file attached to every message sent from this template. |
| SMTP host / port / encryption / username / password | The server used for this template. Leave the host empty to hand the message to the web server's own mail transport. |
| Allow untrusted certificates | See the warning below. |

> **Warning**
> **Allow untrusted certificates** turns off TLS certificate verification for that template's
> SMTP connection, which leaves the credentials and the message open to interception. It is off
> by default and should stay off unless the server is on a trusted network and its self-signed
> certificate genuinely cannot be replaced.

### Testing a template

Run the task by hand rather than waiting for cron:

```bash
php admin/cli/scheduled_task.php --execute='\local_sendwelcomemsg\task\send_welcome_emails'
```

The task reports what it did for each queued user and each template, including why a template
was skipped, so this is the place to look when a message does not arrive.

## Privacy

The plugin stores the user id and queue time of every account waiting for its welcome message,
and removes the entry once the message is delivered. That data is exported and deleted through
the Privacy API. Delivering a message also passes the recipient's email address and full name to
the configured SMTP server, which is declared as an external location.

The message bodies and SMTP credentials are plugin settings, not personal data; the SMTP password
is stored the same way as any other Moodle password setting and is masked in the interface.

## Third party code

The plugin uses the PHPMailer library that ships with Moodle core
(`lib/phpmailer`). No third party code is bundled with the plugin itself.

## Bug tracker

Please report issues at
<https://github.com/sgtlomzik/moodle-local_sendwelcomemsg/issues>.

## License

2026 SgtLomzik <lomzike@gmail.com>

This program is free software: you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not,
see <https://www.gnu.org/licenses/>.
