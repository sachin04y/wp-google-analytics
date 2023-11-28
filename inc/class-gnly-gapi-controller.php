<?php



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'GNLY_GAPI_Controller' ) ) {

	final class GNLY_GAPI_Controller {

		public $client;


		public $analytics;


		private $access = array( '#CLIENT_ID#', '#CLIENT_SECRET#' );


		public function __construct() {

			require_once GNLY_PATH . 'tools/vendor/autoload.php';

			$this->client = new Google_Client();
			$this->client->setScopes( array( 'https://www.googleapis.com/auth/analytics.readonly' ) );
			$this->client->setAccessType( 'offline' );
			$this->client->setApplicationName( 'GooNaly 1.0.0' );
			$this->client->setRedirectUri( 'urn:ietf:wg:oauth:2.0:oob' );

			$this->client->setClientId( $this->access[0] );
			$this->client->setClientSecret( $this->access[1] );

			$this->config  = new GNLY_config();
			$this->options = $this->config->options;
			$token_new     = isset( $this->options['access_code'] ) && '' !== $this->options['access_code'] ? (array) ( $this->options['access_code'] ) : '';

			if ( $token_new && '' !== $token_new ) {

				$this->analytics = new Google_Service_Analytics( $this->client );

				try {
					$this->client->setAccessToken( $token_new );

					if ( $this->client->isAccessTokenExpired() ) {

						$refreshtoken = $this->client->getRefreshToken();
						$this->client->refreshToken( $refreshtoken );
					}

					$refreshed_token              = $this->client->getAccessToken();
					$this->options['access_code'] = $refreshed_token;
					$this->config->set_options( $this->options );

				} catch ( Google_IO_Exception $e ) {
					var_dump( $e );
				}

			}

		}


		public function getProfileId() {

			return $this->profileid;
		}


		public function profiles() {

			// Get the list of accounts for the authorized user.
			$profiles_found = array();
			$accounts       = $this->analytics->management_accounts->listManagementAccounts();

			if ( count( $accounts->getItems() ) > 0 ) {

				$items = $accounts->getItems();

				foreach ( $items as $account ) {

					$first_account_id = $account->getId();

					// Get the list of properties for the authorized user.
					$properties = $this->analytics->management_webproperties->listManagementWebproperties( $first_account_id );

					if ( count( $properties->getItems() ) > 0 ) {

						$items = $properties->getItems();

						foreach ( $items as $item ) {

							// Get the list of views (profiles) for the authorized user.
							$profiles = $this->analytics->management_profiles->listManagementProfiles( $first_account_id, $item->getId() );

							if ( count( $profiles->getItems() ) > 0 ) {

								$items = $profiles->getItems();
		
								// Return the first view (profile) ID.
								$profiles_found[] = array(
									'websiteUrl' => $items[0]->getWebsiteUrl(),
									'profileId'  => $items[0]->getId(),
								);

							} else {
								throw new Exception( 'No views (profiles) found for this user.' );
							}
						}
					} else {
						throw new Exception( 'No properties found for this user.' );
					}
				}

				return $profiles_found;

			} else {
				throw new Exception( 'No accounts found for this user.' );
			}
		}


		public function getFirstProfileId() {

			// Get the user's first view (profile) ID.

			// Get the list of accounts for the authorized user.
			$accounts = $this->analytics->management_accounts->listManagementAccounts();

			if ( count( $accounts->getItems() ) > 0 ) {

				$items          = $accounts->getItems();
				$firstAccountId = $items[0]->getId();

				// Get the list of properties for the authorized user.
				$properties = $this->analytics->management_webproperties->listManagementWebproperties( $firstAccountId );

				if ( count( $properties->getItems() ) > 0 ) {

					$items           = $properties->getItems();
					$firstPropertyId = $items[0]->getId();

					// Get the list of views (profiles) for the authorized user.
					$profiles = $this->analytics->management_profiles->listManagementProfiles( $firstAccountId, $firstPropertyId );

					if ( count( $profiles->getItems() ) > 0 ) {

						$items = $profiles->getItems();

						// Return the first view (profile) ID.
						return $items[0]->getId();

					} else {
						throw new Exception( 'No views (profiles) found for this user.' );
					}
				} else {
					throw new Exception( 'No properties found for this user.' );
				}
			} else {
				throw new Exception( 'No accounts found for this user.' );
			}
		}


		public function reset_token() {

			$this->options['access_code'] = '';

			try {

				$this->client->revokeToken();
				$this->config->set_options( $this->options );

			} catch ( Exception $e ) {
				var_dump( $e );
			}
		}


	}
}