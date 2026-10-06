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

class m1_update_data extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbbmodders\contactadmin\migrations\version_100');
	}

	public function update_data()
	{
		return array(
			// Update config entry
			array('config.update', array('contactadmin_enable', true)),
			array('config.update', array('contactadmin_confirm', true)),
			array('config.update', array('contact_admin_form_enable', false)),
		);
	}
}
