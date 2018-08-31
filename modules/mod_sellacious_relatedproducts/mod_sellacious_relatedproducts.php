<?php
/**
 * @version     1.6.0
 * @package     Sellacious Related Products Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Bhavika Matariya <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Registry\Registry;

JLoader::register('ModSellaciousRelatedProducts', __DIR__ . '/helper.php');
jimport('sellacious.loader');


$db     = JFactory::getDBO();
$me     = JFactory::getUser();
$helper = SellaciousHelper::getInstance();

$jInput     = JFactory::getApplication()->input;
$p_code     = $jInput->getString('p');
$option     = $jInput->getString('option');
$layoutview = $jInput->getString('view');

$c_cat          = $helper->client->loadResult(array('list.select' => 'category_id', 'user_id' => $me->id));
$c_currency     = $helper->currency->current('code_3');
$default_seller = $helper->config->get('default_seller', -1);
$multi_seller   = $helper->config->get('multi_seller', 0);
$allowed        = $helper->config->get('allowed_product_type');
$allow_package  = $helper->config->get('allowed_product_package');

$class_sfx           = $params->get('class_sfx', '');
$limit               = $params->get('total_products', '50');
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
$related_for         = $params->get('related_for', '1');
$productsRelated     = $params->get('products_related', 0);
$relatedIds          = '';
$productID           = '';
$relatedProductsSpl  = array();
$relatedProducts     = array();
$prods               = array();

if ($related_for == 2 || $related_for == 3)
{
	if ($productsRelated == 1)
	{
		if ($params->get('products') != "")
		{
			$prods = $params->get('products');
		}
	}
	else
	{
		if ($params->get('products') != "")
		{
			$relatedIds = implode(",", $params->get('products'));

			if ($splCategory)
			{
				$nd    = $db ->getNullDate();
				$now   = JFactory::getDate()->toSql();

				foreach ($params->get('products') as $item)
				{
					$queryS = $db->getQuery(true);
					$queryS->select('id')->from('#__sellacious_seller_listing')
						->where('product_id = ' . $db->q($item))
						->where('category_id = ' . $db->q($splCategory));

					$queryS->where('publish_up != ' . $db->q($nd))
						->where('publish_up < ' . $db->q($now))
						->where('publish_down != ' . $db->q($nd))
						->where('publish_down > ' . $db->q($now));

					$splCatItem = $db->setQuery($queryS)->loadResult();

					if ($splCatItem)
					{
						array_push($relatedProductsSpl, $item);
					}
				}
			}
		}
	}
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

	$styles[$splList->id] = ".related-grid-layout .spl-cat-$splList->id { $style }" . ".related-list-layout .spl-cat-$splList->id { $style }" . ".related-carousel-layout .spl-cat-$splList->id { $style }";

	$doc = JFactory::getDocument();
	$doc->addStyleDeclaration(implode("\n", $styles));
}

if ($related_for == 1 || $related_for == 3)
{
	if ($option == 'com_sellacious' && !empty($p_code))
	{
		$helper->product->parseCode($p_code, $product_id);

		if ($product_id)
		{
			$productID = $product_id;
			array_push($prods, $product_id);
		}
	}
}

if ($relatedIds != '')
{
	$selectedRelatedIds = array_filter(explode(',', $relatedIds));

	if (count($selectedRelatedIds))
	{
		$relatedIdsFiltered = ModSellaciousRelatedProducts::getFilteredSelectedProducts($selectedRelatedIds);
		$relatedIds         = implode(",", $relatedIdsFiltered);
	}
}

$allowed = $allowed == 'both' ? array('physical', 'electronic') : array($allowed);

if ($allow_package)
{
	$allowed[] = 'package';
}

if ($prods)
{
	foreach ($prods as $prod)
	{
		if ($relatedIds != '')
		{
			$relatedIds .= ',';
		}

		$groups = $helper->relatedProduct->loadColumn(array(
			'list.select' => 'a.group_alias',
			'product_id'  => $prod,
		));

		if (count($groups) > 0)
		{
			$filters = array();

			$filters['list.join'] = array(
				array('inner', '#__sellacious_product_sellers AS ps ON ps.product_id = a.product_id'),
				array('inner', '#__sellacious_product_prices AS p ON p.product_id = a.product_id and p.seller_uid = ps.seller_uid'),
				array('inner', '#__sellacious_products AS pr ON pr.id = a.product_id'),
			);

			$filters['list.select'][] = 'DISTINCT a.product_id';

			if ($productID)
			{
				$filters['list.where'][] = 'a.product_id !=' . (int) $productID;
			}

			$filters['list.where'][] = 'a.group_alias IN (' . implode(",", $db->q($groups)) . ')';

			if ($helper->config->get('hide_zero_priced'))
			{
				$filters['list.where'][] = '(p.product_price > 0 OR ps.price_display > 0)';

			}

			if ($helper->config->get('hide_out_of_stock'))
			{
				$filters['list.where'][] = 'ps.stock + ps.over_stock > 0';
			}

			if (!$multi_seller)
			{
				$filters['list.where'][] = 'ps.seller_uid = ' . (int) $default_seller;
			}

			$filters['list.where'][] = '(pr.type = ' . implode(' OR pr.type = ', $db->quote($allowed)) . ')';

			$filters['list.where'][] = 'p.is_fallback = 1';
			$filters['list.start']   = 0;
			$filters['list.limit']   = $limit;

			// Check whether the product sellers are active
			$filters['list.join'][] = array('inner', '#__sellacious_sellers AS ss ON ss.user_id = ps.seller_uid');
			$filters['list.join'][]  = array('inner', '#__users AS pu ON pu.id = ss.user_id');
			$filters['list.where'][] = 'pu.block = 0';

			try
			{
				$items = $helper->relatedProduct->loadColumn($filters);
			}
			catch (Exception $e)
			{
				JLog::add($e->getMessage(), JLog::WARNING, 'jerror');

				$items = array();
			}

			if ($splCategory)
			{
				foreach ($items as $item)
				{
					$queryS = $db->getQuery(true);
					$queryS->select('id')->from('#__sellacious_seller_listing')
						->where('product_id = ' . $item . '')
						->where('category_id = ' . $splCategory . '');
					$splCatItem = $db->setQuery($queryS)->loadResult();
					if ($splCatItem)
					{
						array_push($relatedProductsSpl, $item);
					}
				}
			}
			$relatedIds .= implode(',', $items);
		}
	}
}
$relatedProductsAll = explode(',', $relatedIds);

if (($splCategory) && count($relatedProductsSpl) > 0)
{
	$relatedProductsAll    = array_filter($relatedProductsAll);
	$relatedProductsSpl    = array_unique(array_filter($relatedProductsSpl));
	$relatedProductsNormal = array_diff($relatedProductsAll, $relatedProductsSpl);
	shuffle($relatedProductsNormal);
	$relatedProductsAll = array_merge((array) $relatedProductsSpl, (array) $relatedProductsNormal);
	$relatedProductsAll = array_unique($relatedProductsAll);
}
else
{
	$relatedProductsAll = array_filter($relatedProductsAll);
	$relatedProductsAll = array_unique($relatedProductsAll);
	shuffle($relatedProductsAll);
}

if (($key = array_search($productID, $relatedProductsAll)) !== false)
{
	unset($relatedProductsAll[$key]);
}
$relatedProducts = array_slice($relatedProductsAll, 0, $limit);

if (empty($relatedProducts[0]))
{
	return;
}

require JModuleHelper::getLayoutPath('mod_sellacious_relatedproducts', $layout);
