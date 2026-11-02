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
 * Add separate settings for the header and footer navigation links
 *
 * Both start on, so boards that update keep showing both links.
 */
class m6_update_data extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['contactadmin_header_link']);
	}

	public static function depends_on()
	{
		return ['\phpbbmodders\contactadmin\migrations\m5_update_data'];
	}

	public function update_data()
	{
		return [
			['config.add', ['contactadmin_header_link', 1]],
			['config.add', ['contactadmin_footer_link', 1]],
		];
	}
}
