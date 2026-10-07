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

namespace phpbbmodders\contactadmin\controller;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\config\db_text;
use phpbb\db\driver\driver_interface as db;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use phpbbmodders\contactadmin\core\contactadmin as contactadmin;

/**
* ACP settings page for the contact form
*/
class admin_controller
{
	/** @var auth */
	protected $auth;

	/** @var config */
	protected $config;

	/** @var db_text */
	protected $db_text;

	/** @var db */
	protected $db;

	/** @var helper */
	protected $helper;

	/** @var language */
	protected $language;

	/** @var log */
	protected $log;

	/** @var request */
	protected $request;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/** @var contactadmin */
	protected $contactadmin;

	/** @var string root_path */
	protected $root_path;

	/** @var string php_ext */
	protected $php_ext;

	/** @var array contact_constants */
	protected $contact_constants;

	/** @var string Custom form action */
	protected $u_action;

	/**
	* Constructor
	*
	* @param auth						$auth				Auth object
	* @param config						$config				Config object
	* @param db_text 					$db_text			Config text object
	* @param db							$db					Database object
	* @param helper						$helper				Controller helper object
	* @param language					$language			Language object
	* @param log						$log				Log object
	* @param request					$request			Request object
	* @param template					$template			Template object
	* @param user						$user				User object
	* @param contactadmin				$contactadmin		Methods for the extension
	* @param string						$root_path			phpBB root path
	* @param string						$php_ext			phpEx
	* @param array						$contact_constants	constants for the extension
	* @access public
	*/
	public function __construct(
			auth $auth,
			config $config,
			db_text $db_text,
			db $db,
			helper $helper,
			language $language,
			log $log,
			request $request,
			template $template,
			user $user,
			contactadmin $contactadmin,
			string $root_path,
			string $php_ext,
			array $contact_constants)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db_text = $db_text;
		$this->db = $db;
		$this->helper = $helper;
		$this->language = $language;
		$this->log = $log;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->contactadmin = $contactadmin;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->contact_constants = $contact_constants;
	}

	/**
	 * Display and save the settings page
	 *
	 * @return void
	 * @access public
	 */
	public function display_options()
	{
		$this->language->add_lang(['acp/board', 'posting']);
		$this->language->add_lang('acp_contact', 'phpbbmodders/contactadmin');

		// Create a form key for preventing CSRF attacks
		add_form_key('contactadmin_settings');

		$contact_admin_data		= $this->db_text->get_array([
			'contactadmin_reasons',
			'contact_admin_info',
			'contact_admin_info_uid',
			'contact_admin_info_bitfield',
			'contact_admin_info_flags',
		]);

		$contact_admin_reasons			= $contact_admin_data['contactadmin_reasons'];
		$contact_admin_info				= $contact_admin_data['contact_admin_info'];
		$contact_admin_info_uid			= $contact_admin_data['contact_admin_info_uid'];
		$contact_admin_info_bitfield	= $contact_admin_data['contact_admin_info_bitfield'];
		$contact_admin_info_flags		= $contact_admin_data['contact_admin_info_flags'];

		$contact_method_email = $contact_method_pm = $contact_method_post = '';
		foreach ($this->contact_constants as $key => $value)
		{
			if ($key == 'CONTACT_METHOD_EMAIL')
			{
				$contact_method_email = $value;
			}
			else if ($key == 'CONTACT_METHOD_PM')
			{
				$contact_method_pm = $value;
			}
			else if ($key == 'CONTACT_METHOD_POST')
			{
				$contact_method_post = $value;
			}
		}

		$contact_admin_info = $this->request->variable('contact_admin_info', $contact_admin_info, true);

		$bot_max_id = (int) $this->bot_max_id();

		$bot_info = $this->contactadmin->bot_user_info($this->request->variable('contact_bot_user', (int) $this->config['contactadmin_bot_user']));

		if (isset($bot_info['error']))
		{
			$user_link = $bot_info['error'];
		}
		else
		{
			$user_link = '<a href="' . append_sid("{$this->root_path}memberlist.$this->php_ext", 'mode=viewprofile&amp;u=' . $bot_info['user_id']) . '" target="_blank">' . $bot_info['username'] . '</a>';
		}

		$error = [];
		if ($this->request->is_set_post('submit')  || $this->request->is_set_post('preview'))
		{
			if (!check_form_key('contactadmin_settings'))
			{
				$error[] = $this->language->lang('FORM_INVALID');
			}

			if (($this->request->variable('contact_method', 0) == $contact_method_email) && !$this->config['email_enable'])
			{
				$error[] = $this->language->lang('EMAIL_NOT_CONFIGURED');
			}

			if (in_array($this->request->variable('contact_method', 0), [$contact_method_email, $contact_method_pm]))
			{
				$admins_exist = $this->check_for_admins();

				if (!$admins_exist)
				{
					$error[] = $this->language->lang(($this->request->variable('contact_method', 0) == $contact_method_email) ? 'ADMINS_NOT_EXIST_EMAIL' : 'ADMINS_NOT_EXIST_PM');
				}
			}

			if (isset($bot_info['error']))
			{
				$error[] = $bot_info['error'];
			}

			$contact_admin_reasons	= $this->request->variable('reasons', '', true);

			generate_text_for_storage(
				$contact_admin_info,
				$contact_admin_info_uid,
				$contact_admin_info_bitfield,
				$contact_admin_info_flags,
				!$this->request->variable('disable_bbcode', false),
				!$this->request->variable('disable_magic_url', false),
				!$this->request->variable('disable_smilies', false)
			);

			if (empty($error) && $this->request->is_set_post('submit'))
			{
				$this->db_text->set_array([
					'contactadmin_reasons'			=> $contact_admin_reasons,
					'contact_admin_info'			=> $contact_admin_info,
					'contact_admin_info_uid'		=> $contact_admin_info_uid,
					'contact_admin_info_bitfield'	=> $contact_admin_info_bitfield,
					'contact_admin_info_flags'		=> $contact_admin_info_flags,
				]);

				$this->set_options();

				// and an entry into the log table
				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_CONFIG_UPDATE');

				trigger_error($this->language->lang('CONTACT_CONFIG_SAVED') . adm_back_link($this->u_action));
			}
		}

		$contact_admin_info_preview = '';
		if ($this->request->is_set_post('preview'))
		{
			// The text was prepared for storage above, together with its uid, bitfield and flags
			$contact_admin_info_preview = generate_text_for_display($contact_admin_info, $contact_admin_info_uid, $contact_admin_info_bitfield, $contact_admin_info_flags);
		}

		$contact_admin_edit = generate_text_for_edit($contact_admin_info, $contact_admin_info_uid, $contact_admin_info_flags);

		$this->template->assign_vars([
			'CONTACT_ERROR'					=> (count($error)) ? implode('<br />', $error) : false,

			'CONTACT_CONFIRM'				=> $this->config['contactadmin_confirm'],
			'CONTACT_CONFIRM_GUESTS'		=> $this->config['contactadmin_confirm_guests'],
			'CONTACT_ATTACHMENTS'			=> $this->config['allow_attachments'] ? $this->config['contactadmin_attach_allowed'] : $this->config['allow_attachments'],
			'CONTACT_MAX_ATTEMPTS'			=> $this->config['contactadmin_max_attempts'],
			'CONTACT_USERNAME_CHK'			=> $this->config['contactadmin_username_chk'],
			'CONTACT_EMAIL_CHK'				=> $this->config['contactadmin_email_chk'],
			'CONTACT_GDPR'					=> $this->config['contactadmin_gdpr'],
			'CONTACT_REASONS'				=> $contact_admin_reasons,
			'CONTACT_METHOD'				=> $this->contactadmin->method_select($this->request->variable('contact_method', $this->config['contactadmin_method'])),
			'CONTACT_WHO'					=> $this->contactadmin->who_select($this->config['contactadmin_who']),
			'CONTACT_METHOD_EMAIL'			=> (int) $contact_method_email,
			'CONTACT_METHOD_PM'				=> (int) $contact_method_pm,
			'CONTACT_METHOD_POST'			=> (int) $contact_method_post,
			'CONTACT_BOT_POSTER'			=> $this->contactadmin->poster_select($this->config['contactadmin_bot_poster']),

			'CONTACT_BOT_MAX_ID'			=> $bot_max_id,
			'CONTACT_BOT_USER'				=> $this->request->variable('contact_bot_user', $this->config['contactadmin_bot_user']),
			'CONTACT_BOT_USER_LINK'			=> $user_link,

			'CONTACT_INFO'					=> $contact_admin_edit['text'],
			'CONTACT_INFO_PREVIEW'			=> $contact_admin_info_preview,

			'L_CONTACT_METHOD_EXPLAIN'		=> $this->config['email_enable'] ? $this->language->lang('CONTACT_METHOD_EXPLAIN') : $this->language->lang('FORUM_EMAIL_INACTIVE'),
			'L_CONTACT_ATTACHMENTS_EXPLAIN'	=> $this->config['allow_attachments'] ? $this->language->lang('CONTACT_ATTACHMENTS_EXPLAIN') : $this->language->lang('NO_FORUM_ATTACHMENTS'),

			'BBCODE_STATUS'			=> $this->language->lang('BBCODE_IS_ON', '<a href="' . append_sid("{$this->root_path}faq.$this->php_ext", 'mode=bbcode') . '">', '</a>'),
			'SMILIES_STATUS'		=> $this->language->lang('SMILIES_ARE_ON'),
			'IMG_STATUS'			=> $this->language->lang('IMAGES_ARE_ON'),
			'FLASH_STATUS'			=> $this->language->lang('FLASH_IS_ON'),
			'URL_STATUS'			=> $this->language->lang('URL_IS_ON'),

			'S_BBCODE_DISABLE_CHECKED'		=> !$contact_admin_edit['allow_bbcode'],
			'S_SMILIES_DISABLE_CHECKED'		=> !$contact_admin_edit['allow_smilies'],
			'S_MAGIC_URL_DISABLE_CHECKED'	=> !$contact_admin_edit['allow_urls'],
			'S_BBCODE_ALLOWED'		=> true,
			'S_SMILIES_ALLOWED'		=> true,
			'S_BBCODE_IMG'			=> true,
			'S_BBCODE_FLASH'		=> true,
			'S_LINKS_ALLOWED'		=> true,

			'AJAX_BOT_USER_INFO'	=> $this->helper->route('phpbbmodders_contactadmin_botuserinfo', ['user_id' => (int) $this->config['contactadmin_bot_user']]),

			'U_ACTION'				=> $this->u_action,
		]);

		foreach ($this->contactadmin->forum_options($this->config['contactadmin_forum']) as $forum_id => $forum)
		{
			$this->template->assign_block_vars('contact_forums', [
				'FORUM_ID'		=> $forum_id,
				'FORUM_NAME'	=> $forum['padding'] . $forum['forum_name'],
				'S_SELECTED'	=> !empty($forum['selected']),
				'S_DISABLED'	=> !empty($forum['disabled']),
			]);
		}

		if (!function_exists('display_custom_bbcodes'))
		{
			include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		}
		// Assigning custom bbcodes
		display_custom_bbcodes();
	}

	/**
	 * Save the submitted settings
	 *
	 * Choices that must be one of a fixed set fall back to their first option
	 * if anything else is submitted.
	 *
	 * @return void
	 * @access protected
	 */
	protected function set_options()
	{
		$c = $this->contact_constants;

		$this->config->set('contactadmin_confirm', $this->request->variable('confirm', 0));
		$this->config->set('contactadmin_confirm_guests', $this->request->variable('confirm_guests', 0));
		$this->config->set('contactadmin_username_chk', $this->request->variable('username_chk', 0));
		$this->config->set('contactadmin_email_chk', $this->request->variable('email_chk', 0));
		$this->config->set('contactadmin_max_attempts', max(0, $this->request->variable('max_attempts', 0)));
		$this->config->set('contactadmin_attach_allowed', $this->request->variable('attach_allowed', 0));
		$this->config->set('contactadmin_who', $this->allowed_value($this->request->variable('contact_who', 0), [$c['CONTACT_WHO_ALL_ADMINS'], $c['CONTACT_WHO_BOARD_DEFAULT'], $c['CONTACT_WHO_BOARD_FOUNDER']]));
		$this->config->set('contactadmin_method', $this->allowed_value($this->request->variable('contact_method', 0), [$c['CONTACT_METHOD_EMAIL'], $c['CONTACT_METHOD_POST'], $c['CONTACT_METHOD_PM']]));
		$this->config->set('contactadmin_bot_user', $this->request->variable('contact_bot_user', 0));
		$this->config->set('contactadmin_bot_poster', $this->allowed_value($this->request->variable('contact_bot_poster', 0), [$c['CONTACT_POST_NEITHER'], $c['CONTACT_POST_GUEST'], $c['CONTACT_POST_ALL']]));
		$this->config->set('contactadmin_forum', $this->request->variable('forum', 0));
		$this->config->set('contactadmin_gdpr', $this->request->variable('gdpr', 0));
	}

	/**
	 * Return a submitted value if it is one of the allowed values, otherwise the first allowed value
	 *
	 * @param int	$value		submitted value
	 * @param array	$allowed	allowed values
	 * @return int
	 * @access protected
	 */
	protected function allowed_value($value, array $allowed)
	{
		return in_array($value, $allowed) ? $value : reset($allowed);
	}

	/**
	* bot_max_id				Used to limit the number allowed for the bot user
	*
	* @return int				The maximum userid on the forum
	* @access protected
	*/
	protected function bot_max_id()
	{
		$sql = 'SELECT MAX(user_id) as max_id
			FROM ' . USERS_TABLE;
		$result = $this->db->sql_query($sql);
		$bot_max_id = $this->db->sql_fetchfield('max_id');
		$this->db->sql_freeresult($result);

		return (int) $bot_max_id;
	}

	/**
	* check_for_admins			ensure the board has at least one administrator to send emails or PMs to
	*
	* @return	bool
	* @access	protected
	*/
	protected function check_for_admins()
	{
		// Grab an array of user_id's with admin permissions
		$admin_ary = $this->auth->acl_get_list(false, 'a_', false);

		return !empty($admin_ary[0]['a_']);
	}

	/**
	 * Set page url
	 *
	 * @param string $u_action Custom form action
	 * @return null
	 * @access public
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
