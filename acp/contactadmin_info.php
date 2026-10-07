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

class contactadmin_info
{
	public function module()
	{
		return [
			'filename'	=> '\phpbbmodders\contactadmin\acp\contactadmin_module',
			'title'		=> 'ACP_CAT_CONTACTADMIN',
			'version'	=> '1.0.0',
			'modes'	=> [
				'configuration'	=> ['title' => 'ACP_CONTACTADMIN_CONFIG', 'auth' => 'ext_phpbbmodders/contactadmin && acl_a_board', 'cat' => ['ACP_CAT_CONTACTADMIN']],
			],
		];
	}
}
