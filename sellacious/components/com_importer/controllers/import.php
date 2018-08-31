<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// No direct access.
defined('_JEXEC') or die;

use Sellacious\Import\ImportHelper;
use Sellacious\Import\AbstractImporter;
use Sellacious\Import\ImagesImporter;
use Sellacious\Utilities\Timer;

/**
 * Import/export controller class.
 *
 * @since   1.5.2
 */
class ImporterControllerImport extends SellaciousControllerAdmin
{
	/**
	 * Upload the given file to the import staging folder
	 *
	 * @return  bool
	 *
	 * @since   1.5.2
	 */
	public function upload()
	{
		JSession::checkToken() or die('Invalid token.');

		$handler = $this->input->getString('handler');

		$this->setRedirect(JRoute::_('index.php?option=com_importer&view=import', false));

		try
		{
			if (!$this->helper->access->check('importer.import', $handler, 'com_importer'))
			{
				throw new Exception(JText::_('COM_IMPORTER_ACCESS_NOT_ALLOWED'));
			}

			/** @var  \ImporterModelImport  $model */
			$model = $this->getModel('Import', 'ImporterModel');
			$model->upload($handler);

			$this->setMessage(JText::_('COM_IMPORTER_IMPORT_FILE_UPLOAD_SUCCESS'));

			return true;
		}
		catch (SellaciousExceptionPremium $e)
		{
			$this->setMessage($e->getMessage(), 'premium');
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'warning');
		}

		return false;
	}

	/**
	 * Set column alias for the import CSV
	 *
	 * @return  void
	 *
	 * @since   1.5.2
	 */
	public function setOptionsAjax()
	{
		$this->validateAjaxToken();

		$app   = JFactory::getApplication();
		$state = $app->getUserState('com_importer.import.state');

		// Check if there is a process queued up and started
		$active = (is_object($state) && !empty($state->logfile)) ? ($state->logfile && file_exists($state->logfile)) : null;

		if ($active === null)
		{
			$response = array(
				'state'   => 0,
				'message' => JText::_('COM_IMPORTER_IMPORT_FILE_NO_PENDING_TO_IMPORT'),
				'data'    => null,
			);

			echo json_encode($response);

			$app->close();
		}

		if ($active)
		{
			$response = array(
				'state'   => 1,
				'message' => JText::_('COM_IMPORTER_IMPORT_OPTIONS_SKIP_PROCESS_RUNNING'),
				'data'    => null,
			);

			echo json_encode($response);

			$app->close();
		}

		// If queued up but not started yet, we have a chance to update the options
		try
		{
			if (!$this->helper->access->check('importer.import', $state->handler, 'com_importer'))
			{
				throw new Exception(JText::_('COM_IMPORTER_ACCESS_NOT_ALLOWED'));
			}

			/** @var  \ImporterModelImport  $model */
			$model = $this->getModel('Import', 'ImporterModel');
			$model->setOptions();

			$response = array(
				'state'   => 1,
				'message' => JText::_('COM_IMPORTER_IMPORT_IMPORT_OPTIONS_UPDATED'),
				'data'    => null,
			);
		}
		catch (Exception $e)
		{
			$response = array(
				'state'   => 0,
				'message' => $e->getMessage(),
				'data'    => null,
			);
		}

		echo json_encode($response);

		$app->close();
	}

	/**
	 * Process the actual import from the set state data. Callable via Ajax only
	 *
	 * @return  void
	 *
	 * @since   1.5.2
	 */
	public function importAjax()
	{
		$this->validateAjaxToken();

		$app   = JFactory::getApplication();
		$state = $app->getUserState('com_importer.import.state');

		// Check if there is a process queued up and started
		$active = (is_object($state) && !empty($state->logfile)) ? ($state->logfile && file_exists($state->logfile)) : null;

		if ($active === null)
		{
			$response = array(
				'state'   => 0,
				'message' => JText::_('COM_IMPORTER_IMPORT_FILE_NO_PENDING_TO_IMPORT'),
				'data'    => null,
			);

			echo json_encode($response);

			$app->close();
		}

		if ($active)
		{
			$log = file_get_contents($state->logfile);

			if (strpos($log, 'EOF') || isset($state->done))
			{
				$app->setUserState('com_importer.import.state', null);

				$response = array(
					'state'   => 3,
					'message' => JText::sprintf('COM_IMPORTER_IMPORT_COMPLETE', $state->handler),
					'data'    => array('log' => $log),
				);
			}
			else
			{
				$response = array(
					'state'   => 2,
					'message' => '&hellip;',
					'data'    => array('log' => $log),
				);
			}

			echo json_encode($response);

			$app->close();
		}

		// Start import process execution
		$response = $this->startImport();

		echo json_encode($response);

		$app->close();
	}

	/**
	 * Cancel the active import session
	 *
	 * @return  bool
	 *
	 * @since   1.5.2
	 */
	public function cancel()
	{
		JSession::checkToken() or die('Invalid token.');

		$app   = JFactory::getApplication();
		$state = $app->getUserState('com_importer.import.state', null);

		if (isset($state))
		{
			// Also delete import source files to avoid unnecessary clutter. But do not remove log file.
			if ($state->path && is_file($state->path))
			{
				jimport('joomla.filesystem.file');

				JFile::delete($state->path);
			}

			$app->setUserState('com_importer.import.state', null);
		}

		$this->setMessage(JText::_('COM_IMPORTER_IMPORT_SESSION_ABANDON_SUCCESS'));
		$this->setRedirect(JRoute::_('index.php?option=com_importer&view=import', false));

		return true;
	}

	/**
	 * Start the queued import process
	 *
	 * @return  array  The response data
	 *
	 * @since   1.5.2
	 */
	protected function startImport()
	{
		// Get stateful instance of Timer
		$app   = JFactory::getApplication();
		$state = $app->getUserState('com_importer.import.state');
		$timer = Timer::getInstance('Import.' . $state->handler, $state->logfile);

		try
		{
			$timer->log('Initializing import...');

			$state->mapping = isset($state->mapping) ? (array) $state->mapping : array();
			$state->options = isset($state->options) ? (array) $state->options : array();

			/** @var  AbstractImporter  $importer */
			$importer = ImportHelper::getImporter($state->handler);

			$importer->load($state->path);
			$importer->setColumnsAlias($state->mapping);
			$importer->setOption('output_csv', $state->outfile);

			foreach ($state->options as $oKey => $oValue)
			{
				$importer->setOption($oKey, $oValue);
			}

			$importer->import();

			$timer->log(JText::_('COM_IMPORTER_IMPORT_EMAILING'));

			$subject    = JText::sprintf('COM_IMPORTER_IMPORT_LOG', $state->handler, $state->timestamp);
			$body       = file_get_contents($state->logfile);
			$attachment = array($state->path);

			if (is_file($state->outfile))
			{
				$attachment[] = $state->outfile;
			}

			if ($this->sendMail($subject, $body, $attachment))
			{
				$timer->log(JText::_('COM_IMPORTER_IMPORT_EMAIL_SENT'));
			}
			else
			{
				$timer->log(JText::_('COM_IMPORTER_IMPORT_EMAIL_FAIL'));
			}

			$app->setUserState('com_importer.import.state.done', true);
			$timer->log('EOF');

			$log      = file_get_contents($state->logfile);
			$response = array(
				'state'   => 3,
				'message' => JText::sprintf('COM_IMPORTER_IMPORT_COMPLETE', $state->handler),
				'data'    => array('log' => $log),
			);
		}
		catch (Exception $e)
		{
			$timer->interrupt($e->getMessage());
			$timer->log('EOF');

			$log      = file_get_contents($state->logfile);
			$response = array(
				'state'   => 0,
				'message' => JText::sprintf('COM_IMPORTER_IMPORT_INTERRUPTED', $e->getMessage()),
				'data'    => array('log' => $log),
			);
		}

		return $response;
	}

	/**
	 * Send an email with the given parameters
	 *
	 * @param   string  $subject     Email subject
	 * @param   string  $body        Email body
	 * @param   array   $attachment  The list of attachment
	 *
	 * @return  bool
	 * @throws  Exception
	 *
	 * @since   1.5.2
	 *
	 * @deprecated   Move this to an plugin trigger and send email via emailing plugin
	 */
	protected function sendMail($subject, $body, $attachment)
	{
		$app = JFactory::getApplication();
		$me  = JFactory::getUser();

		$to          = array($me->name => $me->email);
		$cc          = array();
		$mailFrom    = $app->get('mailfrom');
		$fromName    = $app->get('fromname');
		$replyTo     = $app->get('mailfrom');
		$replyToName = $app->get('fromname');

		$mailer = JFactory::getMailer();

		if ($mailer->setSender(array($mailFrom, $fromName, false)) === false)
		{
			throw new RuntimeException(JText::_('COM_IMPORTER_IMPORT_EMAIL_FAIL_ADD_SENDER'));
		}

		if ($mailer->addReplyTo($replyTo, $replyToName) === false)
		{
			throw new RuntimeException(JText::_('COM_IMPORTER_IMPORT_EMAIL_FAIL_ADD_REPLY_TO'));
		}

		$mailer->clearAllRecipients();

		if ($mailer->addRecipient(array_values($to), array_keys($to)) === false)
		{
			throw new RuntimeException(JText::_('COM_IMPORTER_IMPORT_EMAIL_FAIL_ADD_RECIPIENT'));
		}

		if (count($cc) && $mailer->addCc(array_values($cc), array_keys($cc)) === false)
		{
			throw new RuntimeException(JText::_('COM_IMPORTER_IMPORT_EMAIL_FAIL_ADD_RECIPIENT'));
		}

		$mailer->isHtml(false);
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->addAttachment($attachment);

		return $mailer->Send();
	}

	/**
	 * Checks for a form token in the ajax request.
	 *
	 * @param   string  $method  The request method in which to look for the token key.
	 *
	 * @return  void  Aborts request with invalid token response on invalid CSRF token
	 *
	 * @since   1.5.2
	 */
	protected function validateAjaxToken($method = 'post')
	{
		$app = JFactory::getApplication();

		if (!JSession::checkToken($method))
		{
			$response = array(
				'state'   => 0,
				'message' => JText::_('JINVALID_TOKEN'),
				'data'    => null,
			);
			echo json_encode($response);

			$app->close();
		}
	}
}
