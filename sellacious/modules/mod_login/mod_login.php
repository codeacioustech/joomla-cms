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

// Include the login functions only once
JLoader::register('ModLoginHelper', __DIR__ . '/helper.php');
JLoader::register('UsersHelperRoute', dirname(__DIR__) . '/helpers/route.php');

/** @var  $params  Joomla\Registry\Registry  */
$params->def('greeting', 1);

$return	          = ModLoginHelper::getReturnURL();
$twofactormethods = ModLoginHelper::getTwoFactorMethods();
$user	          = JFactory::getUser();
$layout           = $params->get('layout', 'default');

// Logged users must load the logout sublayout
if (!$user->guest)
{
	$layout .= '_logout';
}

require JModuleHelper::getLayoutPath('mod_login', $layout);
