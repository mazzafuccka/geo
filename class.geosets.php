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
	 * date time format from frontend
	 */
	const DATETIME_FORMAT = 'd.m.Y H:i';

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

		//ajax wp_ajax_nopriv
		add_action( 'wp_ajax_new_action', array( 'geoSets', 'new_action' ) );
//		add_action( 'wp_ajax_nopriv_new_action', array( 'geoSets', 'new_action' ) );
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
		global $current_user;
		$html = "
		<!--content-->
        <div id='map'></div>
        <div id='panel'>
            <div class='info'>
            <h2>" . __( 'Edit Object', GeoSets::CONTENT ) . "</h2>
            <!-- block edited information -->
            <form id='object_form' method='post' enctype='multipart/form-data'>
                <label for='name'>" . __( 'Name', GeoSets::CONTENT ) . "</label>
                <input type='text' value='' name='name' required/>

                <input type='checkbox' value='1' name='unlim' checked/>
                <label for='unlim'>" . __( 'Unlimited time', GeoSets::CONTENT ) . "</label>
                <br/>
				<div class='dateTimeWrapper dateTimeWrapper-js'>
					<label for='start_time'>" . __( 'Start time', GeoSets::CONTENT ) . "</label>
	                <input type='text' value='' id='date_timepicker_start' name='start_time'/>

	                <label for='end_time'>" . __( 'End time', GeoSets::CONTENT ) . "</label>
	                <input type='text' value='' id='date_timepicker_end' name='end_time'/>
				</div>

                <label for='type'>" . __( 'Type object', GeoSets::CONTENT ) . "</label>
                <input type='text' value='' name='type' required/>

				<label for='description'>" . __( 'Description', GeoSets::CONTENT ) . "</label>
                <textarea name='description'></textarea>

                <input type='hidden' name='points' value=''/>
                <input type='hidden' name='type_object' value='polygon'/>
                <input type='hidden' name='action' value='new_action'/>
                <input type='hidden' name='user_id' value='" . $current_user->ID . "'/>
                " . wp_nonce_field( 'token_action', 'token' ) . "
            </form>
			</div>
            <div class='actions'>
           		<button id='save-button'>" . __( 'Save', GeoSets::CONTENT ) . "</button>
                <button id='delete-button'>" . __( 'Delete', GeoSets::CONTENT ) . "</button>
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
		wp_register_script( 'datetime', plugins_url( '/js/vendor/jquery.datetimepicker.js', __FILE__ ) );
		wp_enqueue_script(
			'geo',
			plugins_url( '/js/main.js', __FILE__ ),
			array( 'jquery', 'gmaps-draw', 'datetime' )
		);
		// add info
		wp_localize_script( 'geo', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		//css
		self::load_styles();
	}

	/**
	 * method load require css
	 */
	public static function load_styles() {
		wp_enqueue_style( 'mainCss', plugins_url( '/css/index.css', __FILE__ ) );
		wp_enqueue_style( 'datetime', plugins_url( '/css/jquery.datetimepicker.css', __FILE__ ) );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	function geo_load_textdomain() {
		load_plugin_textdomain( 'geoSets', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * action ajax add new geometry points
	 */
	public static function new_action() {
		// check for user login
		if ( is_user_logged_in() ) {
			// check for token
			if ( empty( $_POST ) || ! wp_verify_nonce( $_POST['token'], 'token_action' ) ) {
				wp_die( 'token error' );
			} else {
				// check user_id equals
				global $current_user;
				if ( isset( $_POST['user_id'] ) && $current_user->ID === (int) $_POST['user_id'] ) {
					// work code
					$actionType = isset( $_POST['action'] ) ? trim( $_POST['action'] ) : null;
					$db         = new GeoSets();
					$data       = $db->_prepare_data( $_POST, $current_user );
					//todo add actions for delete und update user points
					switch ( $actionType ) {
						case 'new_action':
							if ( ! empty( $data ) && is_array( $data ) ) {
								$res = $db->insert( $data );
								if ( $res ) {
									$response = array(
										'error'  => array(),
										'action' => 'new',
										'state'  => 'success',
										'data'   => $res
									);
								} else {
									$response = array(
										'error'  => array( 'Point not create' ),
										'action' => 'new',
										'state'  => 'error',
										'data'   => $res
									);
								}
							}
							break;
						case 'edit_action':
							break;
						case 'delete_action':
							break;
					}

					wp_send_json($response);
				}
			}

		}
	}

	/**
	 * prepare post data to save database
	 *
	 * @param array $input
	 * @param object $user
	 *
	 * @return array
	 */
	private function _prepare_data( $input, $user = null ) {

		$result     = array();
		$removeData = array( 'token', 'id', 'unlim', 'action', '_wp_http_referer', 'type_object' );
		foreach ( $input as $name => $value ) {
			if ( ! in_array( $name, $removeData ) ) {
				if ( $name == 'start_time' || $name == 'end_time' && $input['unlim'] == '0' && ! empty( $value ) ) {
					//todo date
					$mysql_date_string = date_create( $value )->format( 'Y-m-d H:i:s' ); //mysql format
					$result[ $name ]   = $mysql_date_string;
				} elseif ( $input['type_object'] == 'polygon' && $name == 'points' ) {
					//poligon convert data
					$result[ $name ] = self::convertPointsTo( $value, $input['type_object'] );

				} else {
					$result[ $name ] = $value;
				}
			}
		}

		// add modify time
		$result['modify_time'] = date( "Y-m-d H:i:s" );
		$result['status']      = 1;
		// add user_id
		$result['user_id'] = isset( $result['user_id'] ) && $user->ID == (int) $result['user_id'] ? (int) $result['user_id'] : $user->ID;

		return $result;
	}

	/**
	 * Convert type object to mysql string format data geometry objects
	 *
	 * @param $string
	 * @param $type
	 *
	 * @return string
	 */
	public static function convertPointsTo( $string, $type ) {
		if ( ! empty( $string ) && ! empty( $type ) ) {
			// add end point if poligon
			$data = explode( ',', $string );
			if ( $type == 'polygon' ) {
				//add first point
				array_push( $data, $data[0] );
				array_push( $data, $data[1] );
			}
			$points       = array_chunk( $data, 2 );
			$points_s     = array_map( function ( $el ) {
				return $el[0] . ' ' . $el[1];
			}, $points );
			$pointsString = implode( ',', $points_s );

			//POLYGON ((30 10, 40 40, 20 40, 10 20, 30 10))
			//LINESTRING (30 10, 10 30, 40 40)
			//POINT (30 10)
			$resultString = mb_strtoupper( $type ) . ' ((' . $pointsString . '))';

			return $resultString;
		} else {
			return '';
		}

	}
}