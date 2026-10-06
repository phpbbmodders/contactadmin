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

namespace phpbbmodders\contactadmin\acp;

class contactadmin_module
{
	public	$u_action;

	function main($id, $mode)
	{
		global $phpbb_container;

		$this->tpl_name = 'acp_contactadmin';
		$this->page_title = $phpbb_container->get('language')->lang('ACP_CAT_CONTACTADMIN');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('phpbbmodders.contactadmin.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		$admin_controller->display_options();
	}
}
