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
defined('_JEXEC') or die;

/**
 * Product Table class
 */
class SellaciousTableProduct extends SellaciousTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  A database connector object
	 */
	public function __construct(&$db)
	{
		$this->_array_fields = array('features', 'params', 'tags');

		parent::__construct('#__sellacious_products', 'id', $db);
	}

	/**
	 * Override getUniqueConditions, We don't want the parent's logic here
	 */
	protected function getUniqueConditions()
	{
		// Todo: I doubt this shouldn't be here if alias needs to be checked
		$conditions   = array();
		$conditions[] = array('alias' => $this->get('alias'));

		return $conditions;
	}
}
