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

class ModSellaciousSellerProducts
{
	public static function in_array_field($needle, $needle_field, $haystack, $strict = false)
	{
		if ($strict)
		{
			foreach ($haystack as $item)
				if (isset($item->$needle_field) && $item->$needle_field === $needle)
					return true;
		}
		else
		{
			foreach ($haystack as $item)
				if (isset($item->$needle_field) && $item->$needle_field == $needle)
					return true;
		}
		return false;
	}

	public static function getSellerInfo($seller_uid)
	{
		$db = JFactory::getDbo();
		$result = new stdClass;
		$select = $db->getQuery(true);
		$select->select('mobile')->from('#__sellacious_profiles')->where('user_id = ' . (int) $seller_uid);
		$mobile = $db->setQuery($select)->loadResult();

		if (!empty($mobile))
		{
			$result->seller_mobile = $mobile;
		}
		else
		{
			$result->seller_mobile = '(' . JText::_('MOD_SELLACIOUS_SELLERPRODUCTS_NO_NUMBER_GIVEN') . ')';
		}
		$seller_email = JFactory::getUser($seller_uid)->email;
		if (!empty($seller_email))
		{
			$result->seller_email = $seller_email;
		}
		else
		{
			$result->seller_email = '(' . JText::_('MOD_SELLACIOUS_SELLERPRODUCTS_NO_EMAIL_GIVEN') . ')';
		}

		return $result;
	}

	/**
	 * Get All Products List
	 *
	 * @param   \Joomla\Registry\Registry  $params  module parameters
	 *
	 * @return  stdClass[]
	 *
	 * @since   1.6.0
	 */
	public static function getSellerProducts(&$params)
	{
		$db     = JFactory::getDbo();
		$helper = SellaciousHelper::getInstance();
		$input  = JFactory::getApplication()->input;
		$option = $input->getString('option');
		$view   = $input->getString('view');
		$pCode  = $input->getString('p');

		$default_seller = $helper->config->get('default_seller', -1);
		$multi_seller   = $helper->config->get('multi_seller', 0);
		$multi_variant  = $helper->config->get('multi_variant', 0);
		$allowed        = $helper->config->get('allowed_product_type');
		$allow_package  = $helper->config->get('allowed_product_package');

		// Module params
		/** @var  Joomla\Registry\Registry $params */
		$limit               = (int) $params->get('count', '20');
		$showProductsBy      = $params->get('products_by', 'sid');
		$sellers             = $params->get('sellers', '');
		$excludeOthers       = (int) $params->get('exclude_on_detail', 1);

		$sellersArr          = array();
		$sellersArr          = array_unique(array_filter(array_map('intval',explode(",", $sellers))));

		if ($option == 'com_sellacious' && $view == 'product' && !empty($pCode))
		{
			$helper->product->parseCode($pCode, $p_id, $v_id, $s_uid);

			// Seller Id case
			if($showProductsBy == 'sid')
			{
				if ($excludeOthers)
				{
					$sellersArr    = array();
					$sellersArr[0] = $s_uid;
				}
				else
				{
					if (!in_array($s_uid, $sellersArr))
					{
						array_push($sellersArr, $s_uid);
					}
				}
			}
			// Seller Category Id case
			else
			{
				$s_cat_id  = $helper->seller->loadResult(array('list.select' => 'a.category_id', 'user_id' => $s_uid));
				if ($excludeOthers)
				{
					$sellersArr    = array();
					$sellersArr[0] = $s_cat_id;
				}
				else
				{
					if (!in_array($s_cat_id, $sellersArr))
					{
						array_push($sellersArr, $s_cat_id);
					}
				}
			}

		}

		$query = $db->getQuery(true);

		$query->select('a.*')
			->from($db->qn('#__sellacious_cache_products', 'a'))
			->where('a.is_selling = 1')
			->where('a.seller_active = 1')
			->where('a.listing_active = 1')
			->where('a.product_active = 1');

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

		if (count($sellersArr))
		{
			if($showProductsBy == 'sid')
			{
				$query->where('a.seller_uid IN (' . implode(', ', $db->q($sellersArr)) . ')');
			}
			else
			{
				$query->where('a.seller_catid IN (' . implode(', ', $db->q($sellersArr)) . ')');
			}
		}

		$seller_separate  = $multi_seller == 2;
		$variant_separate = $multi_variant == 2;

		$grouping = array('a.product_id');

		if ($multi_variant && $variant_separate)
		{
			$grouping[] = 'a.variant_id';
		}

		if ($multi_seller && $seller_separate)
		{
			$grouping[] = 'a.seller_uid';
		}

		$query->group($grouping);

		$query->order('a.spl_category_ids = ' . $db->q('') . ' ASC');
		$query->order('a.price_display ASC');
		$query->order('a.stock DESC');

		$dispatcher = $helper->core->loadPlugins();
		$dispatcher->trigger('onAfterBuildQuery', array('com_sellacious.module.sellerproducts', &$query));

		$db->setQuery($query, 0, $limit);

		try
		{
			$items = $db->loadObjectList();
		}
		catch (Exception $e)
		{
			JLog::add($e->getMessage(), JLog::WARNING, 'jerror');

			$items = array();
		}

		return $items;
	}
}
