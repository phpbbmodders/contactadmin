<?php
/**
 *
 * Contact Admin extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\contactadmin\migrations;

/**
 * Add the contact page information text
 *
 * The ACP settings page reads these rows, but no earlier migration added
 * them, so they only existed once the settings had been saved. Boards that
 * already have them keep their text (config_text.add skips existing rows).
 */
class m5_update_data extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\phpbbmodders\contactadmin\migrations\m4_update_data'];
	}

	public function update_data()
	{
		return [
			['config_text.add', ['contact_admin_info', '']],
			['config_text.add', ['contact_admin_info_uid', '']],
			['config_text.add', ['contact_admin_info_bitfield', '']],
			// BBCode, smilies and links on, as for a new post
			['config_text.add', ['contact_admin_info_flags', OPTION_FLAG_BBCODE | OPTION_FLAG_SMILIES | OPTION_FLAG_LINKS]],
		];
	}

	public function revert_data()
	{
		return [
			['config_text.remove', ['contact_admin_info']],
			['config_text.remove', ['contact_admin_info_uid']],
			['config_text.remove', ['contact_admin_info_bitfield']],
			['config_text.remove', ['contact_admin_info_flags']],
		];
	}
}
