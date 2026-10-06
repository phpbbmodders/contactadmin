# Contact Admin

[![Tests](https://github.com/phpbbmodders/contactadmin/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/contactadmin/actions/workflows/tests.yml) [![Lint](https://github.com/phpbbmodders/contactadmin/actions/workflows/lint.yml/badge.svg)](https://github.com/phpbbmodders/contactadmin/actions/workflows/lint.yml)

A contact form that lets guests and members reach the board administrators by email, private message or forum post.

## Features

- Replaces phpBB's built-in contact page and links to it from the header, the footer, failed logins and the registration page.
- Messages go out by email, as a private message, or as a post in a forum you choose. Send to all administrators, the board founder or the board's default email address.
- Optional list of contact reasons for the sender to pick from.
- Private messages and posts can come from a "contact bot" user, for guests only or for everyone.
- Optional CAPTCHA (for everyone or guests only, with an attempt limit), attachments, a privacy-policy checkbox, and checks that stop guests from using a registered member's username or email address.
- Settings under **ACP → Extensions → Contact Admin**.
- Works with [Stop Forum Spam](https://github.com/phpbbmodders/stopforumspam), which can check messages sent through the form.

## Requirements

- phpBB 3.3.19 or later
- PHP 7.4 or later

## Installation

1. Copy the extension to `/ext/phpbbmodders/contactadmin`
2. In the Administration Control Panel, go to **Customise → Manage extensions**
3. Enable the **Contact Admin** extension
4. Choose the settings under **ACP → Extensions → Contact Admin**

### Upgrading from `rmcgirr83/contactadmin`

This extension used to be installed as `rmcgirr83/contactadmin`. Your settings carry over:

1. Disable the old **Contact Admin** extension in the ACP. Do **not** delete its data.
2. Upload this version to `/ext/phpbbmodders/contactadmin` and enable it. The old install's migration history and ACP module are moved to the new name automatically.
3. Delete the `/ext/rmcgirr83/contactadmin` folder and purge the board cache.

If you use [Stop Forum Spam](https://github.com/phpbbmodders/stopforumspam), update it as well so it keeps checking the contact form.

If you disable the old extension from the command line (`bin/phpbbcli.php`) instead of the ACP, run `bin/phpbbcli.php cache:purge` before enabling the new one; the command-line disable doesn't clear the cache.

## Contributing

Contributions are welcome!

- **Bug reports**: [Open an issue](https://github.com/phpbbmodders/contactadmin/issues).
- **Everything else** (questions, feature requests, ideas, general discussion): [Use Discussions](https://github.com/orgs/phpbbmodders/discussions), or the [community forum](https://www.phpbbmodders.com/community/).
- Pull requests are welcome for bug fixes or discussed features.

## Acknowledgments

- A port of the phpBB 3.0 **Contact Board Administration** MOD.
- Extension by Rich McGirr ([RMcGirr83](https://github.com/rmcgirr83)).
- Code review, bug fixes, and documentation assisted by [Claude](https://www.anthropic.com/claude).

## License

This extension is licensed under the **GNU General Public License v2.0**.

See [license.txt](license.txt) for more information.
