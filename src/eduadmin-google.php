<?php
defined( 'ABSPATH' ) or die( 'This plugin must be run within the scope of WordPress.' );

/*
 * Plugin Name:	EduAdmin Booking - Google Analytics / Tag Manager plugin
 * Plugin URI:	https://www.eduadmin.se
 * Description:	This plugin adds support for Google Analytics / Tag Manager to your EduAdmin plugin (WordPress only, not the course portal).
 * Tags: booking, participants, courses, events, eduadmin, lega online, google, analytics, tag manager
 * Version:	$PLUGINVERSION$
 * GitHub Plugin URI: multinetinteractive/eduadmin-google
 * GitHub Plugin URI: https://github.com/multinetinteractive/eduadmin-google
 * Requires at least: $PLUGINATLEAST$
 * Tested up to: $PLUGINTESTEDTO$
 * Author:	Chris Gårdenberg, MultiNet Interactive AB
 * Author URI:	https://www.multinet.com
 * License:	GPL3
 * Text Domain:	eduadmin-google
 * Domain Path: /languages/
 */
/*
    EduAdmin Booking plugin
    Copyright (C) 2015-2023 Chris Gårdenberg, MultiNet Interactive AB

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

add_action( 'admin_init', 'checkForEduAdminPlugin' );
function checkForEduAdminPlugin() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ( ! is_plugin_active( 'eduadmin-booking/eduadmin.php' ) && ! is_plugin_active( 'eduadmin/eduadmin.php' ) ) ) {
		add_action( 'admin_notices', function () {
			?>
			<div class="error">
			<p><?php _e( 'This plugin requires the EduAdmin-WordPress-plugin to be installed and activated.', 'eduadmin-google' ); ?></p>
			</div><?php
		} );
		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

if ( ! class_exists( 'EDU_Google_Loader' ) ):

	final class EDU_Google_Loader {
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		public function init() {
			if ( class_exists( 'EDU_Integration' ) ) {
				require_once( __DIR__ . '/class/class-edu-google.php' );

				add_filter( 'edu_integrations', array( $this, 'add_integration' ) );
			}
		}

		public function add_integration( $integrations ) {
			$integrations[] = 'EDU_Google';

			return $integrations;
		}
	}

	$edu_sveawebpay_loader = new EDU_Google_Loader( __FILE__ );
endif;