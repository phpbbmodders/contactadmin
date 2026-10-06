<?php
/**
 *
 * Contact Admin extension for the phpBB Forum Software package
 *
 * @copyright 2016 Rich McGirr (RMcGirr83)
 * @copyright (c) 2026, phpBB Modders, https://www.phpbbmodders.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbmodders\contactadmin\migrations;

/**
* Primary migration
*/

class version_100 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314rc1');
	}

	public function update_data()
	{
		return array(

			// Add config entry
			array('config.add', array('contactadmin_enable', false)),
			array('config.add', array('contactadmin_confirm', false)),
			array('config.add', array('contactadmin_confirm_guests', true)),
			array('config.add', array('contactadmin_max_attempts', 3)),
			array('config.add', array('contactadmin_method', false)),
			array('config.add', array('contactadmin_bot_user', 2)),
			array('config.add', array('contactadmin_bot_poster', false)),
			array('config.add', array('contactadmin_attach_allowed', false)),
			array('config.add', array('contactadmin_username_chk', false)),
			array('config.add', array('contactadmin_mail_chk', false)),
			array('config.add', array('contactadmin_forum', 2)),
			array('config_text.add', array('contactadmin_reasons', '')),
			array('config.add', array('contactadmin_founder_only', false)),
			// disable the default contact admin function
			array('config.update', array('contact_admin_form_enable', false)),

			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_CAT_CONTACTADMIN'
			)),

			array('module.add', array(
				'acp',
				'ACP_CAT_CONTACTADMIN',
				array(
					'module_basename'	=> '\phpbbmodders\contactadmin\acp\contactadmin_module',
					'modes'				=> array('configuration'),
				),
			)),
		);
	}
}
