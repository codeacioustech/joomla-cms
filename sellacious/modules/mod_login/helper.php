<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     SPL Sellacious Private License; see http://www.sellacious.com/spl.html
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access.
defined('_JEXEC') or die;

/**
 * Helper for mod_login
 *
 * @package     Sellacious.Application
 * @subpackage  mod_login
 *
 * @since       1.5
 */
class ModLoginHelper
{
	/**
	 * Retrieve the url where the user should be returned after logging in
	 *
	 * @return string
	 */
	public static function getReturnURL()
	{
		// Check the session for previously entered login form data.
		$app  = JFactory::getApplication();
		$data = $app->getUserState('users.login.form.data', array());

		// Check for return URL from the request first
		if ($return = $app->input->get('return', '', 'BASE64'))
		{
			$data['return'] = base64_decode($return);

			if (!JUri::isInternal($data['return']))
			{
				$data['return'] = '';
			}
		}

		// Set the return URL if empty.
		if (empty($data['return']) || $data['return'] == 'index.php?option=com_login')
		{
			$data['return'] = 'index.php';
		}

		$app->setUserState('users.login.form.data', $data);

		return base64_encode($data['return']);
	}

	/**
	 * Get list of available two factor methods
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getTwoFactorMethods()
	{
		FOFPlatform::getInstance()->importPlugin('twofactorauth');
		$identities = FOFPlatform::getInstance()->runPlugins('onUserTwofactorIdentify', array());

		$options = array(
			JHtml::_('select.option', 'none', JText::_('JGLOBAL_OTPMETHOD_NONE'), 'value', 'text'),
		);

		if (!empty($identities))
		{
			foreach ($identities as $identity)
			{
				if (!is_object($identity))
				{
					continue;
				}

				$options[] = JHtml::_('select.option', $identity->method, $identity->title, 'value', 'text');
			}
		}

		return $options;
	}
}
