<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wk_Goonalytics_Settings' ) ) {

	class Wk_Goonalytics_Settings {

		public $config;

		public $options;

		public function __construct( $message = false ) {

			$this->config        = new GNLY_config();
			$this->options = $this->config->options;

			$general_settings = ( false === $this->options || '' === $this->options || empty( $this->options ) ) ? array() : ( $this->options );

			$this->setting_view_container( $message );
		}

		public function setting_view_container( $message ) {

			?>
			<div class="wrap gnly-config-section">
				<h2><?php esc_html_e( 'Webkul Analytics Settings', 'gnly' ); ?></h2>
				<hr></br></br>

				<?php
				if ( 'success' === $message['status'] ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p><strong><?php echo ( $message['msg'] ); ?></strong></p>
					</div>
					<?php
				}
				if ( 'error' === $message['status'] ) {
					?>
					<div class="notice notice-error is-dismissible">
						<p><strong><?php echo ( $message['msg'] ); ?></strong></p>
					</div>
					<?php
				}

				?>
				<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" method="post">
					<?php

					if ( ! isset( $this->options['access_code'] ) || false === $this->options['access_code'] || '' === $this->options['access_code'] || empty( $this->options['access_code'] ) ) {
						$this->auth_setting_view();
					} else {
						$this->general_setting_view();
					}
					wp_nonce_field( 'gnly_form', 'gnly_security' );
					?>
				</form>

			</div>
			<script>
			jQuery(document).ready(function(){
				jQuery('#gnly-color-scheme').wpColorPicker();
				jQuery('#gnly-icon-color').wpColorPicker();
				jQuery('form').on("keydown", ":input:not(textarea):not(:submit)", function(event) {
					if (event.key == "Enter") {
						event.preventDefault();
					}
				});
			});
			</script>
			<style>
			.form-table td{
				padding:0px 10px 15px;
			}
			.form-table th{
				padding:0px 10px 15px;
			}
			</style>
			<?php

		}

		private function general_setting_view() {

			$color_scheme   = ( isset( $this->options['color_scheme'] ) ) ? $this->options['color_scheme'] : '#2149f3';
			$items_per_page = ( isset( $this->options['items_per_page'] ) ) ? $this->options['items_per_page'] : '20';
			$permission     = ( isset( $this->options['permission'] ) ) ? maybe_unserialize( $this->options['permission'] ) : array();
			?>

			<table class="form-table">
				<tbody>

					<tr><td colspan="2"><h2>Plugin Authorization</h2></td></tr>
						<td colspan="2" class="">Clear the authorization token and start again.</td>
					</tr>
					<tr>
						<td colspan="2">
							<button type="submit" name="gnly-auth-reset" class="button button-secondary" value="Clear Authorization">Clear Authorization</button>
						</td>
					</tr>

					<tr><td colspan="2"><hr></td></tr>

					<tr><th colspan="2"><h2>General Settings</h2></th></tr>
					<tr>
						<th><label for="gnly-color-scheme"><?php esc_html_e( 'Color Scheme', 'gnly' ); ?></label></th>
						<td style="position:absolute;'"><input id="gnly-color-scheme" type="text" value="<?php echo esc_attr( $color_scheme ); ?>" name="gnly-color-scheme"/></td>
					</tr>
					<tr>
						<th><label for="gnly-items-per-page"><?php esc_html_e( 'Results Per Page', 'gnly' ); ?></label></th>
						<td style="position:absolute;'"><input required id="gnly-items-per-page" type="number" min="5" value="<?php echo esc_attr( $items_per_page ); ?>" name="gnly-items-per-page"/></td>
					</tr>

					<tr><th colspan="2"><h2>Permission</h2></th></tr>

					<tr>
						<th class="roles">
							<label for="access_back"><?php esc_html_e( 'Show stats to:', 'gnly' ); ?>
							</label>
						</th>
						<td>
							<table>
								<tr>
								<?php
								if ( ! isset( $wp_roles ) ) {
									$wp_roles = new WP_Roles();
								}

								$i = 0;

								foreach ( $wp_roles->role_names as $role => $name ) {

									if ( 'subscriber' != $role ) {
										$i++;
										?>
										<td>
										<label>
											<input type="checkbox" name="gnly-permission[]" value="<?php echo $role; ?>" <?php if ( in_array( $role, $permission ) || 'administrator' == $role ) echo 'checked="checked"'; if ( 'administrator' == $role ) echo 'disabled="disabled"';?> /> <?php echo $name; ?>
										</label>
										</td>
										<?php
									}
									if ( 0 == $i % 4 ) {
										?>
										</tr>
										<tr>
										<?php
									}
								}
								?>
							</table>
						</td>
					</tr>
					<tr>
						<th><?php submit_button( 'Save', 'primary', 'gnly-setting-save' ); ?></th>
					</tr>
				</tbody>
			</table>
			<?php
		}

		private function auth_setting_view() {

			$this->gapi_controller = new GNLY_GAPI_Controller();
			$created_auth_url      = $this->gapi_controller->client->createAuthUrl();

			?>
			<table>
				<tbody>
					<tr>
						<td colspan="2" style="padding-bottom: 15px;">Use this link to get your <strong>one-time-use</strong> access code: <a href="<?php echo esc_url( $created_auth_url ); ?>" style="color:red;" target="_blank">Get Access Code</a>.</td>
					</tr>
					<tr>
						<td style="width: 140px;padding-left: 20px;">
							<label for="gnly-access-code" title="Use the red link to get your access code! You need to generate a new one each time you authorize!">Access Code:</label>
						</td>
						<td>
							<input type="text" id="gnly-access-code" name="gnly-access-code" value="" size="61" autocomplete="off" pattern=".\/.{30,}" required="required" title="Use the red link to get your access code! You need to generate a new one each time you authorize!">
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<hr>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="submit" class="button button-secondary" name="gnly-auth-save" value="Save Access Code">
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}
	}
}
