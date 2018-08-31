<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;

/**
 * Sellacious user plugin
 *
 * @since  1.5
 */
class PlgSystemSellacious extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var    \JApplicationCms
	 *
	 * @since  3.1
	 */
	protected $app;

	/**
	 * @var    \JDatabaseDriver
	 *
	 * @since  3.1
	 */
	protected $db;

	/**
	 * Adds user registration template fields to the sellacious form for creating email templates
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!$form instanceof JForm)
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		if ($form->getName() != 'com_sellacious.emailtemplate')
		{
			return true;
		}

		$contexts = array();

		$this->onFetchEmailContext('com_sellacious.emailtemplate', $contexts);

		if (!empty($contexts))
		{
			$array = is_object($data) ? Joomla\Utilities\ArrayHelper::fromObject($data) : (array) $data;

			if (array_key_exists($array['context'], $contexts))
			{
				if (strpos($array['context'], 'password_reset') !== false)
				{
					$form->loadFile(__DIR__ . '/forms/password_reset.xml', false);
				}
				else
				{
					$form->loadFile(__DIR__ . '/forms/user_activation.xml', false);

					if ($array['context'] == 'user_activation.admin')
					{
						$form->setFieldAttribute('short_codes', 'description', 'PLG_SYSTEM_SELLACIOUS_USER_ACTIVATION_FIELDSET_ADMIN_SHORTCODES_NOTE');
					}
					elseif ($array['context'] == 'user_activation.self')
					{
						$form->setFieldAttribute('short_codes', 'description', 'PLG_SYSTEM_SELLACIOUS_USER_ACTIVATION_FIELDSET_SELF_SHORTCODES_NOTE');
					}
				}
			}
		}

		return true;
	}

	/**
	 * Fetch the available context of email template
	 *
	 * @param   string    $context   The calling context
	 * @param   string[]  $contexts  The list of email context the should be populated
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	public function onFetchEmailContext($context, array &$contexts = array())
	{
		if ($context == 'com_sellacious.emailtemplate')
		{
			$contexts['user_activation.admin'] = JText::_('COM_SELLACIOUS_EMAILTEMPLATE_USER_ACTIVATION_RECIPIENT_ADMIN');
			$contexts['user_activation.self']  = JText::_('COM_SELLACIOUS_EMAILTEMPLATE_USER_ACTIVATION_RECIPIENT_USER');
			$contexts['password_reset.self']   = JText::_('COM_SELLACIOUS_EMAILTEMPLATE_USER_PASSWORD_RESET_RECIPIENT_USER');
		}
	}

	/**
	 * This method sends a reminder email for non-activated users.
	 *
	 * @return  void
	 *
	 * @since   1.3.3
	 */
	public function onAfterRoute()
	{
		// How frequently to run this?
		jimport('sellacious.loader');

		if (class_exists('SellaciousHelper'))
		{
			try
			{
				$this->registerClientApp();

				$this->checkPendingActivation();

				$this->checkMediaDownloadQueue();
			}
			catch (Exception $e)
			{
			}
		}
	}

	/**
	 * This method executes right before the application head is about to be rendered
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	public function onBeforeCompileHead()
	{
		// External item buttons need JS handler, and they can be in any page, not just sellacious
		if (JFactory::getDocument()->getType() == 'html' && $this->app->isClient('site'))
		{
			JHtml::_('script', 'com_sellacious/util.cart.external-item.js', array('version' => S_VERSION_CORE, 'relative' => true));
		}
	}

	/**
	 * Send the email for the given user object using given email template object
	 *
	 * @param   JTable  $template  The template table object
	 * @param   object  $user      The user object
	 *
	 * @return  void
	 *
	 * @since   1.3.3
	 *
	 * @throws  Exception
	 */
	protected function addUserMail($template, $user)
	{
		$base = JUri::getInstance()->toString(array('scheme', 'user', 'pass', 'host', 'port'));
		$link = JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $user->activation);

		$helper      = SellaciousHelper::getInstance();
		$emailParams = $helper->config->getParams('com_sellacious', 'emailtemplate_options');

		$replacements = array(
			'sitename'          => JFactory::getConfig()->get('sitename'),
			'site_url'          => rtrim(JUri::root(), '/'),
			'email_header'      => $emailParams->get('header', ''),
			'email_footer'      => $emailParams->get('footer', ''),
			'activation_link'   => $base . $link,
			'full_name'         => $user->name,
			'email_address'     => $user->email,
			'registration_date' => JHtml::_('date', $user->registerDate, 'F d, Y h:i A T'),
			'days_passed'       => $user->days,
		);

		$recipients = explode(',', $template->get('recipients'));

		if ($template->get('send_actual_recipient'))
		{
			array_unshift($recipients, $user->email);
		}

		$this->queue($template, $replacements, $recipients);
	}

	/**
	 * Send the email to the administrators for the given user objects using given email template object
	 *
	 * @param   JTable    $template  The template table object
	 * @param   object[]  $users     The user object
	 *
	 * @return  void
	 *
	 * @since   1.3.3
	 */
	protected function addAdminMail($template, $users)
	{
		$helper = SellaciousHelper::getInstance();

		// Load recipients
		$recipients = explode(',', $template->get('recipients'));

		if ($template->get('send_actual_recipient'))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			try
			{
				// Super user = 8, as of J3.x
				$groups = (array) $helper->config->get('usergroups_company') ?: array(8);

				$query->select('u.email')->from('#__users u')
					->where('u.block = 0');

				$query->join('inner', '#__user_usergroup_map m ON m.user_id = u.id')
					->where('m.group_id IN (' . implode($groups) . ')');

				$query->group('u.email');

				$admins     = (array) $db->setQuery($query)->loadColumn();
				$recipients = array_merge($admins, $recipients);
			}
			catch (Exception $e)
			{
				// Ignore
			}
		}

		if (count($recipients))
		{
			// Prepare users list
			$list   = array();
			$list[] = '<table style="width: 100%; white-space: nowrap;" border="0">';

			foreach ($users as $user)
			{
				$rDate  = JHtml::_('date', $user->registerDate, 'F d, Y h:i A T');
				$list[] = "<tr><td>$user->name</td><td>$user->email</td><td>$rDate</td><td>$user->days Days</td></tr>";
			}

			$list[] = '</table>';

			$emailParams = $helper->config->getParams('com_sellacious', 'emailtemplate_options');

			$replacements = array(
				'sitename'     => JFactory::getConfig()->get('sitename'),
				'site_url'     => rtrim(JUri::root(), '/'),
				'email_header' => $emailParams->get('header', ''),
				'email_footer' => $emailParams->get('footer', ''),
				'user_list'    => implode($list),
			);

			$this->queue($template, $replacements, $recipients);
		}
	}

	/**
	 * Queue the email in the database using given template and data for the given recipients
	 *
	 * @param   JTable  $template      The template table object
	 * @param   array   $replacements  The short code replacements for the email text
	 * @param   array   $recipients    The recipient emails
	 *
	 * @return  void
	 *
	 * @since   1.3.3
	 */
	protected function queue($template, $replacements, $recipients)
	{
		$recipients = array_filter($recipients);
		$subject    = trim($template->get('subject'));
		$body       = trim($template->get('body'));

		// Check Recipients, subject and body should not empty before adding to Email Queue
		if (empty($recipients) || $subject == '' || $body == '')
		{
			return;
		}

		// Pre instantiate for constant access.
		$table = JTable::getInstance('MailQueue', 'SellaciousTable');

		// All codes are in upper case
		$replacements = array_change_key_case($replacements, CASE_UPPER);

		$data             = new stdClass;
		$data->context    = $template->get('context');
		$data->subject    = $subject;
		$data->body       = $body;
		$data->is_html    = true;
		$data->state      = SellaciousTableMailQueue::STATE_QUEUED;
		$data->recipients = $recipients;
		$data->sender     = $template->get('sender');
		$data->cc         = !empty($template->cc) ? explode(',', $template->cc) : array();
		$data->bcc        = !empty($template->bcc) ? explode(',', $template->bcc) : array();
		$data->replyto    = !empty($template->replyto) ? explode(',', $template->replyto) : array();

		foreach ($replacements as $code => $replacement)
		{
			$data->subject = str_replace('%' . $code . '%', $replacement, $data->subject);
			$data->body    = str_replace('%' . $code . '%', $replacement, $data->body);
		}

		try
		{
			$table->save($data);
		}
		catch (Exception $e)
		{
			// Todo: Handle this
		}
	}

	/**
	 * Get a list of users that have not yet activated their account
	 *
	 * @param   int[]  $days  Number of days since registration
	 *
	 * @return  array
	 *
	 * @since   1.3.3
	 */
	protected function getInactiveUsers($days)
	{
		$users = array();
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('a.id, a.name, a.username, a.email, a.block, a.registerDate, a.activation')
			->from($db->qn('#__users', 'a'))
			->where('a.block = 1')
			->where('a.activation != ' . $db->q(''));

		sort($days);

		foreach ($days as $day)
		{
			// If the job is missed for a whole day, no mails would be sent for that day on another day.
			$then  = JFactory::getDate()->sub(new DateInterval('P' . $day . 'D'));
			$start = $then->format('Y-m-d 00:00:00');
			$end   = $then->format('Y-m-d 23:59:59');

			try
			{
				$sql = clone $query;
				$sql->select($db->q($day) . ' AS days')
					->where(sprintf('a.registerDate BETWEEN %s AND %s', $db->q($start), $db->q($end)));

				$db->setQuery($sql);

				if ($results = $db->loadObjectList())
				{
					foreach ($results as $result)
					{
						$users[$result->id] = $result;
					}
				}
			}
			catch (Exception $e)
			{
				// Ignore
			}
		}

		return array_values($users);
	}

	/**
	 * This method sends a reminder email for non-activated users.
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	protected function checkPendingActivation()
	{
		// If we've already processed today, skip.
		$table = JTable::getInstance('MailQueue', 'SellaciousTable');
		$db    = JFactory::getDbo();
		$now   = JFactory::getDate();
		$query = $db->getQuery(true);

		$query->select('COUNT(1)')->from($db->qn($table->getTableName(), 'a'))
			->where('a.context LIKE ' . $db->q('user_activation.%', false))
			->where('a.created > ' . $db->q($now->format('Y-m-d 00:00:00')));

		try
		{
			if ($db->setQuery($query)->loadResult())
			{
				return;
			}
		}
		catch (Exception $e)
		{
			return;
		}

		// Send to the user
		$table = JTable::getInstance('EmailTemplate', 'SellaciousTable');
		$table->load(array('context' => 'user_activation.self'));

		$params = new Registry($table->get('params'));
		$days   = ArrayHelper::toInteger(preg_split('/[^\d]+/', $params->get('days')));
		$days   = array_filter($days);

		if ($table->get('state'))
		{
			$users = $this->getInactiveUsers($days);

			foreach ($users as $user)
			{
				$this->addUserMail($table, $user);
			}
		}

		// Send to administrators
		$table = JTable::getInstance('EmailTemplate', 'SellaciousTable');
		$table->load(array('context' => 'user_activation.admin'));

		$params = new Registry($table->get('params'));
		$days   = ArrayHelper::toInteger(preg_split('/[^\d]+/', $params->get('days')));
		$days   = array_filter($days);

		if ($table->get('state'))
		{
			$users = $this->getInactiveUsers($days);

			if ($users)
			{
				$this->addAdminMail($table, $users);
			}
		}
	}

	/**
	 * This method checks.
	 *
	 * @return  void
	 *
	 * @since   1.5.1
	 *
	 * @throws  Exception
	 */
	protected function checkMediaDownloadQueue()
	{
		if ($this->app->input->get('option') == '' && $this->app->input->get('op') == 'sellacious.downloadQueue')
		{
			// ignore_user_abort(true);

			$helper   = SellaciousHelper::getInstance();
			$filter   = array(
				'list.select' => 'a.id, a.path, a.params',
				'list.where'  => 'a.params LIKE ' . $this->db->q('%"remote_download":%', false),
				'state'       => -1,
			);
			$iterator = $helper->media->getIterator($filter);

			foreach ($iterator as $item)
			{
				$params = new Registry($item->params);

				if ($params->get('remote_download') && $params->get('download_url'))
				{
					try
					{
						set_time_limit(60);

						$response = JHttpFactory::getHttp()->get($params->get('download_url'), null, '30');

						if ($response->code == 200 && strlen($response->body))
						{
							jimport('joomla.filesystem.folder');

							$filename = JPATH_SITE . '/' . $item->path;

							JFolder::create(dirname($filename));
							file_put_contents($filename, $response->body);

							$item->state = 1;
							$item->size  = filesize($filename);
							$item->type  = mime_content_type($filename);

							$params->set('remote_download', null);
						}
					}
					catch (\Exception $e)
					{
						JLog::add(sprintf('Media download failed from URL %s: %s', $params->get('download_url'), $e->getMessage()), JLog::WARNING);
					}

					$params->set('download_attempt', $params->get('download_attempt', 0) + 1);
				}
				else
				{
					$params->set('remote_download', null);
				}

				$item->params = (string) $params;

				$this->db->updateObject('#__sellacious_media', $item, array('id'));
			}
		}
	}

	/**
	 * This method tells Joomla about the sellacious client application.
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 *
	 * @throws  Exception
	 */
	protected function registerClientApp()
	{
		$obj    = new stdClass;
		$helper = SellaciousHelper::getInstance();

		$obj->id   = 2;
		$obj->name = 'sellacious';
		$obj->path = JPATH_SELLACIOUS;

		JApplicationHelper::addClientInfo($obj);
	}
}
