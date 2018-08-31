<?php
/**
 * @version     1.6.0
 * @package     Sellacious Seller Products Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Mohd Kareemuddin <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

// Include the helper functions only once
JLoader::register('ModSellaciousSellerProducts', __DIR__ . '/helper.php');
jimport('sellacious.loader');

$db     = JFactory::getDBO();
$me     = JFactory::getUser();
$helper = SellaciousHelper::getInstance();

$input      = JFactory::getApplication()->input;
$option     = $input->getString('option');
$view       = $input->getString('view');
$pCode      = $input->getString('p');

$c_cat          = $helper->client->loadResult(array('list.select' => 'category_id', 'user_id' => $me->id));
$c_currency     = $helper->currency->current('code_3');

/** @var  Joomla\Registry\Registry $params */
$class_sfx           = $params->get('class_sfx', '');
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

$sellerProductsSpl = array();

$productsAll = ModSellaciousSellerProducts::getSellerProducts($params);

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

	$styles[$splList->id] = ".sellerproducts-grid-layout .spl-cat-$splList->id { $style }" . ".sellerproducts-list-layout .spl-cat-$splList->id { $style }" . ".sellerproducts-carousel-layout .spl-cat-$splList->id { $style }";

	$doc = JFactory::getDocument();
	$doc->addStyleDeclaration(implode("\n", $styles));

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
			array_push($sellerProductsSpl, $item);
		}
	}

	if (!empty($sellerProductsSpl))
	{
		$sellerProductsNormal = array_udiff($productsAll, $sellerProductsSpl,
			function ($obj_a, $obj_b)
			{
				return $obj_a->product_id - $obj_b->product_id;
			}
		);

		$products = array_merge($sellerProductsSpl, $sellerProductsNormal);
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

require JModuleHelper::getLayoutPath('mod_sellacious_sellerproducts', $layout);
