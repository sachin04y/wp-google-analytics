<?php
/*
Plugin Name: Goonaly Google Analytics Plugin
Description: Google Analytics Dashboard for WordPress
Text Domain: gnly
Version: 1.0.1
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

! defined( 'GNLY_URI' ) && define( 'GNLY_URI', plugin_dir_url( __FILE__ ) );
! defined( 'GNLY_PATH' ) && define( 'GNLY_PATH', plugin_dir_path( __FILE__ ) );
! defined( 'GNLY_PLUGIN_FILE' ) && define( 'GNLY_PLUGIN_FILE', __FILE__ );


/*--Set Development Mode--*/
define( 'GNLY_DEV_MODE', false );   // false | true
/*--//Set Development Mode--*/

add_action( 'plugins_loaded', function() {

	require_once GNLY_PATH . 'inc/class-gnly-config.php';
	require_once GNLY_PATH . 'inc/class-gnly-gapi-controller.php';
});

add_action( 'admin_enqueue_scripts', function() {

	if ( isset( $_GET['page'] ) && ( 'gnly_setting' === $_GET['page'] ) ) {

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	if ( isset( $_GET['page'] ) && ( 'gnly_report' === $_GET['page'] ) ) {

		wp_enqueue_style( 'gnly-backend-style', GNLY_URI . 'assets/dist/css/backend.min.css', '1.0.0', true );

		$js_path = ( ! GNLY_DEV_MODE ) ? 'assets/dist/js/backend.min.js' : 'assets/build/js/backend.js';

		wp_register_script( 'gnly-googlecharts', 'https://www.gstatic.com/charts/loader.js', array(), null );

		wp_enqueue_script( 'gnly-backend-script', GNLY_URI . $js_path, array( 'gnly-googlecharts', 'jquery' ), '1.0.0', true );

		wp_localize_script( 'gnly-backend-script', 'gnlyObj', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'security' => wp_create_nonce( 'gnly_ajax_handler_nonce' ),
		));
	}
} );

/*Backend settings */
add_action( 'admin_menu', function() {

	add_menu_page( 'Analytics', 'Analytics', 'publish_posts', 'gnly_report', 'gnly_report_page_callback', 'dashicons-chart-area', 10 );

	add_submenu_page( 'gnly_report', 'Settings', 'Settings', 'manage_options', 'gnly_setting', 'gnly_settings_page_callback', 'dashicons-images-alt', 10 );
} );

function gnly_settings_page_callback() {

	require_once GNLY_PATH . 'tools/vendor/autoload.php';
	require_once GNLY_PATH . 'template/admin/class-gnly-goonalytics-settings.php';

	$config  = new GNLY_config();
	$options = $config->options;

	$message = array(
		'status' => '',
		'msg'    => '',
	);

	$fetch_profiles_flag = 0;

	if ( isset( $_POST['gnly-auth-reset'] ) ) {

		if ( isset( $_POST['gnly_security'] ) && wp_verify_nonce( $_POST['gnly_security'], 'gnly_form' ) ) {

			$gapi_controller = new GNLY_GAPI_Controller();
			$gapi_controller->reset_token();

			$options['access_code'] = '';
			$options['profiles']    = array();

			$message['msg'] = __( 'Token Reset and Revoked.', 'gnly' );
			$message['status'] = 'success';

		} else {

			$message['msg'] = __( 'You dont have permission to do this.', 'gnly' );
			$message['status'] = 'error';
		}
	}

	if ( isset( $_POST['gnly-auth-save'] ) && ! empty( $_POST['gnly-auth-save'] ) ) {

		if ( isset( $_POST['gnly_security'] ) && wp_verify_nonce( $_POST['gnly_security'], 'gnly_form' ) ) {

			$access_code = ( isset( $_POST['gnly-access-code'] ) && ! empty( $_POST['gnly-access-code'] ) ) ? wp_unslash( strip_tags( (string) $_POST['gnly-access-code'] ) ) : '';

			if ( false !== $access_code && '' !== $access_code && ! empty( $access_code ) ) {

				require_once GNLY_PATH . 'tools/vendor/autoload.php';

				$client = new Google_Client();
				$client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
				$client->setAccessType( 'offline' );
				$client->setApplicationName( 'GNLY 1.0.0' );
				$client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );

				$client->setClientId( '#CLIENT_ID#' );
				$client->setClientSecret( '#CLIENT_SECRET#' );

				$client->authenticate( $access_code );
				$access_token = $client->getAccessToken();

				if ( is_null( $access_token ) ) {
					$message['msg']    = __( 'One Time Access Code is Invalid. Please generate a new one.', 'gnly' );
					$message['status'] = 'error';

				} else {

					$options['access_code'] = $access_token;
					$message['msg']         = __( 'Token saved', 'gnly' );
					$message['status']      = 'success';
					$fetch_profiles_flag    = 1;  //Init prefetching profiles
				}

			} else {
				$message['msg']    = __( 'Field is empty/invalid.', 'gnly' );
				$message['status'] = 'error';
			}
		} else {

			$message['msg']    = __( 'You dont have permission to do this.', 'gnly' );
			$message['status'] = 'error';
		}

	}

	if ( isset( $_POST['gnly-setting-save'] ) ) {

		$_defaults = array(
			'color_scheme'   => '#2149f3',
			'items_per_page' => '5',
			'permission'     => array(),
		);

		if ( isset( $_POST['gnly_security'] ) && wp_verify_nonce( $_POST['gnly_security'], 'gnly_form' ) ) {

			$color_scheme   = ( isset( $_POST['gnly-color-scheme'] ) && ! empty( $_POST['gnly-color-scheme'] ) ) ? wp_unslash( strip_tags( (string) $_POST['gnly-color-scheme'] ) ) : $_defaults['color_scheme'];

			$items_per_page = ( isset( $_POST['gnly-items-per-page'] ) && ! empty( $_POST['gnly-items-per-page'] ) ) ? wp_unslash( strip_tags( (string) $_POST['gnly-items-per-page'] ) ) : $_defaults['items_per_page'];

			$permission     = ( isset( $_POST['gnly-permission'] ) && ! empty( $_POST['gnly-permission'] ) ) ? wp_unslash( $_POST['gnly-permission'] ) : $_defaults['permission'];

			$options['color_scheme']   = $color_scheme;
			$options['items_per_page'] = $items_per_page;
			$options['permission']     = $permission;

			$message = array(
				'status' => 'success',
				'msg'    => 'WooHoo! Settings are updated successful !',
			);
		} else {

			$message['msg']    = __( 'You dont have permission to do this.', 'gnly' );
			$message['status'] = 'error';
		}
	}

	if ( ! empty( $_POST ) && 'error' !== $message['status'] ) {

		$config->set_options( $options );

		if ( $fetch_profiles_flag ) {

			/* GNLY_GAPI_Controller this need GNLY_Config class to save all options first. */

			$gapi_controller         = new GNLY_GAPI_Controller();
			$prof_option['profiles'] = $gapi_controller->profiles();
			$config->set_options( $prof_option );
		}
	}

	new Wk_Goonalytics_Settings( $message );
}
/*//Backend settings */

function gnly_report_page_callback() {

	require_once GNLY_PATH . 'tools/vendor/autoload.php';
	require_once GNLY_PATH . 'template/class-gnly-goonalytics-report.php';

	new Wk_Goonalytics_Report();
}

/*------Add Extra Field In User Profile Page--------*/
add_action( 'show_user_profile', 'gnly_profile_permission_cb' );
add_action( 'edit_user_profile', 'gnly_profile_permission_cb' );

function gnly_profile_permission_cb( $user ) {

	if ( current_user_can( 'manage_options' ) ) {

		$config   = new GNLY_config();
		$profiles = $config->options['profiles'];
		?>
		<h3>Webkul Anlytics Profile Permission</h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="user_location"><?php _e('Select Profiles'); ?></label>
				</th>
				<td>
					<?php
					$pem_profile = maybe_unserialize( get_user_meta( $user->ID, '__gnly_profile_pem' )[0] );

					foreach( $profiles as $prof ) {
						
						$checked = ( ! is_null( $pem_profile ) && in_array( $prof['profileId'], $pem_profile ) ) ? ' checked' : '';
						
						echo '<label><input type="checkbox" name="gnly-profile-pem[]" value="' . esc_attr( $prof['profileId'] ) . '" ' . $checked . ' /><span>' . esc_html( $prof['websiteUrl'] ) . '</span></label><br>';
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

add_action( 'personal_options_update', 'save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );

function save_extra_user_profile_fields( $user_id ) {

	if ( current_user_can( 'manage_options' ) ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return false;
		}

		update_user_meta( $user_id, '__gnly_profile_pem', maybe_serialize( $_POST['gnly-profile-pem'] ) );
	}
}


add_action( 'wp_ajax_gnly_analytics_report', 'gnly_ajax_handler' );

function gnly_ajax_handler() {

	check_ajax_referer( 'gnly_ajax_handler_nonce', 'security' );

	if ( ! isset( $_POST['projectId'] ) || empty( $_POST['projectId'] )
	|| ! isset( $_POST['from'] ) || empty( $_POST['from'] )
	|| ! isset( $_POST['to'] ) || empty( $_POST['to'] ) ) {
		die;
	}

	$profile_id = trim( $_POST['projectId'] );
	$from       = trim( $_POST['from'] );
	$to         = trim( $_POST['to'] );
	$report     = array();


	$pem_profile = maybe_unserialize( get_user_meta( get_current_user_id(), '__gnly_profile_pem' )[0] );

	if ( ! is_null( $pem_profile ) ) {

		if ( in_array( $profile_id, $pem_profile ) ) {
				
			try {
		
				$gapi_controller       = new GNLY_GAPI_Controller();
				$dimensions            = 'ga:pagePath';
				$options               = array( 'dimensions' => $dimensions );
				$options['dimensions'] = 'ga:pagePath';
				$data                  = $gapi_controller->analytics->data_ga->get( 'ga:' . $profile_id , $from, $to, 'ga:pageviews,ga:uniquePageviews,ga:avgTimeOnPage,ga:entrances,ga:bounceRate,ga:exitRate,ga:pageValue', $options );
				$all_rows              = (array) $data->getRows();
		
				$col_counter = 0;
		
				$serialized_columns = array_map( function ( $column ) use ( &$col_counter, &$all_rows ) {
	
					$column      = (array) $column;
					$column_name = $column['name'];
		
					switch ( $column['dataType'] ) {
		
						case 'PERCENT' :
							foreach ( $all_rows as $idx => $row ) {
								$all_rows[ $idx ][ $col_counter ] = round( $row[ $col_counter ], 2 ) . '%';
							}
							break;
		
						case 'INTEGER' :
							foreach ( $all_rows as $idx => $row ) {
								$all_rows[ $idx ][ $col_counter ] = round( $row[ $col_counter ], 2 );
							}
							break;
		
						case 'STRING' :
							foreach ( $all_rows as $idx => $row ) {
								$all_rows[ $idx ][ $col_counter ] = $row[ $col_counter ];
							}
							break;
		
						case 'CURRENCY' :
							foreach ( $all_rows as $idx => $row ) {
								$all_rows[ $idx ][ $col_counter ] = '$' . round( $row[ $col_counter ], 2 );
							}
							break;
		
						case 'TIME' :
							foreach ( $all_rows as $idx => $row ) {
								$all_rows[ $idx ][ $col_counter ] = date( 'H:i:s', (int) $row[ $col_counter ] );
							}
							break;
		
						default :
							break;
					}
		
					$col_counter ++;
		
					return array( 'label' => str_replace( 'ga:', '', $column_name ), 'type' => 'string' );
		
				}, (array) $data->columnHeaders );
			
				$report[] = $serialized_columns;
		
				foreach ( $all_rows as $row ) {
					$report[] = (array) $row;
				}
		
				wp_send_json( $report );
		
			} catch ( Exception $e ) {
				_gnly_xhr_response( $e->getMessage() );
			}
			die;

		} else {
			_gnly_xhr_response( 'User not authorized to do so!' );
		}

	} else {
		_gnly_xhr_response( 'User not authorized to do so!' );
	}

	die;
}

function _gnly_xhr_response( $data, $status = 1 ) {
	wp_send_json( (object) [
		'body'   => $data,
		'status' => $status,
	] );
	return;
}
