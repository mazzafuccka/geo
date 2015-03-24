<?php
/*  Copyright 2015 drdim (email: dr.dim.pro@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Class GeoSetsAdmin
 */
class GeoSetsAdmin {

	/**
	 * @var bool state init plugin
	 */
	private static $initiated = false;

	/**
	 *  initialized hooks
	 * @static
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * init admin
	 * @return bool
	 */
	public static function admin_init() {
		return false;
	}

	/**
	 * Initialized hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
		self::load_scripts();
        load_plugin_textdomain( GeoSets::CONTENT, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * method load require scripts
	 */
	public static function load_scripts() {
		wp_enqueue_script(
			'geo',
			plugins_url( '/js/main-admin.js', __FILE__ ),
			array( 'jquery' )
		);
		// add info
		wp_localize_script( 'geo', 'ajax_object',
			array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'token_action'),
			'user_id' => get_current_user_id()
			)
		);
	}
}