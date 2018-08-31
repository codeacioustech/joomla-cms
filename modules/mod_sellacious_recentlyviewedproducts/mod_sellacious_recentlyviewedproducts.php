<?php
/**
 * @version     1.6.0
 * @package     Sellacious Recently Viewed Products Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Mohd Kareemuddin <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// Include the helper functions only once
JLoader::register('ModSellaciousRecentlyViewedProducts', __DIR__ . '/helper.php');
jimport('sellacious.loader');


$db     = JFactory::getDBO();
$me     = JFactory::getUser();
$helper = SellaciousHelper::getInstance();

$input      = JFactory::getApplication()->input;
$option     = $input->getString('option');
$view       = $input->getString('view');
$pCode      = $input->getString('p');

$session = JFactory::getSession();
$codes   = $session->get('sellacious.lastviewed', array());

$c_cat          = $helper->client->loadResult(array('list.select' => 'category_id', 'user_id' => $me->id));
$c_currency     = $helper->currency->current('code_3');
$default_seller = $helper->config->get('default_seller', -1);
$multi_seller   = $helper->config->get('multi_seller', 0);
$allowed        = $helper->config->get('allowed_product_type');
$allow_package  = $helper->config->get('allowed_product_package');

$class_sfx           = $params->get('class_sfx', '');
$limit               = $params->get('total_products', '20');
$splCategory         = $params->get('splcategory', 0);
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

$recentProductsSpl = array();

if ($option == 'com_sellacious' && $view == 'product' && !empty($pCode) && in_array($pCode, $codes))
{
	$codes = array_diff($codes,array($pCode)) ;
	$codes = array_values($codes);
}

if(count($codes) > $limit)
{
	$codes = array_slice($codes, 0, $limit);
}

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

	$styles[$splList->id] = ".recent-grid-layout .spl-cat-$splList->id { $style }" . ".recent-list-layout .spl-cat-$splList->id { $style }" . ".recent-carousel-layout .spl-cat-$splList->id { $style }";

	$doc = JFactory::getDocument();
	$doc->addStyleDeclaration(implode("\n", $styles));
}

$query = $db->getQuery(true);

$query->select('a.*');
$query->from($db->qn('#__sellacious_cache_products', 'a'));

if ($helper->config->get('hide_zero_priced'))
{
	$query->where('(a.product_price > 0 OR a.price_display > 0)');
}

if ($helper->config->get('hide_out_of_stock'))
{
	$query->where('a.stock + a.over_stock > 0');
}

$allowed = $allowed == 'both' ? array('physical', 'electronic') : array($allowed);

if ($allow_package)
{
	$allowed[] = 'package';
}

if (!empty($codes))
{
	$query->where('a.code IN (' . implode(', ', $db->q($codes)) . ')');
}
else
{
	$query->where(0);
}
$query->where('(a.product_type = ' . implode(' OR a.product_type = ', $db->quote($allowed)) . ')');
$query->where('a.is_selling = 1')
	->where('a.listing_active = 1')
	->where('a.product_active = 1')
	->where('a.seller_active = 1');

$query->group('a.code');

$dispatcher = $helper->core->loadPlugins();
$dispatcher->trigger('onAfterBuildQuery', array('com_sellacious.module.recentlyviewedproducts', &$query));

$productsAll = $db->setQuery($query)->loadObjectList();

if ($splCategory)
{
	$nd  = $db->getNullDate();
	$now = JFactory::getDate()->toSql();

	foreach ($productsAll as $item)
	{
		$queryS = $db->getQuery(true);
		$queryS->select('id')->from('#__sellacious_seller_listing')
			->where('product_id = ' . $db->q($item->product_id))
			->where('category_id = ' . $db->q($splCategory));

		$queryS->where('publish_up != ' . $db->q($nd))
			->where('publish_up < ' . $db->q($now))
			->where('publish_down != ' . $db->q($nd))
			->where('publish_down > ' . $db->q($now));

		$splCatItem = $db->setQuery($queryS)->loadResult();

		if ($splCatItem)
		{
			array_push($recentProductsSpl, $item);
		}
	}

	if (!empty($recentProductsSpl))
	{
		$recentProductsNormal = array_udiff($productsAll, $recentProductsSpl,
			function ($obj_a, $obj_b)
			{
				return $obj_a->product_id - $obj_b->product_id;
			}
		);

		$products = array_merge($recentProductsSpl, $recentProductsNormal);
	}
	else
	{
		$products = $productsAll;
	}

}
else
{
	$products = $productsAll;
}

if (empty($products))
{
	return;
}

// Reorder with respect to codes array
$keys      = array_combine($codes, array_fill(1, count($codes), 0));
$productsP = ArrayHelper::pivot($products, 'code');
$products  = array_filter(array_replace($keys, $productsP));

require JModuleHelper::getLayoutPath('mod_sellacious_recentlyviewedproducts', $layout);
