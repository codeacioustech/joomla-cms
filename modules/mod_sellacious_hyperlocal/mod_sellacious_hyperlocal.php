<?php
/**
 * @version     1.6.0
 * @package     Sellacious HyperLocal Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Aditya Chakraborty <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

jimport('sellacious.loader');

JLoader::register('ModSellaciousHyperlocalHelper', __DIR__ . '/helper.php');

$db       = JFactory::getDbo();
$helper   = SellaciousHelper::getInstance();
$hlConfig = $helper->config->loadColumn(array('context' => 'plg_system_sellacioushyperlocal'), 3);

if (isset($hlConfig[0]) && JPluginHelper::isEnabled('system', 'sellacioushyperlocal'))
{
	$hlParams = new Registry();
	$hlParams->loadString($hlConfig[0]);

	$productRadius = $hlParams->get('product_radius');
	$storeRadius   = $hlParams->get('store_radius');
	$googleApiKey  = $hlParams->get('google_api_key', '');

	if (!isset($productRadius->u) || !isset($storeRadius->u))
	{
		return;
	}

	if (empty($googleApiKey))
	{
		$msg = JText::_('MOD_SELLACIOUS_HYPERLOCAL_GOOGLE_API_KEY_NOT_FOUND');
		require JModuleHelper::getLayoutPath('mod_sellacious_hyperlocal', 'empty');

		return;
	}

	$params->set('google_api_key', $googleApiKey);

	$meterUnit = $helper->unit->loadResult(array('list.select' => 'a.id', 'list.where' => array('a.title = ' . $db->quote('Meter'), 'a.symbol = ' . $db->quote('m'), 'a.unit_group = ' . $db->quote('Length'))));
	$meterUnit = $meterUnit ? : null;

	$productDistance = $helper->unit->convert($productRadius->m ? : 0, $productRadius->u, $meterUnit);
	$storeDistance   = $helper->unit->convert($storeRadius->m ? : 0, $storeRadius->u, $meterUnit);

	$browser_detect      = $params->get('browser_detect', 1);
	$autofill_components = $params->get('autofill_components', array('zip', 'city', 'district', 'state', 'country'));
	$layout              = $params->get('layout', 'default');

	require JModuleHelper::getLayoutPath('mod_sellacious_hyperlocal', $layout);
}
else
{
	$msg = JText::_('MOD_SELLACIOUS_HYPERLOCAL_PLUGIN_NOT_ENABLED');
	require JModuleHelper::getLayoutPath('mod_sellacious_hyperlocal', 'empty');
}

