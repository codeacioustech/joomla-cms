<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
namespace Sellacious\Import;

// no direct access
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Import utility class for products
 *
 * @since   1.4.7
 */
class ProductsImporter extends AbstractImporter
{
	/**
	 * The temporary table name that would hold the staging data from CSV for import processing
	 *
	 * @var    string
	 *
	 * @since   1.5.0
	 */
	public $importTable = '#__sellacious_import_temp_products';

	/**
	 * Get the columns for the import CSV template for the given categories if any, or a basic one without any specifications
	 *
	 * @return  string[]
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	public function getColumns()
	{
		$columns = array(
			'PRODUCT_UNIQUE_ALIAS',
			'PRODUCT_PARENT_KEY',
			'PRODUCT_TITLE',
			'PRODUCT_TYPE',
			'PRODUCT_SKU',
			'MFG_ASSIGNED_SKU',
			'PRODUCT_SUMMARY',
			'PRODUCT_DESCRIPTION',
			'PRODUCT_CURRENT_STOCK',
			'PRODUCT_OVER_STOCK_SALE_LIMIT',
			'PRODUCT_RESERVED_STOCK',
			'PRODUCT_STOCK_SOLD',
			'PRODUCT_FEATURE_1',
			'PRODUCT_FEATURE_2',
			'PRODUCT_FEATURE_3',
			'PRODUCT_FEATURE_4',
			'PRODUCT_FEATURE_5',
			'PRODUCT_STATE',
			'PRODUCT_ORDERING',
			'LENGTH',
			'WIDTH',
			'HEIGHT',
			'WEIGHT',
			'SHIPPING_LENGTH',
			'SHIPPING_WIDTH',
			'SHIPPING_HEIGHT',
			'SHIPPING_WEIGHT',
			'VOLUMETRIC_WEIGHT',
			'EPRODUCT_DELIVERY_MODE',
			'EPRODUCT_DOWNLOAD_LIMIT',
			'EPRODUCT_DOWNLOAD_PERIOD',
			'EPRODUCT_PREVIEW_URL',
			'PRODUCT_LISTING_TYPE',
			'PRODUCT_CONDITION',
			'WHATS_IN_BOX',
			'MIN_ORDER_QTY',
			'MAX_ORDER_QTY',
			'IS_FLAT_SHIPPING',
			'FLAT_SHIPPING_FEE',
			'ORDER_RETURN_DAYS',
			'ORDER_RETURN_TNC',
			'ORDER_EXCHANGE_DAYS',
			'ORDER_EXCHANGE_TNC',
			'MANUFACTURER_NAME',
			'MANUFACTURER_USERNAME',
			'MANUFACTURER_CODE',
			'MANUFACTURER_COMPANY',
			'MANUFACTURER_EMAIL',
			'SELLER_NAME',
			'SELLER_USERNAME',
			'SELLER_EMAIL',
			'SELLER_BUSINESS_NAME',
			'SELLER_CODE',
			'SELLER_MOBILE',
			'SELLER_WEBSITE',
			'SELLER_STORE_NAME',
			'SELLER_STORE_ADDRESS',
			'STORE_LATITUDE_LONGITUDE',
			'PRODUCT_META_KEY',
			'PRODUCT_META_DESCRIPTION',
			'LISTING_START_DATE',
			'LISTING_END_DATE',
			'PRICE_DISPLAY',
			'PRICE_CURRENCY',
			'PRICE_LIST_PRICE',
			'PRICE_COST_PRICE',
			'PRICE_MARGIN',
			'PRICE_MARGIN_PERCENT',
			'PRICE_AMOUNT_FLAT',
			'VARIANT_UNIQUE_ALIAS',
			'VARIANT_TITLE',
			'VARIANT_SKU',
			'VARIANT_FEATURE_1',
			'VARIANT_FEATURE_2',
			'VARIANT_FEATURE_3',
			'VARIANT_FEATURE_4',
			'VARIANT_FEATURE_5',
			'VARIANT_CURRENT_STOCK',
			'VARIANT_OVER_STOCK_SALE_LIMIT',
			'VARIANT_RESERVED_STOCK',
			'VARIANT_STOCK_SOLD',
			'VARIANT_PRICE_ADD',
			'VARIANT_PRICE_IS_PERCENT',
			'IMAGE_URL',
			'IMAGE_FOLDER',
			'IMAGE_FILENAME',
			'PACKAGE_ITEMS',
			'RELATED_PRODUCT_GROUPS',
		);

		// Remove seller options if multi-seller is disabled
		if (!$this->helper->config->get('multi_seller'))
		{
			$columns = array_diff($columns, array(
				'SELLER_NAME',
				'SELLER_USERNAME',
				'SELLER_EMAIL',
				'SELLER_BUSINESS_NAME',
				'SELLER_CODE',
				'SELLER_MOBILE',
				'SELLER_WEBSITE',
				'SELLER_STORE_NAME',
				'SELLER_STORE_ADDRESS',
			));
		}

		// Remove variant options if multi-variant is disabled
		if (!$this->helper->config->get('multi_variant'))
		{
			$columns = array_diff($columns, array(
				'VARIANT_UNIQUE_ALIAS',
				'VARIANT_TITLE',
				'VARIANT_SKU',
				'VARIANT_FEATURE_1',
				'VARIANT_FEATURE_2',
				'VARIANT_FEATURE_3',
				'VARIANT_FEATURE_4',
				'VARIANT_FEATURE_5',
				'VARIANT_CURRENT_STOCK',
				'VARIANT_OVER_STOCK_SALE_LIMIT',
				'VARIANT_RESERVED_STOCK',
				'VARIANT_STOCK_SOLD',
				'VARIANT_PRICE_ADD',
				'VARIANT_PRICE_IS_PERCENT',
			));
		}

		$priceRows = 0;

		if ($this->helper->config->get('pricing_model') == 'advance')
		{
			$priceRows = $this->getOption('price_rows', 2);
		}

		for ($p = 1; $p <= $priceRows; $p++)
		{
			$columns[] = 'PRICE_' . $p . '_LIST_PRICE';
			$columns[] = 'PRICE_' . $p . '_COST_PRICE';
			$columns[] = 'PRICE_' . $p . '_MARGIN';
			$columns[] = 'PRICE_' . $p . '_MARGIN_PERCENT';
			$columns[] = 'PRICE_' . $p . '_AMOUNT_FLAT';
			$columns[] = 'PRICE_' . $p . '_START_DATE';
			$columns[] = 'PRICE_' . $p . '_END_DATE';
			$columns[] = 'PRICE_' . $p . '_MIN_QUANTITY';
			$columns[] = 'PRICE_' . $p . '_MAX_QUANTITY';
			$columns[] = 'PRICE_' . $p . '_CLIENT_CATEGORIES';
		}

		$catRows = $this->getOption('category_rows', 2);

		$columns[] = 'PRODUCT_CATEGORIES';

		for ($p = 1; $p <= $catRows; $p++)
		{
			$columns[] = 'CATEGORY_' . $p;
		}

		$columns[] = 'SPECIAL_CATEGORIES';

		for ($p = 1; $p <= $catRows; $p++)
		{
			$columns[] = 'SPLCATEGORY_' . $p;
		}

		// Let the plugins add custom columns
		$dispatcher = $this->helper->core->loadPlugins();
		$dispatcher->trigger('onFetchImportColumns', array('com_importer.import.products', &$columns, $this));

		$categories = $this->getOption('categories', array());

		// Add specification fields if requested
		if (is_array($categories))
		{
			// If no category selected we use all product categories
			if (count($categories) == 0)
			{
				$filter     = array(
					'list.select' => 'a.id',
					'list.where'  => 'a.type LIKE ' . $this->db->q($this->db->escape('product/', true) . '%', false),
					'state'       => 1,
				);
				$categories = $this->helper->category->loadColumn($filter);
			}

			$fieldsIds   = $this->helper->category->getFields($categories, array('core', 'variant'), true);
			$specsFields = $this->helper->field->loadObjectList(array('list.select' => 'a.id, a.title', 'id' => $fieldsIds, 'state' => 1));

			foreach ($specsFields as $specsField)
			{
				$columns[] = 'SPEC_' .  $specsField->id . '_' . strtoupper(preg_replace('/[^0-9a-z]+/i', '_', $specsField->title));
			}
		}

		return array_values($columns);
	}

	/**
	 * Get the additional columns for the records which are required for the import utility system
	 *
	 * @return  string[]
	 *
	 * @throws  \Exception
	 *
	 * @since   1.5.0
	 */
	public function getSysColumns()
	{
		$columns = array(
			'x__parent_id',
			'x__product_id',
			'x__variant_id',
			'x__manufacturer_uid',
			'x__seller_uid',
			'x__category_ids',
			'x__spl_category_ids',
			'x__specifications',
			'x__psx_id',
			'x__vsx_id',
		);

		return $columns;
	}

	/**
	 * Method to apply column alias for the uploaded CSV. This is useful if the CSV column headers do not match the prescribed names
	 *
	 * @param   array  $aliases  The column alias array. [column => alias]
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	public function setColumnsAlias($aliases)
	{
		$this->setOption('categories', array());

		parent::setColumnsAlias($aliases);
	}

	/**
	 * Import the records from CSV that was earlier loaded
	 *
	 * @return  bool
	 *
	 * @since   1.4.7
	 *
	 * @see     load()
	 */
	public function import()
	{
		try
		{
			// Check file pointer
			if (!$this->fp)
			{
				throw new \RuntimeException(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_ERROR_FILE_NOT_LOADED'));
			}

			// Check headers, if translated one is not available try using actual CSV header
			if (!$this->fields)
			{
				$this->fields = array_map('strtolower', $this->headers);
			}

			$this->check($this->fields);

			// Mark the start of process
			$this->timer->start(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_START_FILENAME', basename($this->filename)));

			// Build a temporary table from CSV
			$this->createTemporaryTable();

			// Let the plugins pre-process the table and perform any preparation task
			$this->dispatcher->trigger('onBeforeImport', array('com_importer.import.products', $this));

			// Process the batch
			$this->processBatch();

			// Let the plugins post-process the record and perform any relevant task
			$this->dispatcher->trigger('onAfterImport', array('com_importer.import.products', $this));

			// Rebuild any nested set tree involved
			$this->timer->stop(\JText::_('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_REBUILD_NESTED_TABLE'));

			/** @var  \JTableNested  $table */
			$table = $this->helper->category->getTable();
			$table->rebuild();

			$table = $this->helper->splCategory->getTable();
			$table->rebuild();

			// Re-sync category menu
			if ($this->helper->config->get('category_menu_sync', null, 'plg_system_sellaciousimporter'))
			{
				$this->helper->category->syncMenu();
			}

			$this->timer->log(\JText::_('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_GENERATING_OUTPUT_CSV'));

			$this->outputCsv();

			// Remove the temporary table
			$this->db->dropTable($this->importTable, true);

			// Mark the end of process
			$this->timer->stop(\JText::_('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_FINISHED'));

			return true;
		}
		catch (\Exception $e)
		{
			// Mark the unexpected termination of process
			$this->timer->interrupt(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_INTERRUPTED', $e->getMessage()));

			$this->timer->log(\JText::_('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_GENERATING_OUTPUT_CSV'));

			$this->outputCsv();

			// Remove the temporary table
			$this->db->dropTable($this->importTable, true);

			return false;
		}
	}

	/**
	 * Method to check whether the CSV columns are importable.
	 *
	 * @param   array  $fields  The alias processed column list
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function check($fields)
	{
	}

	/**
	 * Perform the initial processing of the temporary table before actual import begins.
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.5.0
	 */
	protected function processTemporaryTable()
	{
		// Update product id if already exists in db by alias if not empty (plugins may have already populated it)
		$query = $this->db->getQuery(true);

		$query->update($this->db->qn($this->importTable, 't'))
			->set('t.x__product_id = a.id')
			->join('left', $this->db->qn('#__sellacious_products', 'a') . ' ON t.product_unique_alias = a.alias')
			->where('t.x__product_id = 0');

		$this->db->setQuery($query)->execute();

		// Update empty product type if product already exists in db or set default to physical
		$query = $this->db->getQuery(true);

		$query->update($this->db->qn($this->importTable, 't'))
			->set("t.product_type = CASE COALESCE(a.type, '') WHEN '' THEN 'physical' ELSE a.type END")
			->join('left', $this->db->qn('#__sellacious_products', 'a') . ' ON t.x__product_id = a.id')
			->where("(t.product_type = '' OR t.product_type IS NULL)");

		$this->db->setQuery($query)->execute();

		// Update seller if multi-seller is disabled. When multi-seller is ON, seller is mandatory
		if (!$this->helper->config->get('multi_seller'))
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->qn($this->importTable, 't'))
				->set('t.x__seller_uid = ' . (int) $this->helper->config->get('default_seller'));

			$this->db->setQuery($query)->execute();
		}

		return true;
	}

	/**
	 * Convert the human readable text values from the import CSV to database friendly values to be saved.
	 *
	 * @param   \stdClass  $obj  The record from the CSV import table
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.5.1
	 */
	protected function translate($obj)
	{
		$booleans = array('TRUE', 'YES', '1', 'PUBLISHED', 'ACTIVE', 'ENABLED');

		if (isset($obj->product_listing_type))
		{
			$obj->product_listing_type = array_search(strtoupper($obj->product_listing_type), array('', 'NEW', 'USED', 'REFURBISHED'));
		}

		if (isset($obj->product_condition))
		{
			$obj->product_condition = array_search(strtoupper($obj->product_condition), array('', 'LIKE NEW', 'AVERAGE', 'GOOD', 'POOR'));
		}

		if (isset($obj->price_display))
		{
			$obj->price_display = array_search(strtoupper($obj->price_display), array('PRICE', 'CALL', 'EMAIL', 'QUERY FORM'));
		}

		if (isset($obj->is_flat_shipping))
		{
			$obj->is_flat_shipping = in_array(strtoupper($obj->is_flat_shipping), $booleans) ? 1 : 0;
		}

		if (isset($obj->price_margin_percent))
		{
			$obj->price_margin_percent = in_array(strtoupper($obj->price_margin_percent), $booleans) ? 1 : 0;
		}

		if (isset($obj->product_state))
		{
			$obj->product_state = in_array(strtoupper($obj->product_state), $booleans) ? 1 : 0;
		}

		if (isset($obj->variant_price_is_percent))
		{
			$obj->variant_price_is_percent = in_array(strtoupper($obj->variant_price_is_percent), $booleans) ? 1 : 0;
		}

		// Process the advance prices
		foreach ($obj as $key => $value)
		{
			if (isset($value) && preg_match('/^price_(\d+)_margin_percent$/', $key))
			{
				$obj->$key = in_array(strtoupper($obj->$key), $booleans) ? 1 : 0;
			}
		}
	}

	/**
	 * Method to import a single record obtained from the CSV
	 *
	 * @param   \stdClass  $obj  The record to be imported into sellacious
	 *
	 * @return  bool  Whether the record was imported successfully
	 *
	 * @since   1.4.7
	 */
	protected function processRecord($obj)
	{
		// Order of saving following items is important, do not randomly move up-down unless very sure
		try
		{
			// Specifications
			if ($obj->x__specifications)
			{
				$fields = json_decode($obj->x__specifications, true);
			}
			else
			{
				$fields = $this->extractSpecifications($obj);

				$obj->x__specifications = json_encode($fields);
			}

			// Categories
			if ($obj->x__category_ids)
			{
				$categories = json_decode($obj->x__category_ids, true);
			}
			else
			{
				$categories = $this->extractCategories($obj, $fields);

				$obj->x__category_ids = json_encode($categories);
			}

			// Special Categories
			if ($obj->x__spl_category_ids)
			{
				$splCategories = json_decode($obj->x__spl_category_ids, true);
			}
			else
			{
				$splCategories = $this->extractSplCategories($obj);

				$obj->x__spl_category_ids = json_encode($splCategories);
			}

			// Seller. If multi-seller is off, this will be set already
			if (!$obj->x__seller_uid)
			{
				$obj->x__seller_uid = $this->saveSeller($obj);
			}

			// Manufacturer
			if (!$obj->x__manufacturer_uid)
			{
				$obj->x__manufacturer_uid = $this->saveManufacturer($obj);
			}

			// Variant
			$multiVariant = $this->helper->config->get('multi_variant');

			// Product
			$this->saveProduct($obj, $categories);

			if ($multiVariant)
			{
				$this->saveVariant($obj);
			}

			// Product XReferences
			$this->saveRelatedGroups($obj);
			$this->saveTypeAttributes($obj);

			$this->saveProductSellerXref($obj);

			$this->saveSellerAttributesByType($obj);

			if ($multiVariant)
			{
				$this->saveVariantSellerXref($obj);
			}

			// Listing
			$this->saveSellerListing($obj, $splCategories);

			// Prices
			$this->savePrices($obj);

			// Save Specifications
			$this->saveSpecifications($obj, $fields);

			// Save Image (single allowed as of now)
			$this->saveImageUrl($obj);
			$this->saveImage($obj);

			if ($obj->product_type == 'package')
			{
				$this->savePackageItems($obj);
			}

			return true;
		}
		catch (\Exception $e)
		{
			$this->timer->log($e->getMessage());

			return false;
		}
	}

	/**
	 * Extract the specs columns from the record and clear them from the row
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  array
	 *
	 * @since   1.4.7
	 */
	protected function extractSpecifications($obj)
	{
		$values = array();

		static $fields = array();

		foreach ($obj as $key => $value)
		{
			if (!empty($value) && substr($key, 0, 5) == 'spec_')
			{
				list(, $pk) = explode('_', $key);

				if (isset($fields[$pk]))
				{
					$field = $fields[$pk];
				}
				else
				{
					$field = $this->helper->field->loadObject(array('list.select' => 'a.id, a.params', 'id' => $pk));

					if ($field && $field->params)
					{
						$field->params = new Registry($field->params);

						$fields[$pk] = $field;
					}
				}

				if ($field)
				{
					// JSonEncode if multiple, any custom field using JSON should do this already
					if ($field->params->get('multiple') == 'true')
					{
						$value = preg_split('#(?<!\\\);#', $value, -1, PREG_SPLIT_NO_EMPTY);
					}

					$values[$pk] = $value;
				}
			}
		}

		return $values;
	}

	/**
	 * Extract the categories from the record
	 *
	 * @param   \stdClass  $obj     The entire row from import table
	 * @param   array      $fields  The specification form fields
	 *
	 * @return  \stdClass[]
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function extractCategories($obj, $fields)
	{
		$catPaths = array();

		// Extract the categories from split columns
		foreach ($obj as $key => $value)
		{
			if (preg_match('/^category_(\d+)$/', $key, $matches))
			{
				list(, $pi)    = $matches;
				$catPaths[$pi] = $value;
			}
		}

		if (!empty($obj->product_categories))
		{
			$catParts = preg_split('#(?<!\\\);#', $obj->product_categories, -1, PREG_SPLIT_NO_EMPTY);
			$catPaths = array_merge($catPaths, $catParts);
		}

		$catNames   = array_unique(array_filter($catPaths, 'trim'));
		$categories = array();

		foreach ($catNames as $catName)
		{
			try
			{
				$type   = 'product/' . strtolower($obj->product_type);
				$create = $this->getOption('create.categories', 0);
				$catId  = Element\Category::getId($catName, $type, $create, array_keys($fields));
			}
			catch (\Exception $e)
			{
				throw new \Exception(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_ERROR_MISSING_CATEGORY', $catName, $e->getMessage()));
			}

			$categories[] = $catId;
		}

		return $categories;
	}

	/**
	 * Extract the special categories from the record
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  int[]
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function extractSplCategories($obj)
	{
		$catPaths = array();

		// Extract the categories from split columns
		foreach ($obj as $key => $value)
		{
			if ($value !== '' && preg_match('/^splcategory_(\d+)$/', $key, $matches))
			{
				list(, $pi)    = $matches;
				$catPaths[$pi] = $value;
			}
		}

		if (!empty($obj->special_categories))
		{
			$catParts = preg_split('#(?<!\\\);#', $obj->special_categories, -1, PREG_SPLIT_NO_EMPTY);
			$catPaths = array_merge($catPaths, $catParts);
		}

		$catNames   = array_unique(array_filter($catPaths, 'trim'));
		$categories = array();

		foreach ($catNames as $catName)
		{
			try
			{
				$create = $this->getOption('create.special_categories', 0);
				$catId  = Element\SplCategory::getId($catName, $create);
			}
			catch (\Exception $e)
			{
				throw new \Exception(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_ERROR_MISSING_SPECIAL_CATEGORY', $catName, $e->getMessage()));
			}

			$categories[] = $catId;
		}

		return $categories;
	}

	/**
	 * Extract the seller from the record
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  int
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveSeller($obj)
	{
		$key = $this->getOption('unique_key.seller', '');
		$key = strtolower($key);

		if (!$key || empty($obj->$key))
		{
			return 0;
		}

		$userId = $obj->x__seller_uid;

		// Find if a matching seller/user exists
		if (!$userId)
		{
			$object = new \stdClass;

			$object->user_id       = null;
			$object->name          = $obj->seller_name;
			$object->username      = $obj->seller_username;
			$object->email         = $obj->seller_email;
			$object->business_name = $obj->seller_business_name;
			$object->code          = $obj->seller_code;
			$object->store_name    = $obj->seller_store_name;

			$userId = Element\Seller::getUid($object, str_replace('seller_', '', $key));
		}

		$create = $this->getOption('create.sellers', 0);
		$update = $this->getOption('update.sellers', 0);

		// Irrespective of create/update flag we need to ensure that the necessary records exists anyway
		if ($userId || $create)
		{
			$object = new \stdClass;
			$dVal   = $userId ? null : $obj->$key;

			$object->user_id       = $userId ?: null;
			$object->name          = $obj->seller_name ?: $dVal;
			$object->username      = $obj->seller_username;
			$object->email         = $obj->seller_email;
			$object->business_name = $obj->seller_business_name ?: $dVal;
			$object->code          = $obj->seller_code;
			$object->store_name    = $obj->seller_store_name ?: $dVal;
			$object->store_address = $obj->seller_store_address;
			$object->mobile        = $obj->seller_mobile;
			$object->website       = $obj->seller_website;
			$object->currency      = $obj->price_currency;

			$userId = Element\Seller::create($object, $update);
		}

		// If we just created/found a match, reflect to all records with same key in import table for further reuse
		if ($userId && !$obj->x__seller_uid)
		{
			$obj->x__seller_uid = $userId;

			$o = new \stdClass;

			$o->x__seller_uid = $userId;
			$o->$key          = $obj->$key;

			$this->db->updateObject($this->importTable, $o, array($key));
		}

		return $userId;
	}

	/**
	 * Extract the manufacturer from the record
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  int
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveManufacturer($obj)
	{
		$key = $this->getOption('unique_key.manufacturer', '');
		$key = strtolower($key);

		if (!$key || empty($obj->$key))
		{
			return 0;
		}

		$userId = $obj->x__manufacturer_uid;

		// Find if a matching manufacturer/user exists
		if (!$userId)
		{
			$object = new \stdClass;

			$object->user_id  = null;
			$object->name     = $obj->manufacturer_name;
			$object->username = $obj->manufacturer_username;
			$object->email    = $obj->manufacturer_email;
			$object->company  = $obj->manufacturer_company;
			$object->code     = $obj->manufacturer_code;

			$userId = Element\Manufacturer::getUid($object, str_replace('manufacturer_', '', $key));
		}

		$create = $this->getOption('create.manufacturers', 0);
		$update = $this->getOption('update.manufacturers', 0);

		// Irrespective of create/update flag we need to ensure that the necessary records exists anyway
		if ($userId || $create)
		{
			$object = new \stdClass;
			$dVal   = $userId ? null : $obj->$key;

			$object->user_id  = $userId ?: null;
			$object->name     = $obj->manufacturer_name ?: $dVal;
			$object->username = $obj->manufacturer_username;
			$object->email    = $obj->manufacturer_email;
			$object->company  = $obj->manufacturer_company ?: $dVal;
			$object->code     = $obj->manufacturer_code;

			$userId = Element\Manufacturer::create($object, $update);
		}

		// If we just created/found a match, reflect to all records with same key in import table for further reuse
		if ($userId && !$obj->x__manufacturer_uid)
		{
			$obj->x__manufacturer_uid = $userId;

			$o = new \stdClass;

			$o->x__manufacturer_uid = $userId;
			$o->$key                = $obj->$key;

			$this->db->updateObject($this->importTable, $o, array($key));
		}

		return $userId;
	}

	/**
	 * Save the product record
	 *
	 * @param   \stdClass  $obj         The importable record
	 * @param   int[]      $categories  The category ids to assign
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.5.0
	 */
	protected function saveProduct($obj, $categories)
	{
		$key = $this->getOption('unique_key.product', '');
		$key = strtolower($key);

		if (!$key || empty($obj->$key))
		{
			return true;
		}

		if (!$obj->x__product_id)
		{
			$obj->x__product_id = Element\Product::findByKey($obj, $key);
		}

		$me  = \JFactory::getUser();
		$now = \JFactory::getDate()->toSql();

		$features = array(
			$obj->product_feature_1,
			$obj->product_feature_2,
			$obj->product_feature_3,
			$obj->product_feature_4,
			$obj->product_feature_5,
		);
		$features = array_filter($features, 'strlen');

		$product                   = new \stdClass;
		$product->id               = $obj->x__product_id ?: null;
		$product->parent_id        = $obj->x__parent_id ?: null;
		$product->title            = $obj->product_title;
		$product->alias            = $obj->product_title ? \JApplicationHelper::stringURLSafe($obj->product_title) : null;
		$product->type             = $obj->product_type;
		$product->local_sku        = $obj->product_sku;
		$product->manufacturer_sku = $obj->mfg_assigned_sku;
		$product->manufacturer_id  = $obj->x__manufacturer_uid ?: null;
		$product->introtext        = $obj->product_summary;
		$product->description      = $obj->product_description;
		$product->metakey          = $obj->product_meta_key;
		$product->metadesc         = $obj->product_meta_description;
		$product->features         = $features ? json_encode($features) : null;
		$product->state            = $obj->product_state;
		$product->ordering         = (int) $obj->product_ordering;

		if ($product->id)
		{
			$update = $this->getOption('update.products', '');

			// Make extra sure that the guest user (id = 0) does not accidentally update a global product
			if ($update == 'all' || ($update == 'own' && $me->id > 0))
			{
				$product->modified    = $now;
				$product->modified_by = $me->id;

				$keys = array('id');

				if ($update == 'own')
				{
					$product->owned_by = $me->id;

					$keys[] = 'owned_by';
				}

				$this->db->updateObject('#__sellacious_products', $product, $keys);
			}
		}
		else
		{
			$create = $this->getOption('create.products', '');

			// Make extra sure that the guest user (id = 0) does not accidentally create a global product
			if ($create == 'all' || ($create == 'own' && $me->id > 0))
			{
				$product->created    = $now;
				$product->created_by = $now;
				$product->owned_by   = $create == 'own' ? $me->id : 0;

				if ($this->db->insertObject('#__sellacious_products', $product, 'id'))
				{
					$obj->x__product_id = $product->id;
				}
			}
		}

		if ($obj->x__product_id)
		{
			if (count($categories))
			{
				$this->helper->product->setCategories($obj->x__product_id, $categories);
			}

			// If a unique key was not specified we cannot map parent
			if ($key)
			{
				$o = new \stdClass;

				$o->product_parent_key = $obj->$key;
				$o->x__parent_id       = $product->id;

				$this->db->updateObject($this->importTable, $o, array('product_parent_key'));
			}
		}

		return true;
	}

	/**
	 * Save the variant record
	 *
	 * @param   \stdClass  $obj  The importable record
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.5.0
	 */
	protected function saveVariant($obj)
	{
		$key = $this->getOption('unique_key.variant', '');
		$key = strtolower($key);

		$independent = $this->getOption('variant_independent', 0);
		$productId   = $independent ? null : $obj->x__product_id;

		if (!$key || empty($obj->$key) || (!$independent && !$productId))
		{
			return true;
		}

		if (!$obj->x__variant_id)
		{
			$obj->x__variant_id = Element\Variant::findByKey($obj, $key, $productId);
		}

		// If doesn't exist and we cant create.. quit.
		$me  = \JFactory::getUser();
		$now = \JFactory::getDate()->toSql();

		$features = array(
			$obj->variant_feature_1,
			$obj->variant_feature_2,
			$obj->variant_feature_3,
			$obj->variant_feature_4,
			$obj->variant_feature_5,
		);
		$features = array_filter($features, 'strlen');

		$variant = new \stdClass;

		$variant->id          = $obj->x__variant_id ?: null;
		$variant->product_id  = $productId;
		$variant->title       = $obj->variant_title;
		$variant->alias       = $obj->variant_title ? \JApplicationHelper::stringURLSafe($obj->variant_title) : null;
		$variant->local_sku   = $obj->variant_sku;
		$variant->features    = $features ? json_encode($features) : null;
		$variant->state       = 1;

		if ($variant->id)
		{
			$update = $this->getOption('update.variants', '');

			// Make extra sure that the guest user (id = 0) does not accidentally update a global variant
			if ($update == 'all' || ($update == 'own' && $me->id > 0))
			{
				$variant->modified    = $now;
				$variant->modified_by = $me->id;

				$keys = array('id');

				if ($update == 'own')
				{
					$variant->owned_by = $me->id;

					$keys[] = 'owned_by';
				}

				$this->db->updateObject('#__sellacious_variants', $variant, $keys);
			}
		}
		else
		{
			$create = $this->getOption('create.variants', '');

			// Make extra sure that the guest user (id = 0) does not accidentally create a global variant
			if ($create == 'all' || ($create == 'own' && $me->id > 0))
			{
				$variant->created    = $now;
				$variant->created_by = $now;
				$variant->owned_by   = $create == 'own' ? $me->id : 0;

				if ($this->db->insertObject('#__sellacious_variants', $variant, 'id'))
				{
					$obj->x__variant_id = $variant->id;
				}
			}
		}

		return true;
	}

	/**
	 * Save the specification fields
	 *
	 * @param   \stdClass  $obj     The importable record
	 * @param   string[]   $fields  The specification attributes
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.5.0
	 */
	protected function saveSpecifications(&$obj, $fields)
	{
		// Todo: Split based on core/variant fields
		if ($obj->x__variant_id)
		{
			$this->helper->field->clearValue('variants', $obj->x__variant_id, array_keys($fields));

			foreach ($fields as $fieldId => $fieldValue)
			{
				$fObj = (object) array(
					'table_name'  => 'variants',
					'record_id'   => $obj->x__variant_id,
					'field_id'    => $fieldId,
					'field_value' => is_scalar($fieldValue) ? $fieldValue : json_encode($fieldValue),
					'is_json'     => is_scalar($fieldValue) ? 0 : 1,
				);

				$this->db->insertObject('#__sellacious_field_values', $fObj, 'id');
			}
		}
		elseif ($obj->x__product_id)
		{
			$this->helper->field->clearValue('products', $obj->x__product_id, array_keys($fields));

			foreach ($fields as $fieldId => $fieldValue)
			{
				$fObj = (object) array(
					'table_name'  => 'products',
					'record_id'   => $obj->x__product_id,
					'field_id'    => $fieldId,
					'field_value' => is_scalar($fieldValue) ? $fieldValue : json_encode($fieldValue),
					'is_json'     => is_scalar($fieldValue) ? 0 : 1,
				);

				$this->db->insertObject('#__sellacious_field_values', $fObj, 'id');
			}
		}

		return true;
	}

	/**
	 * Extract the variant from the record
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveProductSellerXref($obj)
	{
		if (!$obj->x__product_id || !$obj->x__seller_uid)
		{
			return false;
		}

		$psx                 = new \stdClass;
		$psx->product_id     = $obj->x__product_id;
		$psx->seller_uid     = $obj->x__seller_uid;
		$psx->price_display  = $obj->price_display;
		$psx->quantity_min   = $obj->min_order_qty;
		$psx->quantity_max   = $obj->max_order_qty;
		$psx->stock          = $obj->x__variant_id && !$obj->product_current_stock ? null : $obj->product_current_stock;
		$psx->over_stock     = $obj->x__variant_id && !$obj->product_over_stock_sale_limit ? null : $obj->product_over_stock_sale_limit;
		$psx->stock_reserved = $obj->x__variant_id && !$obj->product_reserved_stock ? null : $obj->product_reserved_stock;
		$psx->stock_sold     = $obj->x__variant_id && !$obj->product_stock_sold ? null : $obj->product_stock_sold;
		$psx->state          = 1;

		$filters = array(
			'list.select' => 'a.id',
			'list.from'   => '#__sellacious_product_sellers',
			'product_id'  => $obj->x__product_id,
			'seller_uid'  => $obj->x__seller_uid,
		);
		$psx->id = $this->helper->product->loadResult($filters);

		if ($psx->id)
		{
			$saved = $this->db->updateObject('#__sellacious_product_sellers', $psx, array('id'));
		}
		else
		{
			$saved = $this->db->insertObject('#__sellacious_product_sellers', $psx, 'id');
		}

		$obj->x__psx_id = $psx->id;

		return $saved;
	}

	/**
	 * Extract the variant from the record
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveVariantSellerXref($obj)
	{
		if (!$obj->x__variant_id || !$obj->x__seller_uid)
		{
			return true;
		}

		$vsx = new \stdClass;

		$vsx->variant_id     = $obj->x__variant_id;
		$vsx->seller_uid     = $obj->x__seller_uid;
		$vsx->price_mod      = $obj->variant_price_add;
		$vsx->price_mod_perc = $obj->variant_price_is_percent;
		$vsx->stock          = $obj->variant_current_stock;
		$vsx->over_stock     = $obj->variant_over_stock_sale_limit;
		$vsx->stock_reserved = $obj->variant_reserved_stock;
		$vsx->stock_sold     = $obj->variant_stock_sold;
		$vsx->state          = 1;

		$filters = array(
			'list.select' => 'a.id',
			'list.from'   => '#__sellacious_variant_sellers',
			'variant_id'  => $obj->x__variant_id,
			'seller_uid'  => $obj->x__seller_uid,
		);
		$vsx->id = $this->helper->variant->loadResult($filters);

		if ($vsx->id)
		{
			$saved = $this->db->updateObject('#__sellacious_variant_sellers', $vsx, array('id'));
		}
		else
		{
			$saved = $this->db->insertObject('#__sellacious_variant_sellers', $vsx, 'id');

			$obj->x__vsx_id = $vsx->id;
		}

		return $saved;
	}

	/**
	 * Save the product type specific attributes
	 *
	 * @param   \stdClass  $obj  The entire row from CSV
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveTypeAttributes($obj)
	{
		if (!$obj->x__product_id)
		{
			return false;
		}

		if ($obj->product_type == 'physical')
		{
			$object = new \stdClass;

			$object->product_id = $obj->x__product_id;
			$object->length     = $obj->length ? json_encode(array('m' => (float) $obj->length)) : null;
			$object->width      = $obj->width ? json_encode(array('m' => (float) $obj->width)) : null;
			$object->height     = $obj->height ? json_encode(array('m' => (float) $obj->height)) : null;
			$object->weight     = $obj->weight ? json_encode(array('m' => (float) $obj->weight)) : null;

			$tableName = '#__sellacious_product_physical';
		}
		else
		{
			// Implement this
			return true;
		}

		$filters    = array(
			'list.select' => 'a.id',
			'list.from'   => $tableName,
			'product_id'  => $obj->x__product_id,
		);
		$object->id = $this->helper->variant->loadResult($filters);

		if ($object->id)
		{
			$saved = $this->db->updateObject($tableName, $object, array('id'));
		}
		else
		{
			$saved = $this->db->insertObject($tableName, $object, 'id');
		}

		return $saved;
	}

	/**
	 * Save the product related product groups
	 *
	 * @param   \stdClass  $obj  The entire row from CSV
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.6.0
	 */
	protected function saveRelatedGroups($obj)
	{
		if ($obj->x__product_id && !empty($obj->related_product_groups))
		{
			$groupNames = preg_split('#(?<!\\\);#', $obj->related_product_groups, -1, PREG_SPLIT_NO_EMPTY);
			$groupNames = array_unique(array_filter($groupNames, 'trim'));

			foreach ($groupNames as $groupName)
			{
				$table = \SellaciousTable::getInstance('RelatedProduct');
				$tbl2  = \SellaciousTable::getInstance('RelatedProduct');
				$xref  = new \stdClass;

				$xref->product_id  = $obj->x__product_id;
				$xref->group_title = $groupName;
				$xref->group_alias = null;

				$table->bind($xref);
				$table->check();

				$keys = array(
					'product_id'  => $table->get('product_id'),
					'group_alias' => $table->get('group_alias')
				);

				if (!$tbl2->load($keys))
				{
					$table->store();
				}
			}
		}
	}

	/**
	 * Save the product type specific seller attributes
	 *
	 * @param   \stdClass  $obj  The entire row from import table
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveSellerAttributesByType($obj)
	{
		if (!$obj->x__psx_id)
		{
			return false;
		}

		if ($obj->product_type == 'physical')
		{
			$object = new \stdClass;

			$object->psx_id            = $obj->x__psx_id;
			$object->listing_type      = $obj->product_listing_type;
			$object->item_condition    = $obj->product_condition;
			$object->flat_shipping     = $obj->is_flat_shipping;
			$object->shipping_flat_fee = $obj->flat_shipping_fee;
			$object->return_days       = $obj->order_return_days;
			$object->return_tnc        = $obj->order_return_tnc;
			$object->exchange_days     = $obj->order_exchange_days;
			$object->exchange_tnc      = $obj->order_exchange_tnc;
			$object->whats_in_box      = $obj->whats_in_box;
			$object->length            = $obj->shipping_length ? json_encode(array('m' => (float) $obj->shipping_length)) : null;
			$object->width             = $obj->shipping_width ? json_encode(array('m' => (float) $obj->shipping_width)) : null;
			$object->height            = $obj->shipping_height ? json_encode(array('m' => (float) $obj->shipping_height)) : null;
			$object->weight            = $obj->shipping_weight ? json_encode(array('m' => (float) $obj->shipping_weight)) : null;
			$object->vol_weight        = $obj->volumetric_weight ? json_encode(array('m' => (float) $obj->volumetric_weight)) : null;

			$tableName = '#__sellacious_physical_sellers';
		}
		elseif ($obj->product_type == 'electronic')
		{
			$object = new \stdClass;

			$object->psx_id          = $obj->x__psx_id;
			$object->delivery_mode   = $obj->eproduct_delivery_mode;
			$object->download_limit  = $obj->eproduct_download_limit;
			$object->download_period = $obj->eproduct_download_period;
			$object->preview_url     = $obj->eproduct_preview_url;

			$tableName = '#__sellacious_eproduct_sellers';
		}
		elseif ($obj->product_type == 'package')
		{
			$object = new \stdClass;

			$object->psx_id            = $obj->x__psx_id;
			$object->listing_type      = $obj->product_listing_type;
			$object->item_condition    = $obj->product_condition;
			$object->flat_shipping     = $obj->is_flat_shipping;
			$object->shipping_flat_fee = $obj->flat_shipping_fee;
			$object->return_days       = $obj->order_return_days;
			$object->return_tnc        = $obj->order_return_tnc;
			$object->exchange_days     = $obj->order_exchange_days;
			$object->exchange_tnc      = $obj->order_exchange_tnc;

			$tableName = '#__sellacious_package_sellers';
		}
		else
		{
			return true;
		}

		$filters    = array(
			'list.select' => 'a.id',
			'list.from'   => $tableName,
			'psx_id'      => $obj->x__psx_id,
		);
		$object->id = $this->helper->product->loadResult($filters);

		if ($object->id)
		{
			$saved = $this->db->updateObject($tableName, $object, array('id'));
		}
		else
		{
			$saved = $this->db->insertObject($tableName, $object, 'id');
		}

		return $saved;
	}

	/**
	 * Add the product listing for the seller
	 *
	 * @param   \stdClass  $obj            The entire row from import
	 * @param   int[]      $splCategories  The special categories ids
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 *
	 * @since   1.4.7
	 */
	protected function saveSellerListing($obj, $splCategories)
	{
		if (!$obj->x__product_id || !$obj->x__seller_uid)
		{
			return false;
		}

		// If listing info is not given or invalid, skip it all
		if (@strtotime($obj->listing_start_date) && @strtotime($obj->listing_end_date))
		{
			$start = @strtotime($obj->listing_start_date) ? \JFactory::getDate($obj->listing_start_date) : null;
			$end   = @strtotime($obj->listing_end_date) ? \JFactory::getDate($obj->listing_end_date) : null;
			$diff  = date_diff($end, $start);

			if ($start > $end || $diff === false)
			{
				return false;
			}

			$tableName = $this->helper->listing->getTable()->getTableName();

			$query = $this->db->getQuery(true);
			$query->update($tableName)
				->set('state = 0')
				->where('product_id = ' . (int) $obj->x__product_id)
				->where('seller_uid = ' . (int) $obj->x__seller_uid)
				->where('state = 1');
			$this->db->setQuery($query)->execute();

			// Handle basic listing
			$listing                    = new \stdClass;
			$listing->id                = 0;
			$listing->product_id        = $obj->x__product_id;
			$listing->seller_uid        = $obj->x__seller_uid;
			$listing->category_id       = 0;
			$listing->days              = $diff ? $diff->d : 0;
			$listing->publish_up        = $start->toSql();
			$listing->publish_down      = $end->toSql();
			$listing->subscription_date = null;
			$listing->carried_from      = null;
			$listing->state             = 1;

			$this->db->insertObject($tableName, $listing, array('id'));

			// Handle special categories
			foreach ($splCategories as $catid)
			{
				$listing->id          = 0;
				$listing->category_id = $catid;

				$this->db->insertObject($tableName, $listing, array('id'));
			}
		}

		return true;
	}

	/**
	 * Extract and save the prices columns from the record and clear them from the row
	 *
	 * @param   \stdClass  $obj  The entire row from import
	 *
	 * @return  void
	 *
	 * @since   1.4.7
	 */
	protected function savePrices($obj)
	{
		if (!$obj->x__product_id || !$obj->x__seller_uid)
		{
			return;
		}

		$prices = array();

		// Add default price
		$prices[] = array(
			'amount_flat'    => $obj->price_amount_flat,
			'min_quantity'   => null,
			'max_quantity'   => null,
			'start_date'     => null,
			'end_date'       => null,
			'fallback'       => 1,
			'cost_price'     => $obj->price_cost_price,
			'margin'         => $obj->price_margin,
			'margin_percent' => $obj->price_margin_percent,
			'list_price'     => $obj->price_list_price,
		);

		// Extract the advance prices
		foreach ($obj as $key => $value)
		{
			if (preg_match('/^price_(\d+)_(.*)$/', $key, $matches))
			{
				list(, $pi, $k)  = $matches;
				$prices[$pi][$k] = $value;
			}
		}

		// Save them now
		foreach ($prices as $price)
		{
			$margin    = (bool) $price['margin_percent'] ? ($price['margin'] * $price['cost_price'] / 100.0) : $price['margin'];
			$calcPrice = round($price['cost_price'] + $margin, 2);

			// If there is no calculated price and override price then list price will be taken be default as override price, else we skip.
			if (floatval($price['amount_flat']) >= 0.01)
			{
				$ovrPrice = ($calcPrice < 0.01 || abs($calcPrice - $price['amount_flat']) >= 0.01) ? $price['amount_flat'] : 0.00;
			}
			elseif ($calcPrice >= 0.01)
			{
				$ovrPrice = 0.00;
			}
			elseif (floatval($price['list_price']) >= 0.01)
			{
				$ovrPrice = floatval($price['list_price']);
			}
			else
			{
				continue;
			}

			$table = \SellaciousTable::getInstance('ProductPrices');

			$price['start_date'] = @strtotime($price['start_date']) ? \JFactory::getDate($price['start_date'])->toSql() : null;
			$price['end_date']   = @strtotime($price['start_date']) ? \JFactory::getDate($price['end_date'])->toSql() : null;

			if (empty($price['fallback']))
			{
				$price['fallback'] = 0;

				$keys = array(
					'product_id'  => $obj->x__product_id,
					'seller_uid'  => $obj->x__seller_uid,
					'is_fallback' => 0,
					'qty_min'     => $price['min_quantity'],
					'qty_max'     => $price['max_quantity'],
					'sdate'       => $price['start_date'],
					'edate'       => $price['end_date'],
				);

				$table->load($keys);
			}
			elseif ($price['fallback'] == 1)
			{
				$keys = array(
					'product_id'  => $obj->x__product_id,
					'seller_uid'  => $obj->x__seller_uid,
					'is_fallback' => 1,
				);

				$table->load($keys);
			}

			$sPrice = new \stdClass;

			$sPrice->product_id       = $obj->x__product_id;
			$sPrice->seller_uid       = $obj->x__seller_uid;
			$sPrice->qty_min          = $price['min_quantity'];
			$sPrice->qty_max          = $price['max_quantity'];
			$sPrice->sdate            = $price['start_date'];
			$sPrice->edate            = $price['end_date'];
			$sPrice->cost_price       = $price['cost_price'];
			$sPrice->margin           = $price['margin'];
			$sPrice->margin_type      = $price['margin_percent'];
			$sPrice->list_price       = $price['list_price'];
			$sPrice->calculated_price = $calcPrice;
			$sPrice->ovr_price        = $ovrPrice;
			$sPrice->product_price    = ($ovrPrice >= 0.01) ? $ovrPrice : $calcPrice;
			$sPrice->is_fallback      = $price['fallback'];
			$sPrice->state            = 1;

			$table->bind($sPrice);
			$table->check();
			$table->store();

			$priceId = $table->get('id');

			// Client category map
			if ($priceId && !$price['fallback'] && !empty($price['client_categories']))
			{
				$categories = array();
				$catPaths   = preg_split('#(?<!\\\);#', $price['client_categories'], -1, PREG_SPLIT_NO_EMPTY);
				$catNames   = array_unique(array_filter($catPaths, 'trim'));

				$create = $this->getOption('create.categories', 0);

				foreach ($catNames as $catName)
				{
					try
					{
						$catId = Element\Category::getId($catName, 'client', $create);
						$xref  = new \stdClass;

						$xref->id               = null;
						$xref->product_price_id = $priceId;
						$xref->cat_id           = $catId;

						$this->db->insertObject('#__sellacious_productprices_clientcategory_xref', $xref, 'id');

						// Unused as of now, but may be used later
						$categories[$catId] = $catName;
					}
					catch (\Exception $e)
					{
						$this->timer->log(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_ERROR_MISSING_CATEGORY', $catName, $e->getMessage()));
					}
				}
			}
		}
	}

	/**
	 * Extract and save the prices columns from the record and clear them from the row
	 *
	 * @param   \stdClass  $obj  The entire row from import
	 *
	 * @return  void
	 *
	 * @since   1.6.0
	 */
	protected function savePackageItems($obj)
	{
		$key = $this->getOption('unique_key.package');

		if (!$key)
		{
			return;
		}

		$identifiers = preg_split('#(?<!\\\);#', $obj->package_items, -1, PREG_SPLIT_NO_EMPTY);
		$items       = array();

		foreach ($identifiers as $identifier)
		{
			try
			{
				$p = null;
				$v = null;

				if ($key == 'product_code')
				{
					$this->helper->product->parseCode($identifier, $p, $v, $s);
				}
				else
				{
					$p = Element\Product::findByKey($identifier, $key);
					$v = null;

					if (!$p)
					{
						$v = Element\Variant::findByKey($identifier, $key, null);
						$p = $this->helper->variant->loadResult(array('list.select' => 'a.product_id', 'id' => $v));
					}
				}

				if ($p)
				{
					$items[sprintf('%d:%d', $p, $v)] = (object) array('package_id' => $obj->x__product_id, 'product_id' => $p, 'variant_id' => $v);
				}
			}
			catch (\Exception $e)
			{
				$this->timer->log(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_ERROR_PACKAGE_ITEM_LOOKUP', $identifier, $obj->product_title, $e->getMessage()));
			}
		}

		if ($items)
		{
			try
			{
				$query = $this->db->getQuery(true);
				$query->delete('#__sellacious_package_items')->where('package_id = ' . (int) $obj->x__product_id);
				$this->db->setQuery($query)->execute();

				foreach ($items as $map)
				{
					$this->db->insertObject('#__sellacious_package_items', $map, 'id');
				}
			}
			catch (\Exception $e)
			{
				$this->timer->log(\JText::sprintf('PLG_SYSTEM_SELLACIOUSIMPORTER_IMPORT_ERROR_PACKAGE_ITEMS_ADD', $obj->product_title, $e->getMessage()));
			}
		}
	}

	/**
	 * Load the image as specified in the record
	 *
	 * @param   \stdClass  $obj  The entire row from import
	 *
	 * @return  bool
	 *
	 * @since   1.5.0
	 */
	protected function saveImage($obj)
	{
		$imageUrl = rtrim($obj->image_folder . '/' . $obj->image_filename, '/ ');
		$imageUrl = $this->parseImageCode($obj, $imageUrl);

		jimport('joomla.filesystem.path');
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		if (!is_file(\JPath::clean(JPATH_SITE . '/' . $imageUrl)))
		{
			return true;
		}

		// Use this image for variant/product/category: id, table_name, record_id, context, original_name, type, path, size, state
		if ($obj->x__variant_id)
		{
			$tableName = 'variants';
			$recordId  = $obj->x__variant_id;
		}
		elseif ($obj->x__product_id)
		{
			$tableName = 'products';
			$recordId  = $obj->x__product_id;
		}
		else
		{
			$cat_ids = json_decode($obj->x__category_ids, true) ?: array();
			$catid   = reset($cat_ids);

			if (!$catid)
			{
				return true;
			}

			$tableName = 'categories';
			$recordId  = $catid;
		}

		$filename  = basename($imageUrl);
		$directory = $this->helper->media->getBaseDir(sprintf('%s/images/%d/', $tableName, $recordId));

		if (\JFolder::create(JPATH_SITE . $directory) && \JFile::copy($imageUrl, $directory . $filename, JPATH_SITE))
		{
			// Todo: check for uniqueness
			$image = new \stdClass;

			$image->table_name    = $tableName;
			$image->context       = 'images';
			$image->record_id     = $recordId;
			$image->path          = $directory . $filename;
			$image->original_name = $imageUrl;
			$image->type          = mime_content_type(JPATH_SITE . '/' . $directory . $filename);
			$image->size          = filesize(JPATH_SITE . '/' . $directory . $filename);
			$image->state         = 1;
			$image->created       = \JFactory::getDate()->toSql();

			return $this->db->insertObject('#__sellacious_media', $image, 'id');
		}

		return false;
	}

	/**
	 * Load the image as specified in the record from a remote URL
	 *
	 * @param   \stdClass  $obj  The entire row from import
	 *
	 * @return  bool
	 *
	 * @since   1.5.0
	 */
	protected function saveImageUrl($obj)
	{
		$imageUrl = $obj->image_url;
		$imageUrl = $this->parseImageCode($obj, $imageUrl);

		jimport('joomla.filesystem.path');
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		// People often forget http(s), we can at most detect a 'www.'
		if (substr($imageUrl, 0, 4) == 'www.')
		{
			$imageUrl = 'http://' . $imageUrl;
		}

		if (substr($imageUrl, 0, 7) != 'http://' && substr($imageUrl, 0, 8) != 'https://')
		{
			// This may be a local file, no B/C
			return false;
		}

		// Use this image for variant/product/category: id, table_name, record_id, context, original_name, type, path, size, state
		if ($obj->x__variant_id)
		{
			$tableName = 'variants';
			$recordId  = $obj->x__variant_id;
		}
		elseif ($obj->x__product_id)
		{
			$tableName = 'products';
			$recordId  = $obj->x__product_id;
		}
		else
		{
			$cat_ids = json_decode($obj->x__category_ids, true) ?: array();
			$catid   = reset($cat_ids);

			if (!$catid)
			{
				return true;
			}

			$tableName = 'categories';
			$recordId  = $catid;
		}

		$filename  = basename($imageUrl);
		$directory = $this->helper->media->getBaseDir(sprintf('%s/images/%d/', $tableName, $recordId));

		// We'll download this image later in a separate batch. Put a placeholder for now
		$placeholder = \JHtml::_('image', 'com_importer/coming-soon-placeholder.png', '', null, true, 1);
		$placeholder = $placeholder ? substr($placeholder, strlen(rtrim(\JUri::root(true), '\\/'))) : null;

		if (is_file(JPATH_SITE . $placeholder) &&
			\JFolder::create(JPATH_SITE . $directory) &&
			\JFile::copy($placeholder, $directory . $filename, JPATH_SITE))
		{
			$params = array(
				'remote_download' => true,
				'download_url'    => $imageUrl,
			);

			// Todo: check for uniqueness
			$image = new \stdClass;

			$image->table_name    = $tableName;
			$image->context       = 'images';
			$image->record_id     = $recordId;
			$image->path          = $directory . $filename;
			$image->original_name = $filename;
			$image->type          = 'image/generic';
			$image->size          = 0;
			$image->state         = -1;
			$image->params        = json_encode($params);
			$image->created       = \JFactory::getDate()->toSql();

			return $this->db->insertObject('#__sellacious_media', $image, 'id');
		}

		return false;
	}

	/**
	 * Process the embedded short codes in the image path/url
	 *
	 * @param   \stdClass  $obj       The import record
	 * @param   string     $imageUrl  The path/url to process
	 *
	 * @return  string
	 *
	 * @since   1.6.0
	 */
	protected function parseImageCode($obj, $imageUrl)
	{
		static $pattern;

		if (strpos($imageUrl, '%') !== false)
		{
			// Optimize! Build pattern only once (We use the headers as short-code)
			if (!$pattern)
			{
				$headers = array();

				foreach ($this->headers as $header)
				{
					$headers[] = '%' . preg_quote($header, '/') . '%';
				}

				$pattern = '/(' . implode('|', $headers) . ')/i';
			}

			$matches = array();

			preg_match_all($pattern, $imageUrl, $matches, PREG_SET_ORDER);

			foreach ($matches as $match)
			{
				$key = strtolower($match[1]);

				$imageUrl = str_replace($match[0], isset($obj->$key) ? $obj->$key : '', $imageUrl);
			}
		}

		// If there is no image, do not proceed here
		if (strlen($imageUrl) == 0)
		{
			return null;
		}

		// Check for an allowed image file type
		$ext = substr($imageUrl, -4);

		if ($ext != '.jpg' && $ext != '.png' && $ext != '.gif')
		{
			return null;
		}

		return $imageUrl;
	}
}
