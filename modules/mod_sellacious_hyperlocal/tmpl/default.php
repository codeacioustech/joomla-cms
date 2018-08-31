<?php
/**
 * @version     1.6.0
 * @package     Sellacious Hyperlocal Module
 *
 * @copyright   Copyright (C) 2012-2018 Bhartiy Web Technologies. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Aditya Chakraborty <info@bhartiy.com> - http://www.bhartiy.com
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

JHtml::_('jquery.framework');

JHtml::_('stylesheet', 'mod_sellacious_hyperlocal/default.css', null, true);
JHtml::_('stylesheet', 'mod_sellacious_hyperlocal/jquery.autocomplete.ui.css', null, true);

JText::script('MOD_SELLACIOUS_HYPERLOCAL_GEOCODE_NO_RESULTS_FOUND');
JText::script('MOD_SELLACIOUS_HYPERLOCAL_GEOCODE_FAILED');
JText::script('MOD_SELLACIOUS_HYPERLOCAL_FETCH_ADDRESS_FAILED');
JText::script('MOD_SELLACIOUS_HYPERLOCAL_GET_CURRENT_LOCATION');
JText::script('MOD_SELLACIOUS_HYPERLOCAL_DETECTING_LOCATION');
JText::script('MOD_SELLACIOUS_HYPERLOCAL_SET_ADDRESS_FAILED');

// TODO: Get google api key from config
JHtml::_('script', 'https://maps.googleapis.com/maps/api/js?key=' . $params->get('google_api_key') . '&libraries=places', false, false);
JHtml::_('script', 'mod_sellacious_hyperlocal/jquery.autocomplete.ui.js', false, true);
JHtml::_('script', 'mod_sellacious_hyperlocal/default.js', false, true);

$app = JFactory::getApplication();

$component_order = array('locality', 'city', 'district', 'state', 'country', 'zip');
$components = array_intersect($component_order,$autofill_components);

$location   = $app->getUserState('hyperlocal_location', array());
$hyperlocal = json_encode($location);

$args   = array(
	'location_field'   => 'hyperlocation',
	'location_value'   => 'hyperlocation_id',
	'geo_finder_btn'   => 'detect-location',
	'product_distance' => $productDistance,
	'store_distance'   => $storeDistance,
	'params'           => $params->toArray(),
);
$args   = json_encode($args);
$script = <<<JS
		jQuery(document).ready(function($) {
			var o = new ModSellaciousHyperLocal;
			o.setup({$args});
			o.init();
			o.geolocate({$hyperlocal});
			
			$('.btn-filter_shippable').prop('onclick',null).off('click');
			$('.btn-filter_shippable').on('click', function (e) {
				var value = $('#filter_shippable_text').val();
				var btn   = $(this);
				
				o.setShippableFilter(value, function(){
					btn.closest('form').submit();
				});
				
				return false;
			});
			
			$('.btn-filter_shop_location').prop('onclick',null).off('click');
			$('.btn-filter_shop_location').on('click', function (e) {
				var value = $('#filter_store_location_custom_text').val();
				var btn   = $(this);
				
				o.setLocationFilter(value, function(){
					btn.closest('form').submit();
				});
				
				return false;
			});
			
			$('#reset-location').on('click', function(e) {
			  e.preventDefault();
			  
			  $('#hyperlocation').val('');
			  $('#hyperlocation_id').val('');
			  
			  o.resetAddress(function() {
			    window.location.reload();
			  });
			});
		});
JS;
JFactory::getDocument()->addScriptDeclaration($script);
?>
<div class="mod_sellacious_hyperlocation">
	<input id="hyperlocation" name="hyperlocation" placeholder="Enter your address" type="text" data-autofill-components="<?php echo implode(',', $components);?>" value="<?php echo isset($location['address']) ? $location['address'] : '';?>">
	<input type="hidden" id="hyperlocation_id" name="hyperlocation_id" value="<?php echo isset($location['id']) ? $location['id'] : '';?>">

	<?php if ($browser_detect): ?>
	<button type="button" class="btn btn-primary" id="detect-location"><?php echo JText::_('MOD_SELLACIOUS_HYPERLOCAL_GET_CURRENT_LOCATION');?></button>
	<?php else: ?>
		<button type="button" class="btn btn-primary" id="reset-location"><?php echo JText::_('MOD_SELLACIOUS_HYPERLOCAL_RESET_LOCATION');?></button>
	<?php endif; ?>
</div>
