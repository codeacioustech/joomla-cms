<?php
/**
 * @version     1.6.0
 * @package     sellacious
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Izhar Aazmi <info@bhartiy.com> - http://www.bhartiy.com
 */
// no direct access.
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('List');

/**
 * Form Field class for the list of languages.
 *
 * @since  1.6.0
 */
class JFormFieldProductLanguages extends JFormFieldList
{
	/**
	 * The field type.
	 *
	 * @var     string
	 *
	 * @since   1.6.0
	 */
	protected $type = 'ProductLanguages';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @throws  \Exception
	 *
	 * @since   1.6.0
	 */
	protected function getOptions()
	{
		$helper  = SellaciousHelper::getInstance();
		$options = parent::getOptions();

		$languages = $helper->product->getLanguage();

		$options = array_merge($options, $languages);

		return $options;
	}
}
