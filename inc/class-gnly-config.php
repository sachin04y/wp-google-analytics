<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit();

if ( ! class_exists( 'GNLY_Config' ) ) {

	final class GNLY_Config {

		private $defaults = array(

			'access_code'  => '',
			'color_scheme' => '#2149f3',
			'permission'   => array(),
			'profiles'     => array(),
		);

		public $options;

		public function __construct() {

			$this->options = $this->get_options();
		}

		public function set_options( $options ) {

			$old_options = $this->get_options();
			$new_options = array_merge( $old_options, $options );
			update_option( 'gnly_options', maybe_serialize( $new_options ) );
		}

		public function get_options() {

			$current_options = get_option( 'gnly_options' );

			return ( '' === $current_options || false === $current_options ) ? $this->defaults : maybe_unserialize( get_option( 'gnly_options' ) );
		}
	}

}
