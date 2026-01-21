<?php
/**
 * Google Maps Integration
 *
 * @package Business_Directory_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BDB_Google_Maps {

	private $api_key;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_location_meta_box' ) );
		add_action( 'save_post_business_listing', array( $this, 'save_location_meta' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_maps_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_maps_scripts' ) );
		add_shortcode( 'bdb_map', array( $this, 'map_shortcode' ) );
		add_action( 'wp_ajax_bdb_geocode_address', array( $this, 'geocode_address' ) );
		add_action( 'wp_ajax_bdb_get_nearby_businesses', array( $this, 'get_nearby_businesses' ) );
		add_action( 'wp_ajax_nopriv_bdb_get_nearby_businesses', array( $this, 'get_nearby_businesses' ) );
	}

	public function init() {
		global $bdb_plugin;
		$this->api_key = $bdb_plugin->db->get_setting( 'google_maps_api_key', '' );
	}

	/**
	 * Add location meta box
	 */
	public function add_location_meta_box() {
		add_meta_box(
			'bdb_location',
			'Business Location',
			array( $this, 'location_meta_box_callback' ),
			'business_listing',
			'normal',
			'high'
		);
	}

	/**
	 * Location meta box callback
	 */
	public function location_meta_box_callback( $post ) {
		wp_nonce_field( 'bdb_location_meta', 'bdb_location_nonce' );

		$address = get_post_meta( $post->ID, '_business_address', true );
		$city = get_post_meta( $post->ID, '_business_city', true );
		$state = get_post_meta( $post->ID, '_business_state', true );
		$zip = get_post_meta( $post->ID, '_business_zip', true );
		$latitude = get_post_meta( $post->ID, '_business_latitude', true );
		$longitude = get_post_meta( $post->ID, '_business_longitude', true );
		?>
		<div class="bdb-location-fields">
			<p>
				<label for="business_address"><strong>Street Address:</strong></label><br>
				<input type="text" id="business_address" name="business_address" 
					value="<?php echo esc_attr( $address ); ?>" 
					style="width: 100%;" placeholder="123 Main Street">
			</p>

			<div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
				<p>
					<label for="business_city"><strong>City:</strong></label><br>
					<input type="text" id="business_city" name="business_city" 
						value="<?php echo esc_attr( $city ); ?>" 
						style="width: 100%;">
				</p>

				<p>
					<label for="business_state"><strong>State:</strong></label><br>
					<input type="text" id="business_state" name="business_state" 
						value="<?php echo esc_attr( $state ); ?>" 
						style="width: 100%;">
				</p>

				<p>
					<label for="business_zip"><strong>ZIP:</strong></label><br>
					<input type="text" id="business_zip" name="business_zip" 
						value="<?php echo esc_attr( $zip ); ?>" 
						style="width: 100%;">
				</p>
			</div>

			<p>
				<button type="button" class="button" id="geocodeAddress">📍 Get Coordinates from Address</button>
				<span id="geocodeStatus"></span>
			</p>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
				<p>
					<label for="business_latitude"><strong>Latitude:</strong></label><br>
					<input type="text" id="business_latitude" name="business_latitude" 
						value="<?php echo esc_attr( $latitude ); ?>" 
						style="width: 100%;" readonly>
				</p>

				<p>
					<label for="business_longitude"><strong>Longitude:</strong></label><br>
					<input type="text" id="business_longitude" name="business_longitude" 
						value="<?php echo esc_attr( $longitude ); ?>" 
						style="width: 100%;" readonly>
				</p>
			</div>

			<?php if ( $latitude && $longitude ) : ?>
				<div id="locationPreviewMap" style="width: 100%; height: 300px; margin-top: 15px; border: 1px solid #ddd; border-radius: 4px;"></div>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#geocodeAddress').on('click', function() {
				const button = $(this);
				const address = $('#business_address').val();
				const city = $('#business_city').val();
				const state = $('#business_state').val();
				const zip = $('#business_zip').val();

				if (!address || !city) {
					alert('Please enter at least street address and city');
					return;
				}

				const fullAddress = address + ', ' + city + ', ' + state + ' ' + zip;
				
				button.prop('disabled', true);
				$('#geocodeStatus').html('<span style="color: #666;">Geocoding...</span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'bdb_geocode_address',
						address: fullAddress,
						nonce: '<?php echo wp_create_nonce( 'bdb_geocode' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$('#business_latitude').val(response.data.latitude);
							$('#business_longitude').val(response.data.longitude);
							$('#geocodeStatus').html('<span style="color: green;">✓ Coordinates found!</span>');
							
							// Reload to show map
							setTimeout(function() {
								location.reload();
							}, 1000);
						} else {
							$('#geocodeStatus').html('<span style="color: red;">✗ ' + response.data.message + '</span>');
						}
						button.prop('disabled', false);
					},
					error: function() {
						$('#geocodeStatus').html('<span style="color: red;">✗ Error occurred</span>');
						button.prop('disabled', false);
					}
				});
			});

			// Initialize preview map if coordinates exist
			<?php if ( $latitude && $longitude && $this->api_key ) : ?>
			function initPreviewMap() {
				const map = new google.maps.Map(document.getElementById('locationPreviewMap'), {
					center: { lat: <?php echo floatval( $latitude ); ?>, lng: <?php echo floatval( $longitude ); ?> },
					zoom: 15
				});

				new google.maps.Marker({
					position: { lat: <?php echo floatval( $latitude ); ?>, lng: <?php echo floatval( $longitude ); ?> },
					map: map,
					title: '<?php echo esc_js( get_the_title() ); ?>'
				});
			}

			if (typeof google !== 'undefined' && google.maps) {
				initPreviewMap();
			}
			<?php endif; ?>
		});
		</script>
		<?php
	}

	/**
	 * Save location meta
	 */
	public function save_location_meta( $post_id ) {
		if ( ! isset( $_POST['bdb_location_nonce'] ) || ! wp_verify_nonce( $_POST['bdb_location_nonce'], 'bdb_location_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array( 'address', 'city', 'state', 'zip', 'latitude', 'longitude' );

		foreach ( $fields as $field ) {
			$key = 'business_' . $field;
			if ( isset( $_POST[$key] ) ) {
				update_post_meta( $post_id, '_' . $key, sanitize_text_field( $_POST[$key] ) );
			}
		}
	}

	/**
	 * Geocode address via AJAX
	 */
	public function geocode_address() {
		check_ajax_referer( 'bdb_geocode', 'nonce' );

		$address = sanitize_text_field( $_POST['address'] );

		if ( ! $this->api_key ) {
			wp_send_json_error( array( 'message' => 'Google Maps API key not configured' ) );
		}

		$url = add_query_arg( array(
			'address' => urlencode( $address ),
			'key' => $this->api_key
		), 'https://maps.googleapis.com/maps/api/geocode/json' );

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => 'Failed to connect to Google Maps API' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $body['status'] === 'OK' && ! empty( $body['results'][0] ) ) {
			$location = $body['results'][0]['geometry']['location'];
			wp_send_json_success( array(
				'latitude' => $location['lat'],
				'longitude' => $location['lng']
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Address not found' ) );
		}
	}

	/**
	 * Enqueue maps scripts
	 */
	public function enqueue_maps_scripts() {
		if ( $this->api_key ) {
			wp_enqueue_script(
				'google-maps',
				'https://maps.googleapis.com/maps/api/js?key=' . $this->api_key . '&libraries=places',
				array(),
				null,
				true
			);

			wp_enqueue_script(
				'bdb-maps',
				BDB_PLUGIN_URL . 'public/js/maps.js',
				array( 'jquery', 'google-maps' ),
				'1.0.0',
				true
			);

			wp_localize_script( 'bdb-maps', 'bdbMaps', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'bdb_maps_nonce' ),
			) );
		}
	}

	/**
	 * Enqueue admin maps scripts
	 */
	public function enqueue_admin_maps_scripts( $hook ) {
		global $post;

		if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
			if ( isset( $post ) && $post->post_type === 'business_listing' && $this->api_key ) {
				wp_enqueue_script(
					'google-maps-admin',
					'https://maps.googleapis.com/maps/api/js?key=' . $this->api_key,
					array(),
					null,
					true
				);
			}
		}
	}

	/**
	 * Get nearby businesses
	 */
	public function get_nearby_businesses() {
		$latitude = floatval( $_POST['latitude'] );
		$longitude = floatval( $_POST['longitude'] );
		$radius = intval( $_POST['radius'] ?? 10 ); // miles

		$args = array(
			'post_type' => 'business_listing',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => '_business_latitude',
					'compare' => 'EXISTS'
				),
				array(
					'key' => '_business_longitude',
					'compare' => 'EXISTS'
				)
			)
		);

		$businesses = get_posts( $args );
		$nearby = array();

		foreach ( $businesses as $business ) {
			$bus_lat = floatval( get_post_meta( $business->ID, '_business_latitude', true ) );
			$bus_lng = floatval( get_post_meta( $business->ID, '_business_longitude', true ) );

			$distance = $this->calculate_distance( $latitude, $longitude, $bus_lat, $bus_lng );

			if ( $distance <= $radius ) {
				$nearby[] = array(
					'id' => $business->ID,
					'title' => $business->post_title,
					'latitude' => $bus_lat,
					'longitude' => $bus_lng,
					'distance' => round( $distance, 2 ),
					'url' => get_permalink( $business->ID ),
					'thumbnail' => get_the_post_thumbnail_url( $business->ID, 'thumbnail' )
				);
			}
		}

		// Sort by distance
		usort( $nearby, function( $a, $b ) {
			return $a['distance'] <=> $b['distance'];
		} );

		wp_send_json_success( $nearby );
	}

	/**
	 * Calculate distance between two coordinates (Haversine formula)
	 */
	private function calculate_distance( $lat1, $lon1, $lat2, $lon2 ) {
		$earth_radius = 3959; // miles

		$dLat = deg2rad( $lat2 - $lat1 );
		$dLon = deg2rad( $lon2 - $lon1 );

		$a = sin( $dLat / 2 ) * sin( $dLat / 2 ) +
			cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
			sin( $dLon / 2 ) * sin( $dLon / 2 );

		$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

		return $earth_radius * $c;
	}

	/**
	 * Map shortcode
	 */
	public function map_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'business_id' => get_the_ID(),
			'height' => '400px',
			'zoom' => 15
		), $atts );

		$latitude = get_post_meta( $atts['business_id'], '_business_latitude', true );
		$longitude = get_post_meta( $atts['business_id'], '_business_longitude', true );

		if ( ! $latitude || ! $longitude || ! $this->api_key ) {
			return '';
		}

		$map_id = 'bdb-map-' . uniqid();

		ob_start();
		?>
		<div id="<?php echo esc_attr( $map_id ); ?>" class="bdb-google-map" style="width: 100%; height: <?php echo esc_attr( $atts['height'] ); ?>; border-radius: 8px; overflow: hidden;"></div>
		<script>
		(function() {
			function initMap() {
				const mapDiv = document.getElementById('<?php echo esc_js( $map_id ); ?>');
				if (!mapDiv) return;

				const location = { 
					lat: <?php echo floatval( $latitude ); ?>, 
					lng: <?php echo floatval( $longitude ); ?> 
				};

				const map = new google.maps.Map(mapDiv, {
					center: location,
					zoom: <?php echo intval( $atts['zoom'] ); ?>,
					mapTypeControl: true,
					streetViewControl: true,
					fullscreenControl: true
				});

				const marker = new google.maps.Marker({
					position: location,
					map: map,
					title: '<?php echo esc_js( get_the_title( $atts['business_id'] ) ); ?>',
					animation: google.maps.Animation.DROP
				});

				const infoWindow = new google.maps.InfoWindow({
					content: '<div style="padding: 10px;"><strong><?php echo esc_js( get_the_title( $atts['business_id'] ) ); ?></strong><br><a href="<?php echo esc_js( get_permalink( $atts['business_id'] ) ); ?>">View Details</a></div>'
				});

				marker.addListener('click', function() {
					infoWindow.open(map, marker);
				});
			}

			if (typeof google !== 'undefined' && google.maps) {
				initMap();
			} else {
				window.addEventListener('load', initMap);
			}
		})();
		</script>
		<?php
		return ob_get_clean();
	}
}

new BDB_Google_Maps();
