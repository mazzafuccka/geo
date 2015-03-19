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

require_once( 'class.database-custom-data.php' );

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
	 * name content i18n localization domain
	 */
	const CONTENT = 'geoSets';

	/**
	 * points and data user save elements
	 */
	const DB_USERS_POINTS = 'geosets_user_points';

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		parent::__construct( $wpdb->prefix . GeoSets::DB_USERS_POINTS );
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
		add_action( 'admin_menu', array( 'GeoSets', 'geo_add_cabinet_pages' ) );
		add_action( 'plugins_loaded', array( 'GeoSets', 'geo_load_textdomain' ) );
		add_action( 'geo_main_page_view_hook', array( 'GeoSets', 'geo_main_page_view_hook' ) );

		//js css
		add_action( 'wp_enqueue_scripts', array( 'GeoSets', 'load_scripts' ) );
	}

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation() {
		if ( version_compare( $GLOBALS['wp_version'], GEOSETS__MINIMUM_WP_VERSION, '<' ) ) {
			$message = '<strong> Minimum version of wordpress v.' . GEOSETS__MINIMUM_WP_VERSION . '. Please upgrade wordpress for normal functionality plugin <strong>';
			add_action( 'admin_notices', array( 'GeoSets', 'myAdminNotice' ), 10, array( $message, 'error' ) );
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
	private static function myAdminNotice( $message, $type ) {
		$domain = self::CONTENT;
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
		$content = self::CONTENT;
		add_menu_page( __( 'List Points', $content ), __( 'List Points', $content ), 'read', __FILE__, array(
			'GeoSets',
			'geo_toplevel_page'
		), 'dashicons-location' );
	}

	/**
	 * view for cabinet top level page
	 */
	public static function geo_toplevel_page() {
		global $current_user;
		get_currentuserinfo();
		// table user points
		$content = self::CONTENT;

		// List table
		if ( ! class_exists( 'GeoListTables' ) ) {
			require_once( 'class.geolist-tables.php' );
		}
		$db = new GeoSets();
		// current user data points from DB
		$data = $db->getByUserId( $current_user->ID );

		?>
		<!-- Шаблон -->
		<div class="wrapper">
			<h2><?php _e( 'Your points', $content ) ?>, <?php echo $current_user->display_name; ?></h2>
			<?php
			$table = new GeoListTables();
			$table->setData( $data );
			$table->prepare_items();
			$table->display();
			?>
		</div>

	<?php
	}

	/**
	 * Main page view google maps + operations to add, edit points on main page
	 * add js work files to load
	 * add js core files google map to load
	 * add css styles to load
	 * add html wrapper code
	 */
	public static function geo_main_page_view_hook() {
		$html = "
		<!--content-->
        <div id='map'></div>
        <div id='panel'>
            <div class='info'>
            <h2>" .__('Edit Object', GeoSets::CONTENT). "</h2>
            <!-- block edited information -->
            <form id='object_form'>
                <label for='name'>" .__('Name', GeoSets::CONTENT). "</label>
                <input type='text' value='' name='name'/>

                <label for='start_time'>" .__('Start time', GeoSets::CONTENT). "</label>
                <input type='text' value='' datatimepicker='' name='start_time'/>

                <label for='end_time'>" .__('End time', GeoSets::CONTENT). "</label>
                <input type='text' value='' datatimepicker='' name='end_time'/>

				<label for='description'>" .__('Description', GeoSets::CONTENT). "</label>
                <input type='text' value='' name='description'/>

                <label for='description'>" .__('Type object', GeoSets::CONTENT). "</label>
                <input type='text' value='' name='type'/>
            </form>
			</div>
            <div class='actions'>
           		<button id='save-button'>" .__('Save', GeoSets::CONTENT). "</button>
                <button id='delete-button'>" .__('Delete', GeoSets::CONTENT). "</button>
            </div>
        </div>
        ";

		echo $html;
	}

	/**
	 * method load require scripts
	 */
	public static function load_scripts() {
		wp_register_script( 'gmaps-draw', '//maps.googleapis.com/maps/api/js?sensor=false&libraries=drawing' );
		wp_enqueue_script(
			'geo',
			plugins_url( '/js/main.js', __FILE__ ),
			array( 'jquery', 'gmaps-draw' )
		);
		self::load_styles();
	}

	/**
	 * method load require css
	 */
	public static function load_styles() {
		wp_enqueue_style( 'mainCss', plugins_url( '/css/index.css', __FILE__ ) );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	function geo_load_textdomain() {
		load_plugin_textdomain( 'geoSets', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}