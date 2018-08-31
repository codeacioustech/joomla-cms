<?php
/**
 * @version     1.6.0
 * @package     Sellacious Latest Products Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Bhavika Matariya <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

// Include the helper functions only once
JLoader::register('ModSellaciousLatestProducts', __DIR__ . '/helper.php');
jimport('sellacious.loader');


$db     = JFactory::getDBO();
$me     = JFactory::getUser();
$helper = SellaciousHelper::getInstance();

$c_cat          = $helper->client->loadResult(array('list.select' => 'category_id', 'user_id' => $me->id));
$c_currency     = $helper->currency->current('code_3');
$default_seller = $helper->config->get('default_seller', -1);
$multi_seller   = $helper->config->get('multi_seller', 0);
$allowed        = $helper->config->get('allowed_product_type');
$allow_package  = $helper->config->get('allowed_product_package');

$class_sfx           = $params->get('class_sfx', '');
$limit               = $params->get('total_products', '50');
$categories          = $params->get('categories', '');
$splCategory         = $params->get('splcategory', 0);
$prods               = $params->get('products', '');
$featurelist         = $params->get('featurelist', '1');
$displayratings      = $params->get('displayratings', '1');
$displaycomparebtn   = $params->get('displaycomparebtn', '1');
$displayaddtocartbtn = $params->get('displayaddtocartbtn', '1');
$displaybuynowbtn    = $params->get('displaybuynowbtn', '1');
$displayquickviewbtn = $params->get('displayquickviewbtn', '1');
$layout              = $params->get('layout', 'grid');
$autoplayopt         = $params->get('autoplay', '0');
$autoplayspeed       = $params->get('autoplayspeed', '3000');
$gutter              = $params->get('gutter', '8');
$responsive0to500    = $params->get('responsive0to500', '1');
$responsive500       = $params->get('responsive500', '2');
$responsive992       = $params->get('responsive992', '3');
$responsive1200      = $params->get('responsive1200', '4');
$responsive1400      = $params->get('responsive1400', '4');
$filters             = array();
$latestProductsSpl   = array();

if ($splCategory)
{
	$splList = $helper->splCategory->getItem($splCategory);

	$style = '';
	$params   = new Registry($splList->params);

	// New or old format?
	$css = isset($params['styles']) ? (array) $params->get('styles') : $params;

	foreach ($css as $css_k => $css_v)
	{
		$style .= "$css_k: $css_v;";
	}

	$styles[$splList->id] = ".latest-grid-layout .spl-cat-$splList->id { $style }" . ".latest-list-layout .spl-cat-$splList->id { $style }" . ".latest-carousel-layout .spl-cat-$splList->id { $style }";

	$doc = JFactory::getDocument();
	$doc->addStyleDeclaration(implode("\n", $styles));
}

$jInput     = JFactory::getApplication()->input;
$catId      = $jInput->getInt('category_id');
$option     = $jInput->getString('option');
$layoutview = $jInput->getString('view');

if ($option == 'com_sellacious' && !empty($catId))
{
	$categories = array($catId);
}

$filters['list.join'][] = array('inner', '#__sellacious_product_sellers AS ps ON ps.product_id = a.id');
$filters['list.join'][] = array('inner', '#__sellacious_product_prices AS p ON p.product_id = a.id and p.seller_uid = ps.seller_uid');

if ($categories)
{
	$filters['list.join'][] = array('inner', '#__sellacious_product_categories AS pc ON pc.product_id = a.id');
}

$filters['list.select'][] = ' DISTINCT a.*, ps.seller_uid';

if ($prods)
{
	$filters['list.where'][] = 'a.id IN (' . implode(",", $prods) . ')';
}
elseif ($categories)
{
	$filters['list.where'][] = 'pc.category_id IN (' . implode(",", $categories) . ')';
}

if (!$multi_seller)
{
	$filters['list.where'][] = 'ps.seller_uid = ' . (int) $default_seller;
}

if ($multi_seller < 2)
{
	$filters['list.group'] = 'a.id';
}

if ($helper->config->get('hide_zero_priced'))
{
	$filters['list.where'][] = '(p.product_price > 0 OR ps.price_display > 0)';
}

if ($helper->config->get('hide_out_of_stock'))
{
	$filters['list.where'][] = 'ps.stock + ps.over_stock > 0';
}

$allowed = $allowed == 'both' ? array('physical', 'electronic') : array($allowed);

if ($allow_package)
{
	$allowed[] = 'package';
}

$filters['list.where'][] = '(a.type = ' . implode(' OR a.type = ', $db->quote($allowed)) . ')';
$filters['list.where'][] = 'a.state = 1';
$filters['list.order'][] = 'a.created DESC';
$filters['list.start']   = 0;

// Check whether the product sellers are active
$filters['list.join'][] = array('inner', '#__sellacious_sellers AS ss ON ss.user_id = ps.seller_uid');
$filters['list.join'][]  = array('inner', '#__users AS pu ON pu.id = ss.user_id');
$filters['list.where'][] = 'pu.block = 0';

$productsAll = $helper->product->loadObjectList($filters, 'id');
$pids        = array_keys($productsAll);
$products    = array();

if ($splCategory && $pids)
{
	$nd    = $db ->getNullDate();
	$now   = JFactory::getDate()->toSql();

	$queryS = $db->getQuery(true);
	$queryS->select('DISTINCT product_id')->from('#__sellacious_seller_listing')
		->where('product_id IN (' . implode(', ', $db->q($pids)) . ')')
		->where('category_id = ' . $db->q($splCategory))
		->where('state = 1');

	$queryS->where('publish_up != ' . $db->q($nd))
		->where('publish_up < ' . $db->q($now))
		->where('publish_down != ' . $db->q($nd))
		->where('publish_down > ' . $db->q($now));

	$sPids = $db->setQuery($queryS)->loadColumn();

	foreach (array_slice($sPids, 0, $limit ?: 10) as $pid)
	{
	    $products[] = $productsAll[$pid];
	}
}
else
{
    $products = array_slice($productsAll, 0, $limit ?: 10);
}

if (empty($products))
{
	return;
}

require JModuleHelper::getLayoutPath('mod_sellacious_latestproducts', $layout);
