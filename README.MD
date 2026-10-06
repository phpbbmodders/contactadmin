# ![Contact Admin](https://imattpro.github.io/logo/search.png "Contact Admin") Contact Admin for phpBB3

phpBB extension that allows guests and/or registered users to either send an email to admins or to either send a PM or make a Post in a designated forum. It also overrides the default contact admin link found on the forum.  A port of the phpBB 3.0.x mod “Contact Board Administration”

Requires phpBB 3.3.19 or higher and PHP 7.4 or higher.

[![Build Status](https://github.com/phpbbmodders/contactadmin/workflows/Tests/badge.svg)](https://github.com/phpbbmodders/contactadmin/actions)

## Installation

### 1. clone
Clone (or download and move) the repository into the folder ext/phpbbmodders/contactadmin:

```
cd phpBB3
git clone https://github.com/phpbbmodders/contactadmin.git ext/phpbbmodders/contactadmin/
```

### 2. activate
Go to admin panel -> tab customise -> Manage extensions -> enable Contact Admin

Within the Admin panel visit the Extensions tab and choose the settings for the extension.

## Upgrading from rmcgirr83/contactadmin
This extension used to be installed as `rmcgirr83/contactadmin`. Your settings and ACP module carry over:

1. Go to your phpBB-Board > Admin Control Panel > Customise > Manage extensions > Contact Admin: disable (do **not** delete its data)
2. Upload the new files to ext/phpbbmodders/contactadmin
3. Go to your phpBB-Board > Admin Control Panel > Customise > Manage extensions > Contact Admin: enable
4. Delete the ext/rmcgirr83/contactadmin folder
5. Purge the board cache

If you use [Stop Forum Spam](https://github.com/phpbbmodders/stopforumspam), update it as well so its contact form checks keep working.

## Update instructions:
1. Go to you phpBB-Board > Admin Control Panel > Customise > Manage extensions > Contact Admin: disable
2. Delete all files of the extension from ext/phpbbmodders/contactadmin
3. Upload all the new files to the same locations
4. Go to you phpBB-Board > Admin Control Panel > Customise > Manage extensions > Contact Admin: enable
