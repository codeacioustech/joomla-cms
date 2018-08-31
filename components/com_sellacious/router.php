<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

jimport('sellacious.loader');

/**
 * Routing class from component
 *
 * @since   1.5.0
 *
 * @note   IN FUTURE VERSIONS THIS CLASS IS EXPECTED TO ALLOW USER-DEFINED ROUTING ALGORITHM USING ROUTER OVERRIDE CLASSES.
 */
class SellaciousRouter extends JComponentRouterBase
{
	/**
	 * @var   string
	 *
	 * @since   1.5.2
	 */
	protected $component = 'com_sellacious';

	/**
	 * @var   int
	 *
	 * @since   1.5.0
	 */
	protected $componentId;

	/**
	 * @var   \SellaciousHelper
	 *
	 * @since   1.6.0
	 */
	protected $helper;

	/**
	 * @var   \JDatabaseDriver
	 *
	 * @since   1.5.0
	 */
	protected $db;

	/**
	 * @var   array
	 *
	 * @since   1.5.0
	 */
	protected $lookup;

	/**
	 * @var   array
	 *
	 * @since   1.5.0
	 */
	protected $views = array();

	/**
	 * @var   Registry
	 *
	 * @since   1.5.2
	 */
	protected $viewSegments;

	/**
	 * @var   array
	 *
	 * @since   1.6.0
	 */
	protected $viewMap = array();

	/**
	 * Class constructor.
	 *
	 * @param   JApplicationCms  $app   Application-object that the router should use
	 * @param   JMenu            $menu  Menu-object that the router should use
	 *
	 * @throws  Exception
	 *
	 * @since   1.5.0
	 */
	public function __construct($app = null, $menu = null)
	{
		parent::__construct($app, $menu);

		$component         = JComponentHelper::getComponent($this->component);
		$this->componentId = $component->id;
		$this->db          = JFactory::getDbo();
		$this->helper      = SellaciousHelper::getInstance();

		$segments = $this->helper->config->get('frontend_sef', array());

		$this->viewSegments = new Registry($segments);

		$this->addView('addresses');
		$this->addView('cart', array('aio', 'cancelled', 'complete', 'empty', 'failed'));
		$this->addView('categories', array(), 'category_id');
		$this->addView('compare');
		$this->addView('downloads');
		$this->addView('license', array(), 'id');
		$this->addView('order', array('invoice', 'password', 'payment', 'print', 'receipt'), 'id');
		$this->addView('orders');
		$this->addView('product', array('modal', 'query'), 'p');
		$this->addView('products', array(), 'category_id');
		$this->addView('profile');
		$this->addView('register');
		$this->addView('search');
		$this->addView('seller', array('complete'), 'id');
		$this->addView('store', array(), 'id');
		$this->addView('stores');
		$this->addView('wishlist', array(), 'user_id');
		$this->addView('reviews');

		// Build viewMap only after all viewSegments have been initialised
		$this->viewMap = array_flip($this->viewSegments->flatten('/'));
	}

	/**
	 * Register a new view to the router
	 *
	 * @param   string    $name     The view name
	 * @param   string[]  $layouts  The layout names other than default
	 * @param   string    $key      The key name for the view items
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	protected function addView($name, $layouts = array(), $key = null)
	{
		array_unshift($layouts, 'default');

		$view = new stdClass;

		$view->name    = $name;
		$view->layouts = $layouts;
		$view->key     = $key;

		$this->views[$view->name] = $view;

		$this->viewSegments->def($name . '.default', $name);
	}

	/**
	 * Return an array of registered view objects
	 *
	 * @param   string  $name  The view name
	 *
	 * @return  stdClass  Selected item from registered view objects
	 *
	 * @since   1.5.0
	 */
	public function getView($name)
	{
		return isset($this->views[$name]) ? $this->views[$name] : null;
	}

	/**
	 * Return an custom segment for a registered view
	 *
	 * @param   array  $query  The url query
	 *
	 * @return  string  The sef segment
	 *
	 * @since   1.6.0
	 */
	public function getViewSegment(&$query)
	{
		$seg = null;

		// If we don't have a view, do not add view segment
		if (!isset($query['view']))
		{
			return $seg;
		}

		if (isset($query['layout']))
		{
			$key = $query['view'] . '.' . $query['layout'];

			if ($seg = $this->viewSegments->get($key))
			{
				unset($query['layout']);
			}
		}

		if (!$seg)
		{
			$key = $query['view'] . '.default';
			$seg = $this->viewSegments->get($key);
		}

		unset($query['view']);

		return $seg;
	}

	/**
	 * Generic method to preprocess a URL. This will try to obtain the appropriate menu Itemid for the specific query.
	 *
	 * @param   array  $query  An associative array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   1.5.0
	 */
	public function preprocess($query)
	{
		$vName = isset($query['view']) ? $query['view'] : null;
		$view  = $this->getView($vName);

		if (!$view)
		{
			return $query;
		}

		$lang   = isset($query['lang']) ? $query['lang'] : '*';
		$layout = isset($query['layout']) ? $query['layout'] : null;
		$key    = isset($view->key, $query[$view->key]) ? $query[$view->key] : null;
		$links  = array();

		if ($view->key)
		{
			if ($layout)
			{
				$links[] = 'index.php?option=' . $this->component . '&view=' . $vName . '&layout=' . $layout . '&' . $view->key . '=' . $key;
			}

			$links[] = 'index.php?option=' . $this->component . '&view=' . $vName . '&' . $view->key . '=' . $key;
		}
		elseif ($layout)
		{
			$links[] = 'index.php?option=' . $this->component . '&view=' . $vName . '&layout=' . $layout;
		}

		$links[] = 'index.php?option=' . $this->component . '&view=' . $vName;
		$links[] = 'index.php?option=' . $this->component . '&view=' . str_replace('com_', '', $this->component);
		$links[] = 'index.php?option=' . $this->component;

		foreach ($links as $link)
		{
			$keys = array('component_id' => $this->componentId, 'link' => $link, 'language' => array($lang, '*'));
			$item = $this->menu->getItems(array_keys($keys), array_values($keys), true);

			if (is_object($item))
			{
				$query['Itemid'] = $item->id;

				return $query;
			}
		}

		// Check if the active menuitem matches the requested language and this component
		$active = $this->menu->getActive();

		if ($active && $active->component === $this->component &&
			($lang === '*' || in_array($active->language, array('*', $lang)) || !JLanguageMultilang::isEnabled()))
		{
			$query['Itemid'] = $active->id;

			return $query;
		}

		// If not found, return language specific home link
		$default = $this->menu->getDefault($lang);

		if (!empty($default->id))
		{
			$query['Itemid'] = $default->id;
		}

		return $query;
	}

	/**
	 * Build method for URLs
	 *
	 * @param   array  &$query  Array of query elements
	 *
	 * @return  array  Array of URL segments
	 *
	 * @since   1.5.0
	 */
	public function build(&$query)
	{
		if (!isset($query['view']))
		{
			return array();
		}

		$item = $this->menu->getItem($query['Itemid']);

		// If we do not have a component menu item of our own, we cannot have a custom sef route
		if ($item->component != $this->component)
		{
			return array();
		}

		$view = $this->getView($query['view']);

		if (!$view)
		{
			// This is an unknown view
			return array();
		}

		// Remove parameters that are already in the menu item
		if (isset($item->query['view']) && $item->query['view'] === $query['view'])
		{
			unset($query['view']);

			if (isset($view->key, $item->query[$view->key], $query[$view->key]) && $item->query[$view->key] == $query[$view->key])
			{
				unset($query[$view->key]);
			}

			if (isset($item->query['layout'], $query['layout']) && $item->query['layout'] === $query['layout'])
			{
				unset($query['layout']);
			}
		}

		$segments = array();

		/**
		 * Perform SEF route only if -
		 * - Menu Item has not view [OR]
		 * - Menu Item has default view that we don't see as a view at all, equivalent to not having a view [OR]
		 * - The query has not view to be made as segment
		 *
		 * This is to prevent segments for view twice - menu item + query.
		 */
		if (!isset($item->query['view']) || $item->query['view'] == str_replace('com_', '', $this->component) || !isset($query['view']))
		{
			// Now build the segments for the specific view by calling the appropriate method if available
			$method   = 'get' . ucfirst($view->name) . 'Segments';
			$segments = is_callable(array($this, $method)) ? call_user_func_array(array($this, $method), array(&$query)) : array();
		}

		return $segments;
	}

	/**
	 * Parse method for URLs
	 * This method is meant to transform the human readable URL back into
	 * query parameters. It is only executed when SEF mode is switched on.
	 *
	 * @param   array  &$segments  The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @since   1.5.0
	 */
	public function parse(&$segments)
	{
		$active = $this->menu->getActive();
		$vars   = $active ? $active->query : array();

		// Remove default view parameter, its always override'able
		if (isset($vars['view']) && $vars['view'] == str_replace('com_', '', $this->component))
		{
			unset($vars['view']);
		}

		if (isset($vars['view']))
		{
			// We don't need to find the view, its set by menu.
		}
		elseif (array_key_exists($segments[0], $this->viewMap))
		{
			// We'll get view (and maybe layout) from the segments.
			$viewLayout = $this->viewMap[$segments[0]];

			@list($viewName, $layoutName) = explode('/', $viewLayout, 2);

			$vars['view'] = $viewName;

			$view = $this->getView($viewName);

			if (!isset($vars['layout']) && isset($layoutName) && in_array($layoutName, $view->layouts))
			{
				$vars['layout'] = $layoutName;
			}

			array_shift($segments);
		}
		else
		{
			// Special processing for the categories, products and product urls, they don't have a view segment.
			$query = $this->parseCategoriesSegments($segments);
			$vars  = array_merge($vars, $query);

			return $vars;
		}

		// Now build the segments for the specific view by calling the appropriate method if available and needed
		if (count($segments))
		{
			$method = 'parse' . ucfirst($vars['view']) . 'Segments';
			$query  = is_callable(array($this, $method)) ? call_user_func_array(array($this, $method), array(&$segments)) : array();

			$vars = array_merge($vars, $query);
		}

		return $vars;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getAddressesSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getCartSegments(&$query)
	{
		$segments = (array) $this->getViewSegment($query);

		if (isset($query['layout']))
		{
			$segments[] = $query['layout'];

			unset($query['layout']);
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getCompareSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getDownloadsSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getLicenseSegments(&$query)
	{
		$segments = (array) $this->getViewSegment($query);

		$view = $this->getView('license');

		if (isset($query[$view->key]))
		{
			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('a.alias')
					->from($this->db->qn('#__sellacious_licenses', 'a'))
					->where('a.id = ' . (int) $query[$view->key]);

				$value = $this->db->setQuery($sql)->loadResult();

				if ($value)
				{
					$segments[] = urlencode($value);

					unset($query[$view->key]);
				}
			}
			catch (Exception $e)
			{
				// Ignore, the query parameter remains and no segments are added
			}
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getOrderSegments(&$query)
	{
		$segments = (array) $this->getViewSegment($query);

		$found = false;
		$view  = $this->getView('order');

		if (isset($query[$view->key]))
		{
			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('a.order_number')
					->from($this->db->qn('#__sellacious_orders', 'a'))
					->where('a.id = ' . (int) $query[$view->key]);

				$value = $this->db->setQuery($sql)->loadResult();

				if ($value)
				{
					$found = true;

					$segments[] = urlencode($value);

					unset($query[$view->key]);
				}
			}
			catch (Exception $e)
			{
				// Ignore, the query parameter remains and no segments are added
			}
		}

		// Process layout parameter only if an order number was found
		if ($found && isset($query['layout']))
		{
			$segments[] = $query['layout'];

			unset($query['layout']);
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getOrdersSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getProductsSegments(&$query)
	{
		$segments = array();

		$view = $this->getView('products');

		if (isset($query[$view->key]))
		{
			if ($query[$view->key] == 1)
			{
				unset($query[$view->key]);
			}
			else
			{
				try
				{
					$sql = $this->db->getQuery(true);

					$sql->select('a.path')
						->from($this->db->qn('#__sellacious_categories', 'a'))
						->where('a.id = ' . (int) $query[$view->key]);

					$path = $this->db->setQuery($sql)->loadResult();

					if ($path)
					{
						$segments = explode('/', $path);

						// unset($query['view']);
						unset($query[$view->key]);
					}
				}
				catch (Exception $e)
				{
					// Ignore, the query parameter remains and no segments are added
				}
			}
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getProfileSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getRegisterSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getSearchSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getSellerSegments(&$query)
	{
		$segments = (array) $this->getViewSegment($query);

		$found = false;
		$view  = $this->getView('seller');

		if (isset($query[$view->key]))
		{
			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('s.code')->from($this->db->qn('#__sellacious_sellers', 's'))->where('s.user_id = ' . (int) $query[$view->key]);

				$value = $this->db->setQuery($sql)->loadResult();

				if ($value)
				{
					$found      = true;
					$segments[] = urlencode($value);

					unset($query[$view->key]);
				}
			}
			catch (Exception $e)
			{
				// Ignore, the query parameter remains and no segments are added
			}
		}

		// Process layout parameter only if an order number was found
		if ($found && isset($query['layout']))
		{
			$segments[] = $query['layout'];

			unset($query['layout']);
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getStoreSegments(&$query)
	{
		$segments = (array) $this->getViewSegment($query);

		$view = $this->getView('store');

		if (isset($query[$view->key]))
		{
			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('s.code')->from($this->db->qn('#__sellacious_sellers', 's'))->where('s.user_id = ' . (int) $query[$view->key]);

				$value = $this->db->setQuery($sql)->loadResult();

				if ($value)
				{
					$segments[] = urlencode($value);

					unset($query[$view->key]);
				}
			}
			catch (Exception $e)
			{
				// Ignore, the query parameter remains and no segments are added
			}
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getWishlistSegments(&$query)
	{
		return (array) $this->getViewSegment($query);
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getCategoriesSegments(&$query)
	{
		$segments = array();

		$view = $this->getView('categories');

		if (isset($query[$view->key]))
		{
			if ($query[$view->key] == 1)
			{
				unset($query[$view->key]);
			}
			else
			{
				try
				{
					$sql = $this->db->getQuery(true);

					$sql->select('a.path')
						->from($this->db->qn('#__sellacious_categories', 'a'))
						->where('a.id = ' . (int) $query[$view->key]);

					$path = $this->db->setQuery($sql)->loadResult();

					if ($path)
					{
						$segments = explode('/', $path);

						unset($query['view']);
						unset($query[$view->key]);
					}
				}
				catch (Exception $e)
				{
					// Ignore, the query parameter remains and no segments are added
				}
			}
		}
		// B/C
		elseif (isset($query['parent_id']))
		{
			if ($query['parent_id'] == 1)
			{
				unset($query['parent_id']);
			}
			else
			{
				try
				{
					$sql = $this->db->getQuery(true);

					$sql->select('a.path')
						->from($this->db->qn('#__sellacious_categories', 'a'))
						->where('a.id = ' . (int) $query['parent_id']);

					$path = $this->db->setQuery($sql)->loadResult();

					if ($path)
					{
						$segments = explode('/', $path);

						unset($query['view']);
						unset($query['parent_id']);
					}
				}
				catch (Exception $e)
				{
					// Ignore, the query parameter remains and no segments are added
				}
			}
		}

		return $segments;
	}

	/**
	 * Get the sef route segments for the given query URL
	 *
	 * @param   array  $query  The URL query parameters
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function getProductSegments(&$query)
	{
		$segments = array();

		$view  = $this->getView('product');
		$found = false;

		if (isset($query[$view->key]))
		{
			if ($this->helper->product->parseCode($query[$view->key], $productId, $variantId, $sellerUid))
			{
				$sql = $this->db->getQuery(true);

				$searchParent = $this->helper->config->get('category_sef_prefix');

				if ($searchParent)
				{
					$sql->select('a.path')
						->from($this->db->qn('#__sellacious_categories', 'a'))
						->order('a.lft ASC');

					$sql->join('inner', $this->db->qn('#__sellacious_product_categories', 'pc') . ' ON pc.category_id = a.id')
						->where('pc.product_id = ' . (int) $productId);

					$path = $this->db->setQuery($sql)->loadResult();

					$segments = explode('/', $path);
				}

				try
				{
					if ($variantId && $this->helper->config->get('multi_variant'))
					{
						$sql->clear()->select('a.alias')
							->from($this->db->qn('#__sellacious_variants', 'a'))
							->where('a.id = ' . (int) $variantId)
							->where('a.product_id = ' . (int) $productId);

						$value = $this->db->setQuery($sql)->loadResult();

						if ($value)
						{
							$found      = true;
							$segments[] = urlencode($value);

							unset($query['view'], $query[$view->key]);
						}
					}

					if (!$found)
					{
						$sql->clear()->select('a.alias')
							->from($this->db->qn('#__sellacious_products', 'a'))
							->where('a.id = ' . (int) $productId);

						$value = $this->db->setQuery($sql)->loadResult();

						if ($value)
						{
							$found      = true;
							$segments[] = urlencode($value);

							unset($query['view'], $query[$view->key]);
						}
					}

					if ($found && $sellerUid && $this->helper->config->get('multi_seller'))
					{
						$query['s'] = $sellerUid;
					}
				}
				catch (Exception $e)
				{
					// Optional, display only segments will not be added on exception, URL still works
				}
			}
		}

		return $segments;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseCartSegments(&$segments)
	{
		$vars = array();
		$view = $this->getView('cart');

		if (count($segments) && in_array($segments[0], $view->layouts))
		{
			$vars['layout'] = array_shift($segments);
		}

		return $vars;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseLicenseSegments(&$segments)
	{
		$vars = array();
		$view = $this->getView('license');

		if (count($segments))
		{
			$license = array_shift($segments);

			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('a.id')->from($this->db->qn('#__sellacious_licenses', 'a'))->where('a.alias = ' . $this->db->q($license));

				$lId = $this->db->setQuery($sql)->loadResult();

				if ($lId)
				{
					$vars[$view->key] = $lId;
				}
			}
			catch (Exception $e)
			{
				// Ignore
			}
		}

		return $vars;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseOrderSegments(&$segments)
	{
		$vars = array();
		$view = $this->getView('order');

		if (count($segments))
		{
			$oNum = array_shift($segments);

			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('a.id')->from($this->db->qn('#__sellacious_orders', 'a'))->where('a.order_number = ' . $this->db->q($oNum));

				$orderId = $this->db->setQuery($sql)->loadResult();

				if ($orderId)
				{
					$vars[$view->key] = $orderId;
				}
			}
			catch (Exception $e)
			{
				// Todo: Throw 404
				// Ignore
			}
		}

		if (count($segments) && in_array($segments[0], $view->layouts))
		{
			$vars['layout'] = array_shift($segments);
		}

		return $vars;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseSellerSegments(&$segments)
	{
		$vars = array();
		$view = $this->getView('seller');

		if (count($segments))
		{
			$seller = array_shift($segments);

			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('a.user_id')->from($this->db->qn('#__sellacious_sellers', 'a'))->where('a.code = ' . $this->db->q($seller));

				$sId = $this->db->setQuery($sql)->loadResult();

				if ($sId)
				{
					$vars[$view->key] = $sId;
				}
			}
			catch (Exception $e)
			{
				// Todo: Throw 404
				// Ignore
			}
		}

		if (count($segments) && in_array($segments[0], $view->layouts))
		{
			$vars['layout'] = array_shift($segments);
		}

		return $vars;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseStoreSegments(&$segments)
	{
		$vars = array();
		$view = $this->getView('store');

		if (count($segments))
		{
			$seller = array_shift($segments);

			try
			{
				$sql = $this->db->getQuery(true);

				$sql->select('a.user_id')->from($this->db->qn('#__sellacious_sellers', 'a'))->where('a.code = ' . $this->db->q($seller));

				$sId = $this->db->setQuery($sql)->loadResult();

				if ($sId)
				{
					$vars[$view->key] = $sId;
				}
			}
			catch (Exception $e)
			{
				// Todo: Throw 404
				// Ignore
			}
		}

		return $vars;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseProductSegments(&$segments)
	{
		return $this->parseCategoriesSegments($segments);
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseProductsSegments(&$segments)
	{
		$vars = $this->parseCategoriesSegments($segments);

		$vars['view'] = 'products';

		return $vars;
	}

	/**
	 * Parse the sef route segments for the given query URL
	 *
	 * @param   array  $segments  The SEF route segments
	 *
	 * @return  array
	 *
	 * @since   1.5.0
	 */
	protected function parseCategoriesSegments(&$segments)
	{
		$vars  = array();
		$catid = null;

		// Match segments to find category id
		if (count($segments))
		{
			$parts = array();
			$paths = array();

			foreach ($segments as $segment)
			{
				$parts[] = $segment;
				$paths[] = implode('/', $parts);
			}

			try
			{
				$sql = $this->db->getQuery(true);
				$sql->select('a.id, a.path, a.level')
					->from($this->db->qn('#__sellacious_categories', 'a'))
					->where('a.path IN (' . implode(', ', $this->db->q($paths)) . ')')
					->order('a.level DESC');

				$category = $this->db->setQuery($sql)->loadObject();

				if ($category)
				{
					$catid    = $category->id;
					$segments = array_slice($segments, $category->level);
				}
			}
			catch (Exception $e)
			{
				return $vars;
			}
		}

		if (count($segments) == 0)
		{
			$view = $this->getView('categories');

			$vars['view']     = 'categories';
			$vars[$view->key] = $catid ?: 1;

			return $vars;
		}

		// Find a product/variant
		$variantId    = 0;
		$searchParent = $this->helper->config->get('category_sef_prefix');

		if ($searchParent && !$catid)
		{
			return $vars;
		}

		try
		{
			$sql = $this->db->getQuery(true);
			$sql->select('a.id')
				->from($this->db->qn('#__sellacious_products', 'a'))
				->where('a.alias = ' . $this->db->q($segments[0]));

			if ($catid)
			{
				$condition = 'pc.product_id = a.id AND pc.category_id = ' . (int) $catid;

				$sql->join('inner', $this->db->qn('#__sellacious_product_categories', 'pc') . ' ON ' . $condition);
			}

			$productId = $this->db->setQuery($sql)->loadResult();
		}
		catch (Exception $e)
		{
			return $vars;
		}

		if (!$productId && $this->helper->config->get('multi_variant'))
		{
			try
			{
				$sql = $this->db->getQuery(true);
				$sql->select('a.id, a.product_id')
					->from($this->db->qn('#__sellacious_variants', 'a'))
					->where('a.alias = ' . $this->db->q($segments[0]));

				if ($catid)
				{
					$condition = 'pc.product_id = a.product_id AND pc.category_id = ' . (int) $catid;

					$sql->join('inner', $this->db->qn('#__sellacious_product_categories', 'pc') . ' ON ' . $condition);
				}

				$obj = $this->db->setQuery($sql)->loadObject();

				if ($obj)
				{
					$productId = $obj->product_id;
					$variantId = $obj->id;
				}
			}
			catch (Exception $e)
			{
				return $vars;
			}
		}

		if ($productId)
		{
			array_shift($segments);

			try
			{
				$view      = $this->getView('product');
				$sellerUid = $this->app->input->getInt('s');

				$vars['view']     = 'product';
				$vars[$view->key] = $this->helper->product->getCode($productId, $variantId, $sellerUid);
			}
			catch (Exception $e)
			{
				return $vars;
			}
		}

		return $vars;
	}
}

/**
 * Sellacious router functions
 *
 * These functions are proxies for the new router interface for old SEF extensions.
 *
 * @param   array  &$query  An array of URL arguments
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @throws  Exception
 *
 * @since   1.5.0
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function sellaciousBuildRoute(&$query)
{
	$app    = JFactory::getApplication();
	$router = new SellaciousRouter($app, $app->getMenu());

	return $router->build($query);
}

/**
 * Parse the segments of a URL.
 *
 * This function is a proxy for the new router interface for old SEF extensions.
 *
 * @param   array  $segments  The segments of the URL to parse.
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @throws  Exception
 *
 * @since   1.5.0
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function sellaciousParseRoute($segments)
{
	$app    = JFactory::getApplication();
	$router = new SellaciousRouter($app, $app->getMenu());

	return $router->parse($segments);
}
