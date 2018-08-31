<?php
/**
 * @version     1.6.0
 * @package     Sellacious Seller Stores Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Mohd Kareemuddin <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('sellacious.loader');

JLoader::register('ModSellaciousStores', __DIR__ . '/helper.php');


$db     = JFactory::getDBO();
$me     = JFactory::getUser();
$helper = SellaciousHelper::getInstance();

$c_cat          = $helper->client->loadResult(array('list.select' => 'category_id', 'user_id' => $me->id));
$c_currency     = $helper->currency->current('code_3');
$default_seller = $helper->config->get('default_seller', -1);
$multi_seller   = $helper->config->get('multi_seller', 0);

/** @var  Joomla\Registry\Registry $params */
$class_sfx             = $params->get('class_sfx', '');
$limit                 = $params->get('total_records', '50');
$category_id           = $params->get('category_id', '0');
$display_ratings       = $params->get('display_ratings', '1');
$display_product_count = $params->get('display_product_count', '1');
$layout                = $params->get('layout', 'grid');
$autoplayopt           = $params->get('autoplay', '0');
$autoplayspeed         = $params->get('autoplayspeed', '3000');
$gutter                = $params->get('gutter', '8');
$responsive0to500      = $params->get('responsive0to500', '1');
$responsive500         = $params->get('responsive500', '2');
$responsive992         = $params->get('responsive992', '3');
$responsive1200        = $params->get('responsive1200', '4');
$responsive1400        = $params->get('responsive1400', '4');
$ordering              = $params->get('ordering', '3');
$orderBy               = $params->get('orderby', 'DESC');
$filters               = array();

$filters['list.select'][] = 'a.*, u.name, u.username, u.email';

switch ($ordering)
{
	case "1":
		$ord = 'a.title ' . $orderBy;
		break;
	case "2":
		$ord = 'rand() ';
		break;
	default:
		$ord = 'rand() ';
}

$filters['list.where'][] = 'a.state = 1';
$filters['list.where'][] = 'u.block = 0';

if ($category_id)
{
	$filters['list.where'][] = 'a.category_id = ' . (int) $category_id;
}

if (!$multi_seller)
{
	$filters['list.where'][] = 'a.user_id = ' . $default_seller;
}

$filters['list.order'][] = $ord;
$filters['list.start']   = 0;
$filters['list.limit']   = $limit;

$stores = $helper->seller->loadObjectList($filters);

if (empty($stores))
{
	return;
}

require JModuleHelper::getLayoutPath('mod_sellacious_stores', $layout);
