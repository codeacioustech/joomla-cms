<?php
/**
 * @version     1.6.0
 * @package     Sellacious Special Category Products Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Mohd Kareemuddin <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

JLoader::register('ModSellaciousSpecialProducts', __DIR__ . '/helper.php');
jimport('sellacious.loader');


$db     = JFactory::getDBO();
$me     = JFactory::getUser();
$helper = SellaciousHelper::getInstance();

$c_cat           = $helper->client->loadResult(array('list.select' => 'category_id', 'user_id' => $me->id));
$c_currency      = $helper->currency->current('code_3');
$default_seller  = $helper->config->get('default_seller', -1);
$multi_seller    = $helper->config->get('multi_seller', 0);
$allowed         = $helper->config->get('allowed_product_type');
$allow_package   = $helper->config->get('allowed_product_package');
$seller_separate = $multi_seller == 2;

$class_sfx           = $params->get('class_sfx', '');
$limit               = $params->get('total_products', '50');
$splCategory         = $params->get('splcategory', 1);
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
$ordering            = $params->get('ordering', '4');
$orderBy             = $params->get('orderby', 'DESC');
$filters             = array();
$specialProductsSpl  = array();
$splCategoryClass    = 'spl-cat-' . $splCategory;

if ($splCategory)
{
	$splList = $helper->splCategory->getItem($splCategory);

	$style  = '';
	$params = new Registry($splList->params);

	// New or old format?
	$css = isset($params['styles']) ? (array) $params->get('styles') : $params;

	foreach ($css as $css_k => $css_v)
	{
		$style .= "$css_k: $css_v;";
	}

	$styles[$splList->id] = ".special-grid-layout .spl-cat-$splList->id { $style }" . ".special-list-layout .spl-cat-$splList->id { $style }" . ".special-carousel-layout .spl-cat-$splList->id { $style }";

	$doc = JFactory::getDocument();
	$doc->addStyleDeclaration(implode("\n", $styles));
}

$jInput     = JFactory::getApplication()->input;
$option     = $jInput->getString('option');
$layoutview = $jInput->getString('view');

$filters['list.join'] = array(
	array('inner', '#__sellacious_product_sellers AS ps ON ps.product_id = a.id'),
	array('inner', '#__sellacious_seller_listing AS scl ON scl.product_id = a.id and scl.seller_uid = ps.seller_uid'),
	array('inner', '#__sellacious_product_prices AS p ON p.product_id = a.id and p.seller_uid = ps.seller_uid'),
);

$filters['list.select'][] = ' DISTINCT a.*, p.product_price, p.list_price, ps.seller_uid';

if ($splCategory)
{
	$filters['list.where'][] = 'scl.category_id = ' . $splCategory . '';

	$nd    = $db ->getNullDate();
	$now   = JFactory::getDate()->toSql();

	$filters['list.where'][] = 'scl.publish_up != ' . $db->q($nd);
	$filters['list.where'][] = 'scl.publish_up < ' . $db->q($now);
	$filters['list.where'][] = 'scl.publish_down != ' . $db->q($nd);
	$filters['list.where'][] = 'scl.publish_down > ' . $db->q($now);
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

switch ($ordering)
{
	case "1":
		$ord = 'a.title ' . $orderBy;
		break;
	case "2":
		$ord = 'p.product_price ' . $orderBy;
		break;
	case "3":
		$ord = 'a.created ' . $orderBy;
		break;
	case "4":
		$ord = 'rand() ';
		break;
	default:
		$ord = 'rand() ';
}

$filters['list.where'][] = 'a.state = 1';
$filters['list.where'][] = 'p.is_fallback = 1';

if (!$multi_seller)
{
	$filters['list.where'][] = 'ps.seller_uid = ' . (int) $default_seller;
}

if ($multi_seller < 2)
{
	$filters['list.group'] = 'a.id';
}

$filters['list.order'][] = $ord;
$filters['list.start']   = 0;
$filters['list.limit']   = $limit;

// Check whether the product sellers are active
$filters['list.join'][] = array('inner', '#__sellacious_sellers AS ss ON ss.user_id = ps.seller_uid');
$filters['list.join'][]  = array('inner', '#__users AS pu ON pu.id = ss.user_id');
$filters['list.where'][] = 'pu.block = 0';

$products = $helper->product->loadObjectList($filters);

if (empty($products))
{
	return;
}

require JModuleHelper::getLayoutPath('mod_sellacious_specialcatsproducts', $layout);
