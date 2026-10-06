<?php
/**
 *
 * Contact Admin extension for the phpBB Forum Software package
 *
 * @copyright 2020 Rich McGirr (RMcGirr83)
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\contactadmin\migrations;

/**
* Primary migration
*/

class m3_update_data extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\phpbbmodders\contactadmin\migrations\m2_update_data'];
	}

	public function update_data()
	{
		return [
			['config.add', ['contactadmin_gdpr', false]],
		];
	}
}
