<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wk_Goonalytics_Report' ) ) {

	class Wk_Goonalytics_Report {

		public $profileid;

		public $gapi_controller;

		public $config;

		public function __construct( $message = false ) {

			$this->gapi_controller = new GNLY_GAPI_Controller();
			$this->config          = new GNLY_config();
			$this->options         = $this->config->options;

			if ( ! empty( $this->config->options['access_code'] ) ) {

				if ( ! empty( $this->config->options['profiles'] ) ) {

					$this->profiles = $this->config->options['profiles'];

				} else {

					$this->profiles = $this->gapi_controller->profiles();
				}

			}

			if ( is_user_logged_in() ) {

				$current_user = wp_get_current_user();
				$roles        = (array) $current_user->roles;

				if ( ( current_user_can( 'manage_options' ) ) || count( array_intersect( $roles, $this->options['permission'] ) > 0 ) ) {
					$this->report_view( $message );

				} else {
					echo '<br><p><b>' . __( 'You do not have permission to access the Analytics', 'gnly' ) . '</b></p>';
				}
			}
		}

		public function report_view( $message ) {

			?>
			<section class="wrap" id="gnly-analytics-report-area">

				<h2><?php esc_html_e( 'Analytics Report', 'gnly' ); ?></h2>
				<hr></br></br>

				<?php
				if ( empty( $this->config->options['access_code'] ) ) {

					echo '<br><p>' . __( 'This plugin needs an authorization:', 'gnly' ) . '</p>';
					if ( ! current_user_can( 'manage_options' ) ) {
						echo '<b></b>Please contact site admin to authorize the plugin</b>';
					} else {
						echo '<form action="' . menu_page_url( 'gnly_setting', false ) . '" method="POST">' . get_submit_button( __( 'Authorize Plugin', 'gnly' ), 'secondary' ) . '</form>';
					}

					return;
				}
				?>
				<div class="gnly-controls">
					<input id="gnly-per-page" type="hidden" value="<?php echo esc_attr( $this->options['items_per_page'] ); ?>" />
					<?php

					$pem_profile = maybe_unserialize( get_user_meta( get_current_user_id(), '__gnly_profile_pem' )[0] );

					$allowed_prof_count = 0;

					if ( ! is_null( $pem_profile ) ) {

						echo '<select class="gnly-report-filter" id="profileId">';

						foreach ( $this->profiles as $key => $prof ) {

							if ( ! is_null( $pem_profile ) && in_array( $prof['profileId'], $pem_profile ) ) {
								
								echo '<option value="' . esc_attr( $prof['profileId'] ) . '" >' . esc_attr( $prof['websiteUrl'] ) . '</option>';

								$allowed_prof_count ++;
							}
						}

						echo '</select>';

						if ( 0 === $allowed_prof_count ) {
							echo '<br><h2><b>' . __( 'You are not authorized to view Analytics.', 'gnly' ) . '</b></h2>';

							return;
						}
					} else {
						echo '<br><h2><b>' . __( 'You are not authorized to view Analytics.', 'gnly' ) . '</b></h2>';
						return;
					}
					?>

					<select class="gnly-report-filter" id="period">
						<option value="yesterday">Yesterday</option>
						<option value="7daysAgo" selected="selected">Last 7 Days</option>
						<option value="14daysAgo">Last 14 Days</option>
						<option value="30daysAgo">Last 30 Days</option>
						<option value="90daysAgo">Last 90 Days</option>
						<option value="365daysAgo">One Year</option>
					</select>
				</div><br><br>

				<div id="gnly-dashboard">
					<div id="gnly-report-search-filter"></div>
					<span>Category: </span>
					<select id="gnly-cat-filter-spoof">
						<option value="" selected="selected">All</option>
						<option value="odoo">Odoo</option>
						<option value="prestashop">Prestashop</option>
						<option value="shopify">Shopify</option>
						<option value="woocommerce">WooCommerce</option>
						<option value="bagisto">Bagisto</option>
						<option value="opencart">Opencart</option>
						<option value="magento">Magento</option>
						<option value="magento 2">Magento 2</option>
						<option value="mobikul">Mobikul</option>
					</select>
					<span id="gnly-report-cat-filter"></span><br><br>
					<div class="gnly-report-wrapper">
						<div class="gnly-loader" state="on"><div class="bar"></div></div>
						<div id="view-selector-container"></div>
					</div>
				</div>
			</section>
			<?php
		}
	}
}