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
namespace phpbbmodders\contactadmin\core;

use phpbb\auth\auth;
use phpbb\cache\service as cache;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as db;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\request\request;
use phpbb\user;
use phpbb\exception\http_exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
* Shared helpers for the contact form and its ACP settings
*/
class contactadmin
{
	/** @var auth */
	protected $auth;

	/** @var cache */
	protected $cache;

	/** @var config */
	protected $config;

	/** @var db */
	protected $db;

	/** @var language */
	protected $language;

	/** @var log */
	protected $log;

	/** @var request */
	protected $request;

	/** @var user */
	protected $user;

	/** @var string root_path */
	protected $root_path;

	/** @var string php_ext */
	protected $php_ext;

	/** @var array contact_constants */
	protected $contact_constants;

	/**
	* Constructor
	*
	* @param auth						$auth					Auth object
	* @param cache						$cache					Cache object
	* @param config 					$config					Config object
	* @param db							$db						Database object
	* @param language					$language				Language object
	* @param log						$log					Log object
	* @param request					$request				Request object
	* @param user						$user					User object
	* @param string						$root_path				phpBB root path
	* @param string						$php_ext				phpEx
	* @param array						$contact_constants		constants for the extension
	* @access public
	*/
	public function __construct(
			auth $auth,
			cache $cache,
			config $config,
			db $db,
			language $language,
			log $log,
			request $request,
			user $user,
			string $root_path,
			string $php_ext,
			array $contact_constants)
	{
		$this->auth = $auth;
		$this->cache = $cache;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->request = $request;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->contact_constants = $contact_constants;
	}

	/**
	* contact_change_auth
	* thanks to poppertom69 for the idea..and some of the code
	* @param $bot_id	the user id of the contact bot chosen in the ACP
	* @param $mode		the mode either replace or restore
	* @param $bkup_data	an array of the current users data
	* changes the user in posting to that of the bot chosen in the ACP
	*/
	public function contact_change_auth($bot_id, $mode = 'replace', $bkup_data = false)
	{
		switch ($mode)
		{
			// change our user to one we chose in the ACP settings
			// for posting or PM'ing only
			case 'replace':

				$bkup_data = [
					'user_backup'	=> $this->user->data,
				];

				// sql to get the bots info
				$sql = 'SELECT *
					FROM ' . USERS_TABLE . '
					WHERE user_id = ' . (int) $bot_id;
				$result	= $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// reset the current users info to that of the bot
				// we do this instead of just using the sql query
				// for items such as $this->user->data['is_registered'] which isn't a table column from the users table
				$this->user->data = array_merge($this->user->data, $row);

				unset($row);

				return $bkup_data;

			break;

			// now we restore the users stuff
			case 'restore':

				$this->user->data = $bkup_data['user_backup'];

				unset($bkup_data);
			break;
		}
	}

	/**
	* contact_check
	* @param string		$mode		what we are checking
	* @param int		$forum_id	the forum id selected in ACP
	* @param int		$bot_id		the id of the bot selected in the ACP
	* @param string		$method		the type of contact we are using email, forum post or PM
	* ensures postable forum and correct "bot"
	*/
	public function contact_check($mode, $forum_id = false, $bot_id = false, $method = false)
	{
		// the servers url
		$server_url = generate_board_url();

		switch ($mode)
		{
			// check for a valid forum
			case 'contact_check_forum':

				$sql = 'SELECT forum_name
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id . '
				AND forum_type = ' . FORUM_POST;
				$result = $this->db->sql_query($sql);

				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// we didn't get a result
				// send an email if board enabled
				if (!$row && $this->config['email_enable'])
				{

					// send an email to the board default
					$email_template = '@phpbbmodders_contactadmin/contact_error';
					$email_message = $this->language->lang('CONTACT_BOT_MESSAGE', $this->user->data['username'], $this->config['sitename'], $this->language->lang('CONTACT_FORUM'), $server_url);
					$this->contact_send_email($email_template, $email_message);

					// add an entry into the error log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_FORUM_INVALID',  time(), [$forum_id, $row]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_ERROR') . '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">', '</a>');

					throw new http_exception(503, $message);
				}
				else if (!$row)
				{
					// add an entry into the error log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_FORUM_INVALID',  time(), [$forum_id, $row]);

					$message = $this->language->lang('CONTACT_DISABLED', '<a href="mailto:' . $this->config['board_contact'] . '">', '</a>');

					throw new http_exception(503, $message);
				}

			break;

			// check for a valid bot
			case 'contact_check_bot':

				$sql = 'SELECT username
					FROM ' . USERS_TABLE . '
						WHERE user_id = ' . (int) $bot_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				// we didn't get a result
				// send an email if board enabled
				if (!$row && $this->config['email_enable'])
				{
					// send an email to the board default
					$email_template = '@phpbbmodders_contactadmin/contact_error';
					$email_message = $this->language->lang('CONTACT_BOT_MESSAGE', $this->user->data['username'], $this->config['sitename'], $this->language->lang('USER'), $server_url);
					$this->contact_send_email($email_template, $email_message);

					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_BOT_INVALID',  time(), [$bot_id, $row]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_ERROR') . '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">', '</a>');

					throw new http_exception(503, $message);
				}
				else if (!$row)
				{
					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_BOT_INVALID',  time(), [$bot_id, $row]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_DISABLED', '<a href="mailto:' . $this->config['board_contact'] . '">', '</a>');

					throw new http_exception(503, $message);
				}
			break;

			case 'contact_nobody':

				//this is only called if there are no contact admins available
				// for pm'ing or for emailing to per the preferences set by the admin user in their profiles
				if ($method == $this->contact_constants['CONTACT_METHOD_EMAIL'])
				{
					$error = $this->language->lang('EMAIL');
				}
				else
				{
					$error = $this->language->lang('PRIVATE_MESSAGE');
				}

				// only send an email if the board allows it
				if ($this->config['email_enable'])
				{
					// send an email to the board default
					$email_template = '@phpbbmodders_contactadmin/contact_error';
					$email_message = $this->language->lang('CONTACT_NONE', $this->user->data['username'], $this->config['sitename'], $error, $server_url);

					$this->contact_send_email($email_template, $email_message);

					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_NONE',  time(), [$error]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_ERROR') . '<br /><br />' . $this->language->lang('RETURN_INDEX', '<a href="' . append_sid("{$this->root_path}index.$this->php_ext") . '">', '</a>');

					throw new http_exception(503, $message);
				}
				else
				{
					// add an entry into the log error table
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONTACT_NONE',  time(), [$error]);

					// show a message to the user
					$message = $this->language->lang('CONTACT_DISABLED', '<a href="mailto:' . $this->config['board_contact'] . '">', '</a>');

					throw new http_exception(503, $message);
				}
			break;
		}
		return;
	}

	/**
	 * contact_send_email
	 * @param $email_template	the email template to use
	 * @param $email_message	the message we are sending
	 * sends an email to the board default if an error occurs
	 */
	private function contact_send_email($email_template, $email_message)
	{
		$dir_array = $this->dir_to_array($this->root_path .'ext/phpbbmodders/contactadmin/language');

		$lang = (in_array($this->config['default_lang'], $dir_array)) ? $this->config['default_lang'] : 'en';

		if (!class_exists('messenger'))
		{
			include($this->root_path . 'includes/functions_messenger.' . $this->php_ext);
		}
		// don't use the queue send the email immediately if not sooner
		$messenger = new \messenger(false);

		// Email headers
		$messenger->headers('X-AntiAbuse: Board servername - ' . $this->config['server_name']);
		$messenger->headers('X-AntiAbuse: User_id - ' . $this->user->data['user_id']);
		$messenger->headers('X-AntiAbuse: Username - ' . $this->user->data['username']);
		$messenger->headers('X-AntiAbuse: User IP - ' . $this->user->ip);

		$messenger->template($email_template, $lang);
		// No from() call: the messenger sends from the board email address by default
		$messenger->to($this->config['board_email']);

		$messenger->assign_vars([
			'SUBJECT'		=> $this->language->lang('CONTACT_BOT_SUBJECT'),
			'EMAIL_SIG'  	=> $this->config['board_email_sig'],
			'MESSAGE'		=> $email_message,
		]);

		$messenger->send(NOTIFY_EMAIL);

		return;
	}

	/**
	 * contact_reason_options
	 *
	 * @param array 	$input_ary	an array of contact reasons
	 * @param string	$selected	the chosen reason
	 * @return array 				Options for the reasons drop down, empty if there are no reasons
	 */
	public function contact_reason_options($input_ary, $selected)
	{
		// only accept arrays, no empty ones
		if (!is_array($input_ary) || !count($input_ary))
		{
			return [];
		}

		// add a default entry asking for user to choose a reason
		$input_ary = array_merge([$this->language->lang('REASON_EXPLAIN')], $input_ary);

		$options = [];
		foreach ($input_ary as $item)
		{
			$options[] = [
				'VALUE'			=> $item,
				'S_SELECTED'	=> ($item == $selected),
			];
		}

		return $options;
	}

	/**
	 * Create the selection for who gets the message
	 *
	 * @param int		$value	the selected option
	 * @param string	$key	config key passed on to h_radio()
	 * @return string			Radio buttons html, built by phpBB's h_radio()
	 */
	public function who_select($value, $key = '')
	{
		$radio_ary = [
			$this->contact_constants['CONTACT_WHO_ALL_ADMINS']		=> 'CONTACT_WHO_ALL_ADMINS',
			$this->contact_constants['CONTACT_WHO_BOARD_DEFAULT']	=> 'CONTACT_WHO_BOARD_DEFAULT',
			$this->contact_constants['CONTACT_WHO_BOARD_FOUNDER']		=> 'CONTACT_WHO_BOARD_FOUNDER',
		];

		return h_radio('contact_who', $radio_ary, $value, $key);
	}

	/**
	 * Create the selection for the contact method
	 *
	 * @param int		$value	the selected option
	 * @param string	$key	config key passed on to h_radio()
	 * @return string			Radio buttons html, built by phpBB's h_radio()
	 */
	public function method_select($value, $key = '')
	{
		if ($this->config['email_enable'])
		{
			$radio_ary = [
				$this->contact_constants['CONTACT_METHOD_EMAIL']	=> 'CONTACT_METHOD_EMAIL',
				$this->contact_constants['CONTACT_METHOD_POST']		=> 'CONTACT_METHOD_POST',
				$this->contact_constants['CONTACT_METHOD_PM']		=> 'CONTACT_METHOD_PM',
			];
		}
		else
		{
			$radio_ary = [
				$this->contact_constants['CONTACT_METHOD_POST']	=> 'CONTACT_METHOD_POST',
				$this->contact_constants['CONTACT_METHOD_PM']	=> 'CONTACT_METHOD_PM',
			];
		}

		return h_radio('contact_method', $radio_ary, $value, $key);
	}

	/**
	 * Create the selection for the post method
	 *
	 * @param int		$value	the selected option
	 * @param string	$key	config key passed on to h_radio()
	 * @return string			Radio buttons html, built by phpBB's h_radio()
	 */
	public function poster_select($value, $key = '')
	{
		$radio_ary = [
			$this->contact_constants['CONTACT_POST_NEITHER']	=> 'CONTACT_POST_NEITHER',
			$this->contact_constants['CONTACT_POST_GUEST']		=> 'CONTACT_POST_GUEST',
			$this->contact_constants['CONTACT_POST_ALL']		=> 'CONTACT_POST_ALL',
		];

		return h_radio('contact_bot_poster', $radio_ary, $value, $key);
	}

	/**
	 * Get the forums for the bot forum drop down
	 *
	 * @param int	$value	the selected forum id
	 * @return array		Forum data keyed by forum id, from make_forum_select()
	 */
	public function forum_options($value)
	{
		return make_forum_select((int) $value, false, true, true, true, false, true);
	}

	/**
	* get an array of admins
	*/
	public function admin_array()
	{
		$sql_where = '';
		$contact_users = [];

		// board default email
		if ($this->config['contactadmin_who'] == $this->contact_constants['CONTACT_WHO_BOARD_DEFAULT'])
		{
			$contact_users[] = [
				'username'		=> !empty($this->config['board_contact_name']) ? $this->config['board_contact_name'] : $this->config['sitename'],
				'user_email'	=> $this->config['board_contact'],
				'user_jabber'	=> '',
				'user_lang'		=> $this->config['default_lang'],
				'user_notify_type'	=> 0,
				'user_dateformat'	=> $this->config['default_dateformat'],
				'user_timezone'	=> $this->config['board_timezone']
			];

			return $contact_users;
		}
		else if ($this->config['contactadmin_who'] == $this->contact_constants['CONTACT_WHO_BOARD_FOUNDER'])
		{
			$sql_where .= ' WHERE user_type = ' . USER_FOUNDER;
		}
		else
		{
			// Grab an array of user_id's with admin permissions
			$admin_ary = $this->auth->acl_get_list(false, 'a_', false);
			$admin_ary = (!empty($admin_ary[0]['a_'])) ? $admin_ary[0]['a_'] : [];

			if (in_array($this->config['contactadmin_method'], [$this->contact_constants['CONTACT_METHOD_EMAIL'], $this->contact_constants['CONTACT_METHOD_PM']]) && count($admin_ary))
			{
				$sql_where .= ' WHERE ' . $this->db->sql_in_set('user_id', $admin_ary);
			}
		}

		if (!empty($sql_where))
		{
			$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type, user_dateformat, user_timezone
				FROM ' . USERS_TABLE . ' ' .
				$sql_where;
			$result = $this->db->sql_query($sql);
			$contact_users = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
		}

		// we didn't get a soul
		if (!count($contact_users))
		{
			// we have no one to send anything to
			// notify the board default
			$this->contact_check('contact_nobody', false, false, (int) $this->config['contactadmin_method']);
		}

		return $contact_users;
	}

	/**
	* bot_user_info					look up the user chosen as the contact "bot"
	*
	* @param int	$user_id		user id
	* @return array					user_id, username and user_type, or an 'error' message if not found
	* @access public
	*/
	public function bot_user_info($user_id)
	{
		$sql = 'SELECT user_id, username, user_type
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$bot_user_info = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$bot_user_info)
		{
			$bot_user_info = ['error' => $this->language->lang('CONTACT_NO_BOT_USER')];
		}

		return $bot_user_info;
	}

	/**
	* bot_user_info_json			AJAX lookup used by the ACP settings when choosing the "bot"
	*
	* Only board administrators may use it: it reveals usernames and account types.
	*
	* @param int	$user_id		user id
	* @return JsonResponse			the user's profile link, plus an error key if the user is unsuitable
	* @throws http_exception		403 for anyone without the a_board permission
	* @access public
	*/
	public function bot_user_info_json($user_id)
	{
		if (!$this->auth->acl_get('a_board'))
		{
			throw new http_exception(403, 'NOT_AUTHORISED');
		}

		$bot_user_info = $this->bot_user_info($user_id);

		if (isset($bot_user_info['error']))
		{
			return new JsonResponse([
				'error'		=> 'CONTACT_NO_BOT_USER',
				'user_link'	=> '',
			]);
		}
		else if ($bot_user_info['user_id'] == ANONYMOUS)
		{
			return new JsonResponse([
				'error'		=> 'CONTACT_BOT_IS_GUEST',
				'user_link'	=> $bot_user_info['username'],
			]);
		}
		else if ($bot_user_info['user_type'] == USER_IGNORE)
		{
			return new JsonResponse([
				'error'		=> 'CONTACT_BOT_IS_BOT',
				'user_link'	=> $bot_user_info['username'],
			]);
		}

		return new JsonResponse([
			'error'		=> false,
			'user_link'	=> '<a href="' . append_sid("{$this->root_path}memberlist.$this->php_ext", 'mode=viewprofile&amp;u=' . $bot_user_info['user_id']) . '" target="_blank">' . $bot_user_info['username'] . '</a>',
		]);
	}

	/**
	* Get the names of the sub-directories of a directory
	*
	* @param string	$directory	path of the directory
	* @return array				directory names
	*/
	public function dir_to_array($directory)
	{
		$directories = glob($directory . '/*' , GLOB_ONLYDIR);
		$dir_array = [];
		foreach ($directories as $key => $value)
		{
			$dir_array[] = substr(strrchr($value, '/'), 1);
		}
		return $dir_array;
	}
}
