<?php
defined( 'ABSPATH' ) or die( 'This plugin must be run within the scope of WordPress.' );

if ( ! class_exists( 'EDU_Google' ) ) {
	class EDU_Google extends EDU_Integration {
		public function __construct() {
			$this->id = 'eduadmin-google';
			$this->displayName = __( 'Google Analytics / Tag Manager', 'eduadmin-google' );
			$this->description = __( 'Plugin to enable more advanced Google Analytics / Tag Manager integration', 'eduadmin-google' );
			$this->type = 'plugin';

			$this->init_form_fields();
			$this->init_settings();
		}

		public function init_form_fields() {
			$this->setting_fields = [
				'enabled' => [
					'title' => __( 'Enabled', 'eduadmin-google' ),
					'type' => 'checkbox',
					'description' => __( 'Enable Google Analytics / Tag Manager integration', 'eduadmin-google' ),
					'default' => 'no',
				]
			];
		}
	}
}
