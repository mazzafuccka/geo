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

require_once('class.database-custom-data.php');

/**
 * Class GeoSetsAdmin
 */
class GeoSets extends DataBaseCustomData {
	/**
	 * @var bool state init plugin
	 */
	private static $initiated = false;

	/**
	 * @var string db custom version
	 */
	private static $jal_db_version = '1.0';

	/**
	 * @var string name content i18n localization domain
	 */
	private static $content = 'geoSets';

	/**
	 * points and data user save elements
	 */
	const DB_USERS_POINTS = 'geosets_user_points';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(self::DB_USERS_POINTS);
	}

	/**
	 * Initialized plugin
	 * @static
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
		// Hook for adding admin menus
		add_action('admin_menu', array('GeoSets','geo_add_cabinet_pages'));
	}

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation() {
		if ( version_compare( $GLOBALS['wp_version'], GEOSETS__MINIMUM_WP_VERSION, '<' ) ) {
			$message = '<strong> Minimum version of wordpress v.' . GEOSETS__MINIMUM_WP_VERSION . '. Please upgrade wordpress for normal functionality plugin <strong>';
			add_action( 'admin_notices', array( 'GeoSets', 'myAdminNotice') , 10, array( $message, 'error' ) );
		} else {
			// install db tables
			GeoSets::jal_install();
		}
	}

	/**
	 * Removes all connection options
	 * @static
	 */
	public static function plugin_deactivation() {
		//tidy up
	}

	/**
	 * Initialized hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
	}

	/**
	 * Install databases structure
	 * @internal dbDelta
	 * @global $wpdb
	 */
	private static function jal_install() {
		global $wpdb;
		$jal_db_version = GeoSets::$jal_db_version;

		$table_name = $wpdb->prefix . GeoSets::DB_USERS_POINTS;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		user_id int(11) NOT NULL COMMENT 'wp user id, fk wp_users',
		modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'modifications row time',
		start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'start object activation',
		end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'stop object activation',
		name tinytext NOT NULL COMMENT 'name object or poligon',
		type varchar(64) NOT NULL COMMENT 'type of poligon',
		points geometry NOT NULL COMMENT 'geo point of objects, poligons',
		description text NOT NULL,
		status tinyint(1) DEFAULT '0' COMMENT 'state, view or not views on map, active state',
		UNIQUE KEY id (id),
        INDEX (start_time, end_time),
        INDEX (user_id)
	) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'jal_db_version', $jal_db_version );
	}

	/**
	 * admin error message
	 *
	 * @param string $message
	 * @param string $type type message 'error', 'update'
	 */
	private static function myAdminNotice( $message, $type) {
		$domain = self::$content;
		?>
		<div class="<?= $type; ?>">
			<p><?php _e( $message, $domain ); ?></p>
		</div>
	<?php
	}


	/**
	 * action function for menu hook
	 */
	public static function geo_add_cabinet_pages() {
		$content  = 'geoSets';
		add_menu_page(__('List Points', $content), __('List Points', $content), 'read', __FILE__, array('GeoSets', 'geo_toplevel_page'));
	}

	/**
	 * view for cabinet top level page
	 */
	public static function geo_toplevel_page() {
		global $current_user;
		get_currentuserinfo();
	   // todo page template
	   echo __("Hello, ", GeoSets::$content). $current_user->display_name;
		// table user points
	}

}