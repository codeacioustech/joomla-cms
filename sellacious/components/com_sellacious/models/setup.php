<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     SPL Sellacious Private License; see http://www.sellacious.com/spl.html
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access.
defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Sellacious\Config\ConfigHelper;

/**
 * Sellacious initial configuration model.
 *
 * @since   1.5.0
 */
class SellaciousModelSetup extends SellaciousModelAdmin
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * @note    Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering
	 * @param   string  $direction
	 *
	 * @throws  Exception
	 *
	 * @since   1.5.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$this->app->getUserStateFromRequest('com_sellacious.setup.return', 'return', '', 'cmd');

		parent::populateState();
	}

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    Table name
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for table. Optional.
	 *
	 * @return  JTable
	 *
	 * @since   1.5.0
	 */
	public function getTable($type = 'Config', $prefix = 'SellaciousTable', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

	/**
	 * Method to save the form data
	 *
	 * @param   array  $data  The form data
	 *
	 * @return  bool
	 * @throws  Exception
	 *
	 * @since   1.5.0
	 */
	public function save($data)
	{
		unset($data['tags']);
		unset($data['premium_trial']);

		$defaults = new Registry($this->getItem());
		$registry = new Registry($data);

		$defaults->merge($registry, true);

		foreach ($defaults as $name => $params)
		{
			$config = ConfigHelper::getInstance($name);

			$config->bind($params);

			$config->store();
		}

		return true;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  stdClass
	 *
	 * @since   1.5.0
	 */
	public function getItem($pk = null)
	{
		$data   = array(
			'multi_seller'                    => '0',
			'multi_variant'                   => '1',
			'pricing_model'                   => 'basic',
			'allowed_product_type'            => 'both',
			'eproduct_file_type'              => array('image', 'document', 'archive'),
			'allowed_product_package'         => '1',
			'allowed_listing_type'            => array('1'),
			'allow_duplicate_location'        => '0',
			'product_rating'                  => '1',
			'product_detail_page'             => '1',
			'product_compare'                 => '1',
			'compare_limit'                   => '3',
			'redirect'                        => '',
			'contact_spam_protection'         => '1',
			'product_filter_position'         => 'left',
			'financial_year_start'            => '1',
			'forex_update_interval'           => array(
				'l' => '1',
				'p' => 'day',
			),
			'show_brand_footer'               => '1',
			'show_rate_us'                    => '1',
			'show_doc_link'                   => '1',
			'show_support_link'               => '1',
			'show_advertisement'              => '1',
			'show_back_to_joomla'             => '1',
			'show_sellacious_version'         => '1',
			'show_licence_to'                 => '1',
			'development'                     => '',
			'shop_name'                       => '',
			'shop_logo'                       => '',
			'shop_address'                    => '',
			'shop_country'                    => '216',
			'ip_country'                      => '0',
			'shop_phone1'                     => '',
			'shop_phone2'                     => '',
			'shop_email'                      => '',
			'shop_website'                    => '',
			'global_currency'                 => 'USD',
			'shopping_complete_redirect'      => '',
			'user_currency'                   => '0',
			'ip_currency'                     => '1',
			'allow_checkout'                  => '1',
			'min_checkout_value'              => 0,
			'order_number_pattern'            => '{YY}{OID}',
			'order_number_pad'                => '4',
			'order_number_shift'              => '0',
			'purchase_return'                 => '1',
			'purchase_return_icon'            => '',
			'purchase_return_icon_global'     => '',
			'purchase_return_tnc'             => '',
			'purchase_exchange'               => '1',
			'purchase_exchange_icon'          => '',
			'purchase_exchange_icon_global'   => '',
			'purchase_exchange_tnc'           => '',
			'splcategory_badge_display'       => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'product_features_list'           => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'product_rating_display'          => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'product_compare_display'         => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'product_add_to_cart_display'     => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'product_buy_now_display'         => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'product_quick_detail_pages'      => array(
				0 => 'categories',
				1 => 'products',
			),
			'product_price_display'           => '2',
			'product_price_display_pages'     => array(
				0 => 'categories',
				1 => 'products',
				2 => 'product',
				3 => 'product_modal',
			),
			'cart_shop_more_link'             => '1',
			'shop_more_redirect'              => '',
			'show_category_child_count'       => '1',
			'show_category_product_count'     => '1',
			'show_category_description'       => '1',
			'show_category_products'          => '1',
			'category_page_product_limit'     => '5',
			'image_slider_height'             => '270',
			'image_slider_width'              => '270',
			'image_slider_size_adjust'        => 'contain',
			'list_style'                      => 'grid',
			'list_style_switcher'             => '1',
			'hide_out_of_stock'               => '0',
			'hide_zero_priced'                => '0',
			'hide_zip_filter'                 => '0',
			'hide_category_filter'            => '0',
			'geolocation_levels'              => array(
				'company'     => '1',
				'address'     => '1',
				'po_box'      => '1',
				'landmark'    => '1',
				'country'     => '1',
				'state_loc'   => '1',
				'district'    => '1',
				'zip'         => '1',
				'mobile'      => '1',
				'residential' => '1',
			),
			'address_zip_regex'               => '^[\w\d]{5,8}$',
			'address_mobile_regex'            => '^\+?[\d]{8,12}$',
			'usergroups_company'              => array('7'),
			'usergroups_client'               => array('9', '2'),
			'usergroups_seller'               => array('10'),
			'usergroups_staff'                => array('11'),
			'require_activation_cart_aio'     => '0',
			'seller_country'                  => '',
			'default_seller'                  => '',
			'seller_can_know_client_category' => '0',
			'listing_currency'                => '0',
			'on_sale_commission'              => '0.00',
			'free_listing'                    => '1',
			'listing_fee'                     => '0.00',
			'listing_fee_recurrence'          => '0',
			'seller_tnc'                      => '0',
			'query_form_recipient'            => '2',
			'allowed_price_display'           => array('0', '1', '2'),
			'shipment_itemised'               => '0',
			'shipped_by'                      => 'shop',
			'flat_shipping'                   => '1',
			'shipping_flat_fee'               => '0.00',
			'shippable_location_by_seller'    => '1',
			'tax_on_shipping'                 => '0',
			'itemised_shipping'               => '0',
			'shipping_address_line1'          => '',
			'shipping_address_line2'          => '',
			'shipping_address_line3'          => '',
			'shipping_country'                => '216',
			'shipping_state'                  => '1291',
			'shipping_district'               => '1404',
			'shipping_zip'                    => '1405',
			'ship_to_country'                 => '',
			'ship_to_state'                   => '',
			'ship_to_district'                => '',
			'ship_to_zip'                     => '',
			'bill_to_country'                 => '',
			'bill_to_state'                   => '',
			'bill_to_district'                => '',
			'bill_to_zip'                     => '',
			'allow_guest_ratings'             => '1',
			'allow_non_buyer_ratings'         => '1',
			'show_reviewer_badge'             => '1',
			'allow_ratings_for'               => array('product', 'seller'),
			'allow_review_for'                => array('product', 'seller'),
			'allow_client_authorised_users'   => '0',
			'allow_credit_limit'              => '0',
		);
		$params = new Registry($data);

		if (!$this->helper->access->isSubscribed())
		{
			$params->set('show_brand_footer', 1);
			$params->set('show_rate_us', 1);
			$params->set('show_doc_link', 1);
			$params->set('show_support_link', 1);
			$params->set('show_advertisement', 1);
			$params->set('show_back_to_joomla', 1);
			$params->set('show_sellacious_version', 1);
			$params->set('show_licence_to', 1);
		}

		$data = (object) array('com_sellacious' => $params->toArray());

		return $data;
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param   JForm  $form  A JForm object.
	 * @param   mixed  $data  The data expected for the form.
	 * @param   string $group The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @throws  Exception if there is an error in the form event.
	 *
	 * @see     JFormField
	 *
	 * @since   12.2
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'sellacious')
	{
		if ($this->helper->core->getLicense('free_forever'))
		{
			$form->removeField('premium_trial');
		}

		parent::preprocessForm($form, $data, $group);
	}
}
