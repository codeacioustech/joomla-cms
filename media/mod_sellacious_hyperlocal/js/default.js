var ModSellaciousHyperLocal = function () {
	this.options = {
	};

	this.geocoder = null;
	this.geojax   = null;

	return this;
};

(function ($) {
	ModSellaciousHyperLocal.prototype = {
		init: function() {
			var $this = this;
			var $geo_finder_btn = '#' + $this.options.geo_finder_btn;

			$this.geocoder = new google.maps.Geocoder();

			$($geo_finder_btn).on('click', function () {
				$this.geolocate();
			});
		},
		setup: function (options) {

			$.extend(this.options, options);

			var $this = this;
			var $location_field = '#' + $this.options.location_field;
			var $location_value = '#' + $this.options.location_value;

			$($location_field).autocomplete({
				source: function( request, response ) {
					var pData = {
						option: 'com_ajax',
						module: 'sellacious_hyperlocal',
						method: 'getAutoCompleteSearch',
						format: 'json',
						term: request.term,
						parent_id: 1,
						types: $($location_field).data('autofill-components').split(','),
						list_start: 0,
						list_limit: 5
					};
					$.ajax({
						url: "index.php",
						type: 'POST',
						dataType: "json",
						data: pData,
						cache: false,
						success: function(data) {
							response(data);
						}
					});
				},
				select: function(event, ui) {
					$($location_field).val(ui.item.value);
					$($location_value).val(ui.item.id);

					$this.setAddress(ui.item.id, ui.item.value, $this.setBounds);

					return false;
				},
				minLength: 3
			});
		},
		geolocate: function(location) {
			var $this = this;

			if ($this.options.params.browser_detect == 1 && (location === undefined || !Object.keys(location).length))
			{
				if (navigator.geolocation) {
					var $this = this;
					var $geo_finder_btn = '#' + $this.options.geo_finder_btn;

					$($geo_finder_btn).attr('disabled', true);
					$($geo_finder_btn).text(Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_DETECTING_LOCATION'));

					navigator.geolocation.getCurrentPosition(function(position) {
						var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

						$this.geocoder.geocode({
							'latLng': latlng
						}, function (results, status) {
							if (status === google.maps.GeocoderStatus.OK) {
								if (results[1]) {
									$this.getAddress(results[1].address_components, results[1].geometry.location.lat(), results[1].geometry.location.lng(), $this.setBounds);
								} else {
									Joomla.renderMessages({error: [Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_GEOCODE_NO_RESULTS_FOUND')]});
								}
							} else {
								alert(Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_GEOCODE_FAILED') + status);
							}
						});
					});
				}
			}
		},
		setAddress: function(id, address, callback) {
			var $this = this;

			if ($this.geojax) $this.geojax.abort();
			var data = {
				option : 'com_ajax',
				module : 'sellacious_hyperlocal',
				format : 'json',
				method : 'setAddress',
				id     : id,
				address: address,
				params : $this.options.params,
			};

			$this.geojax = $.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				cache: false,
				data: data,
				beforeSend: function () {
				},
				complete: function () {
				}
			}).done(function (response) {
				if (response.success) {
					if (typeof callback == 'function') callback(response, $this);
				} else {
					Joomla.renderMessages({warning: [response.message]});
				}
			}).fail(function (jqXHR) {
				Joomla.renderMessages({warning: [Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_SET_ADDRESS_FAILED')]});
				console.log(jqXHR.responseText);
			});
		},
		resetAddress: function(callback) {
			var $this = this;

			if ($this.geojax) $this.geojax.abort();
			var data = {
				option : 'com_ajax',
				module : 'sellacious_hyperlocal',
				format : 'json',
				method : 'resetAddress',
				params : $this.options.params,
			};

			$this.geojax = $.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				cache: false,
				data: data,
				beforeSend: function () {
				},
				complete: function () {
				}
			}).done(function (response) {
				if (response.success) {
					if (typeof callback == 'function') callback(response, $this);
				} else {
					Joomla.renderMessages({warning: [response.message]});
				}
			}).fail(function (jqXHR) {
				console.log(jqXHR.responseText);
			});
		},
		setShippableFilter: function(address, callback) {
			var $this = this;

			if ($this.geojax) $this.geojax.abort();
			var data = {
				option : 'com_ajax',
				module : 'sellacious_hyperlocal',
				format : 'json',
				method : 'setShippableFilter',
				address: address,
				params : $this.options.params,
			};

			$this.geojax = $.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				cache: false,
				data: data,
				beforeSend: function () {
				},
				complete: function () {
				}
			}).done(function (response) {
				if (response.success) {
					if (typeof callback == 'function') callback(response, $this);
				} else {
					Joomla.renderMessages({warning: [response.message]});
				}
			}).fail(function (jqXHR) {
				Joomla.renderMessages({warning: [Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_SET_ADDRESS_FAILED')]});
				console.log(jqXHR.responseText);
			});
		},
		setLocationFilter: function(address, callback) {
			var $this = this;

			if ($this.geojax) $this.geojax.abort();
			var data = {
				option : 'com_ajax',
				module : 'sellacious_hyperlocal',
				format : 'json',
				method : 'setLocationFilter',
				address: address,
				params : $this.options.params,
			};

			$this.geojax = $.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				cache: false,
				data: data,
				beforeSend: function () {
				},
				complete: function () {
				}
			}).done(function (response) {
				if (response.success) {
					if (typeof callback == 'function') callback(response, $this);
				} else {
					Joomla.renderMessages({warning: [response.message]});
				}
			}).fail(function (jqXHR) {
				Joomla.renderMessages({warning: [Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_SET_ADDRESS_FAILED')]});
				console.log(jqXHR.responseText);
			});
		},
		getAddress: function(components, lat, lng, callback) {
			var $this = this;
			var $location_field = '#' + $this.options.location_field;
			var $location_value = '#' + $this.options.location_value;
			var $geo_finder_btn = '#' + $this.options.geo_finder_btn;
			var $params         = $this.options.params;
			var $view           = $this.options.view;

			if ($this.geojax) $this.geojax.abort();
			var data = {
				option: 'com_ajax',
				module: 'sellacious_hyperlocal',
				format: 'json',
				method: 'getAddress',
				params: $params,
				autofill: $($location_field).data('autofill-components'),
				address_components: components,
				lat: lat,
				lng: lng,
			};

			$this.geojax = $.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				cache: false,
				data: data,
				beforeSend: function () {
				},
				complete: function () {
					$($geo_finder_btn).attr('disabled', false);
					$($geo_finder_btn).text(Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_GET_CURRENT_LOCATION'));
				}
			}).done(function (response) {
				if (response.success) {
					$($location_field).val(response.data.address);
					$($location_value).val(response.data.id);

					if (typeof callback == 'function') callback(response, $this);
				} else {
					Joomla.renderMessages({warning: [response.message]});
				}
			}).fail(function (jqXHR) {
				Joomla.renderMessages({warning: [Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_FETCH_ADDRESS_FAILED')]});
				console.log(jqXHR.responseText);
			});
		},
		setBounds: function (response, object) {
			var $this  = this;
			var latlng = new google.maps.LatLng(response.data.lat, response.data.long);

			// Get Product bounds
			var productCircle = new google.maps.Circle({
				center: latlng,
				radius: object.options.product_distance
			});
			var productBounds   = productCircle.getBounds();
			var hlProductBounds = {
				north: Math.round(productBounds.getNorthEast().lat() * 10000) / 10000,
				east : Math.round(productBounds.getNorthEast().lng() * 10000) / 10000,
				south: Math.round(productBounds.getSouthWest().lat() * 10000) / 10000,
				west : Math.round(productBounds.getSouthWest().lng() * 10000) / 10000,
			}

			// Get Store bounds
			var storeCircle = new google.maps.Circle({
				center: latlng,
				radius: object.options.store_distance
			});
			var storeBounds   = storeCircle.getBounds();
			var hlStoreBounds = {
				north: Math.round(storeBounds.getNorthEast().lat() * 10000) / 10000,
				east : Math.round(storeBounds.getNorthEast().lng() * 10000) / 10000,
				south: Math.round(storeBounds.getSouthWest().lat() * 10000) / 10000,
				west : Math.round(storeBounds.getSouthWest().lng() * 10000) / 10000,
			}

			if ($this.geojax) $this.geojax.abort();
			var data = {
				option: 'com_ajax',
				module: 'sellacious_hyperlocal',
				format: 'json',
				method: 'setBounds',
				product_bounds: hlProductBounds,
				store_bounds  : hlStoreBounds
			};

			$this.geojax = $.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				cache: false,
				data: data,
				beforeSend: function () {
				},
				complete: function () {
				}
			}).done(function (response) {
				if (response.success) {
					window.location.reload();
				} else {
					Joomla.renderMessages({warning: [response.message]});
				}
			}).fail(function (jqXHR) {
				Joomla.renderMessages({warning: [Joomla.JText._('MOD_SELLACIOUS_HYPERLOCAL_FETCH_ADDRESS_FAILED')]});
				console.log(jqXHR.responseText);
			});
		}
	}
})(jQuery);

