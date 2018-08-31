<?php
/**
 * @version      1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Mohd Kareemuddin <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access
use Joomla\Utilities\ArrayHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

JLoader::import('sellacious.loader');

if (class_exists('SellaciousHelper')):

/**
 * The Product Notification management plugin
 *
 * @since   1.6.0
 */
class plgSystemSellaciousProductNotification extends SellaciousPlugin
{
	/**
	 * @var    bool
	 *
	 * @since  1.6.0
	 */
	protected $hasConfig = true;

	/**
	 * Log entries collected during execution.
	 *
	 * @var    array
	 *
	 * @since  1.6.0
	 */
	protected $log = array();

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.6.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var    \JApplicationCms
	 *
	 * @since  1.6.0
	 */
	protected $app;

	/**
	 * @var    \JDatabaseDriver
	 *
	 * @since  1.6.0
	 */
	protected $db;

	/**
	 * Adds product notification template fields to the sellacious form for creating email templates
	 *
	 * @param   JForm  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		parent::onContentPrepareForm($form, $data);

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
				if (strpos($array['context'], 'product_notification') !== false)
				{
					$form->loadFile(__DIR__ . '/forms/notification.xml', false);
				}
				elseif (strpos($array['context'], 'product_status') !== false)
				{
					$form->loadFile(__DIR__ . '/forms/status.xml', false);
				}
				elseif (strpos($array['context'], 'product_approval') !== false)
				{
					$form->loadFile(__DIR__ . '/forms/approval.xml', false);
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
	 * @since   1.6.0
	 */
	public function onFetchEmailContext($context, array &$contexts = array())
	{
		if ($context == 'com_sellacious.emailtemplate')
		{
			$contexts['product_notification.admin'] = JText::_('PLG_SYSTEM_SELLACIOUSPRODUCTNOTIFICATION_PRODUCT_NOTIFICATION_ADMIN');
			$contexts['product_approval.admin']     = JText::_('PLG_SYSTEM_SELLACIOUSPRODUCTNOTIFICATION_PRODUCT_APPROVAL_ADMIN');
			$contexts['product_status.seller']      = JText::_('PLG_SYSTEM_SELLACIOUSPRODUCTNOTIFICATION_PRODUCT_STATUS_SELLER');
		}
	}
	/**
	 * This method send product notification based on a schedule cron job or page loads at set interval
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	public function onAfterRoute()
	{
		$useCRON  = $this->params->get('cron', 1);
		$cronKey  = $this->params->get('cron_key', '');
		$key      = $this->app->input->getString('notification_key');

		$this->log  = array();

		// Cron use is disabled or the cronKey matches, if cron enabled do only at given seconds interval
		$canRun = $useCRON ? (trim($cronKey) != '' && $cronKey == $key) : false;

		if ($canRun)
		{
			$t = microtime(true);

			try
			{
				// Sent Products Statistic Notification to admin via cron
				$this->sendProductsReport(true);
			}
			catch (Exception $e)
			{
				$this->log($e->getMessage());

				JLog::add($e->getMessage(), JLog::CRITICAL);
			}

			if ($useCRON)
			{
				echo '<pre>';
				echo microtime(true) - $t;
				echo "\n";
				echo implode("\n", $this->log);
				echo '</pre>';

				jexit();
			}
		}
		else
		{
			try
			{
				// Sent Products Statistic Notification to admin via page load
				$this->sendProductsReport(false);
			}
			catch (Exception $e)
			{
				JLog::add($e->getMessage(), JLog::CRITICAL);
			}
		}
	}

	/**
	 * Method is called right after an item is saved
	 *
	 * @param   string    $context  The calling context
	 * @param   stdClass  $object   Holds the new message data
	 * @param   boolean   $isNew    If the content is just created
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	public function onContentAfterSave($context, $object, $isNew)
	{
		if (!class_exists('SellaciousHelper'))
		{
			return true;
		}

		switch ($context)
		{
			case 'com_sellacious.product':
				$this->handleProductSave($object, $isNew);
				break;
		}

		return true;
	}

	/**
	 * Method is called right after an item state is changed
	 *
	 * @param   string  $context  The calling context
	 * @param   int[]   $pks      Record ids which are affected
	 * @param   bool    $value    The new state value
	 *
	 * @return  bool
	 *
	 * @since   1.6.0
	 */
	public function onContentChangeState($context, $pks, $value)
	{
		if (!class_exists('SellaciousHelper'))
		{
			return true;
		}

		if (count($pks) == 0)
		{
			return true;
		}

		if ($context == 'com_sellacious.product')
		{
			$helper = SellaciousHelper::getInstance();

			foreach ($pks as $pk)
			{
				$object = $helper->product->getItem($pk);

				$this->handleProductState($object, false);
			}
		}

		return true;
	}

	/**
	 * Handle the events for Product state changed
	 *
	 * @param   stdClass  $object
	 * @param   bool      $isNew
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	protected function handleProductState($object, $isNew)
	{
		$helper = SellaciousHelper::getInstance();

		$state  = $object->state;

		if ($state == 1 || $state == -3 )
		{
			// Send to the respective sellers
			$table = JTable::getInstance('EmailTemplate', 'SellaciousTable');
			$table->load(array('context' => 'product_status.seller'));

			if ($table->get('state'))
			{
				$sellers = $this->getProductSellers($object->id);

				foreach ($sellers as $sellerId)
				{
					$seller                 = $helper->user->getItem(array('id' => $sellerId));
					$sellerInfo             = $helper->seller->getItem(array('user_id' => $sellerId));
					$object->seller_company = $sellerInfo ? ($sellerInfo->store_name ?: $sellerInfo->title) : '';
					$object->product_code   = $helper->product->getCode($object->id, 0, $sellerId);

					$replacements = $this->getValues('product_status.seller', $object);

					$recipients = explode(',', $table->get('recipients'));

					if ($table->get('send_actual_recipient'))
					{
						array_unshift($recipients, $seller->email);
					}

					$this->queue($table, $replacements, $recipients);
				}
			}
		}
	}

	/**
	 * Handler for Product save
	 *
	 * @param   stdClass  $object
	 * @param   bool      $isNew
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	protected function handleProductSave($object, $isNew)
	{
		$helper     = SellaciousHelper::getInstance();
		$state      = $object->state;

		$requireProductApprove = $helper->config->get('seller_product_approve', 0);

		if ($state == -1  && $requireProductApprove)
		{
			$sellerInfo             = $helper->seller->getItem(array('user_id' => $object->created_by));
			$object->seller_company = $sellerInfo ? ($sellerInfo->store_name ?: $sellerInfo->title) : '';
			$object->product_code   = $helper->product->getCode($object->id, 0, $object->created_by);

			// Send to administrators
			$table = JTable::getInstance('EmailTemplate', 'SellaciousTable');
			$table->load(array('context' => 'product_approval.admin'));

			if ($table->get('state'))
			{
				$recipients = explode(',', $table->get('recipients'));

				if ($table->get('send_actual_recipient'))
				{
					$recipients = array_merge($this->getAdmins(), $recipients);
				}

				$this->queue($table, $this->getValues('product_approval.admin', $object), $recipients);
			}
		}
	}
	/**
	 * Get an array of replacement data for an email
	 *
	 * @param   string  $context
	 * @param   object  $object
	 *
	 * @return  string[]
	 *
	 * @throws  Exception
	 *
	 * @since   1.6.0
	 */
	protected function getValues($context, $object)
	{
		$helper      = SellaciousHelper::getInstance();
		$emailParams = $helper->config->getParams('com_sellacious', 'emailtemplate_options');

		switch ($context)
		{
			case 'product_status.seller':

				if($object->state == 1)
				{
					$status = JText::_('PLG_SELLACIOUS_MAILQUEUE_PRODUCT_STATUS_PUBLISHED');
				}
				elseif($object->state == -3)
				{
					$status = JText::_('PLG_SELLACIOUS_MAILQUEUE_PRODUCT_STATUS_DISAPPROVED');
				}

				$values = array(
					'sitename'       => JFactory::getConfig()->get('sitename'),
					'site_url'       => rtrim(JUri::root(), '/'),
					'email_header'   => $emailParams->get('header', ''),
					'email_footer'   => $emailParams->get('footer', ''),
					'date'           => JHtml::_('date', $object->created, 'F d, Y h:i A T'),
					'product_name'   => $object->title,
					'product_url'    => JRoute::_(JUri::root() . 'index.php?option=com_sellacious&view=product&p=' . $object->product_code),
					'status'         => $status,
					'seller_company' => $object->seller_company,
				);
				break;
			case 'product_approval.admin':

				if($object->state == -1)
				{
					$status = JText::_('PLG_SELLACIOUS_MAILQUEUE_PRODUCT_APPROVAL_PENDING');
				}

				$values = array(
					'sitename'       => JFactory::getConfig()->get('sitename'),
					'site_url'       => rtrim(JUri::root(), '/'),
					'email_header'   => $emailParams->get('header', ''),
					'email_footer'   => $emailParams->get('footer', ''),
					'date'           => JHtml::_('date', $object->created, 'F d, Y h:i A T'),
					'product_name'   => $object->title,
					'product_url'    => JRoute::_(JUri::root() . 'index.php?option=com_sellacious&view=product&p=' . $object->product_code),
					'status'         => $status,
					'seller_company' => $object->seller_company,
				);
				break;
			default:
				$values = array();
		}

		return $values;
	}

	/**
	 * Send the email to the administrators for the given products objects using given email template object
	 *
	 * @param   JTable    $template  The template table object
	 * @param   int       $productCount  Total Number of Products
	 * @param   string    $interval  Interval
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	protected function addAdminMailProductReport($template, $productCount, $interval)
	{
		$helper = SellaciousHelper::getInstance();

		// Load recipients
		$recipients = explode(',', $template->get('recipients'));

		if ($template->get('send_actual_recipient'))
		{
			$recipients = array_merge($this->getAdmins(), $recipients);
		}

		$interval   = (int) $interval > 1 ? $interval . 's' : $interval;

		if (count($recipients))
		{
			$emailParams = $helper->config->getParams('com_sellacious', 'emailtemplate_options');

			$replacements = array(
				'sitename'       => JFactory::getConfig()->get('sitename'),
				'site_url'       => rtrim(JUri::root(), '/'),
				'email_header'   => $emailParams->get('header', ''),
				'email_footer'   => $emailParams->get('footer', ''),
				'total_products' => (int) $productCount,
				'time_duration'  => $interval,
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
	 * @since   1.6.0
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
	 * This method sends a product statistic notification email to admin.
	 *
	 * @param boolean $isCron Send notification via Cron
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	protected function sendProductsReport($isCron = false)
	{
		// Send to administrators
		$table = JTable::getInstance('EmailTemplate', 'SellaciousTable');
		$table->load(array('context' => 'product_notification.admin'));

		$params    = new Registry($table->get('params'));
		$intervals = (array) $params->get('intervals');

		if ($table->get('state'))
		{
			if(count($intervals))
			{
				foreach ($intervals as $interval)
				{
					$productsCount = (int) $this->getProductsCount($interval);

					if($isCron)
					{
						$this->addAdminMailProductReport($table, $productsCount, $interval);
					}
					else
					{
						// Check to send notification
						$canSendReport = $this->canSendProductsReport($interval);

						if ($canSendReport)
						{
							$this->addAdminMailProductReport($table, $productsCount, $interval);
						}
					}
				}
			}
		}
	}

	/**
	 * This method check when to send a product statistic notification email to admin.
	 *
	 * @param  string
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	protected function canSendProductsReport($interval)
	{
		$a = strtotime('now');
		$b = strtotime('now + ' . $interval);

		$intervalTime = $b - $a;
		$logfile      = $this->app->get('tmp_path') . '/' . md5($intervalTime . __METHOD__);

		$lastAccess = 0;
		$curTime    = time();

		if (is_readable($logfile))
		{
			$lastAccess = file_get_contents($logfile);
		}

		if ($lastAccess != 0)
		{
			if ($interval == '1 day')
			{
				$lastAccessDate = JFactory::getDate($lastAccess);
				$lastAccessDate->setTime(0, 0, 0)->modify('1 day');
			}
			elseif ($interval == '1 week')
			{
				$lastAccessDate = JFactory::getDate($lastAccess);
				$lastAccessDate->setTime(0, 0, 0)->modify('1 week')->modify('last sunday');
			}
			// 1 month (30days)
			else
			{
				$lastAccessDate = JFactory::getDate($lastAccess);
				$lastAccessDate->setTime(0, 0, 0)->modify('1 month')->modify('first day of this month');
			}

			$canRun = ($lastAccessDate->toUnix() < $curTime);
		}
		else
		{
			$canRun = true;
		}


		if ($canRun)
		{
			// Mark started earlier to avoid any other instance creating in between
			file_put_contents($logfile, $curTime);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get Total Products created in given interval
	 *
	 * @param   $interval
	 *
	 * @return  int
	 *
	 * @since   1.6.0
	 */
	protected function getProductsCount($interval)
	{
		$db     = JFactory::getDbo();
		$helper = SellaciousHelper::getInstance();

		$filters = array();

		$filters['list.where'][] = 'a.state = 1';

		$start = JFactory::getDate();
		$start->setTime(0,0, 0);
		$start->modify("-$interval");

		$end   = JFactory::getDate();
		$end->setTime(0,0,0)->modify('-1 second');

		$filters['list.where'][] = sprintf('a.created BETWEEN %s AND %s', $db->q($start->toSql()), $db->q($end));
		$result = $helper->product->count($filters);

		return $result;
	}

	/**
	 * Get a list of administrator users who can receive administrative emails
	 *
	 * @return  array
	 *
	 * @since   1.6.0
	 */
	protected function getAdmins()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		try
		{
			// Super user = 8, as of J3.x
			$helper = SellaciousHelper::getInstance();
			$groups = (array) $helper->config->get('usergroups_company') ?: array(8);

			$query->select('u.email')->from('#__users u')
				->where('u.block = 0');

			$query->join('inner', '#__user_usergroup_map m ON m.user_id = u.id')
				->where('m.group_id IN (' . implode(',', $groups) . ')');

			$query->group('u.email');

			$db->setQuery($query);
			$admins = $db->loadColumn();
		}
		catch (Exception $e)
		{
			$admins = array();
		}

		return $admins;
	}

	/**
	 * Get a list of sellers ids for the given product
	 *
	 * @param   int  $product_id
	 *
	 * @return  array
	 *
	 * @since   1.6.0
	 */
	protected function getProductSellers($product_id)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		try
		{
			$query->select('ps.seller_uid')->from('#__sellacious_product_sellers ps')
				->where('ps.state = 1')
				->where('ps.product_id = ' . (int) $product_id);

			$db->setQuery($query);
			$sellers = $db->loadColumn();
		}
		catch (Exception $e)
		{
			$sellers = array();
		}

		return $sellers;
	}

	/**
	 * Log the messages if logging enabled
	 *
	 * @param   string  $message  The message line to be logged
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	protected function log($message)
	{
		$this->log[] = $message;
	}
}

endif;
