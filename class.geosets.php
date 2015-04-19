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
	 * devices structure
	 */
	const DB_USERS_DEVICES = 'geosets_devices';

	/**
	 * user-device structure
	 */
	const DB_USERS_USER_DEVICES = 'geosets_user_device';

	/**
	 * user-device structure
	 */
	const DB_USERS_ROUTES = 'geosets_routes';

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
		add_action( 'geo_main_page_view_hook', array( 'GeoSets', 'geo_main_page_view_hook' ) );
		add_action( 'showTable', array( 'GeoSets', 'showTable' ) );
		//call register settings function
		add_action( 'admin_init', array( 'GeoSets', 'register_mysettings' ) );

		//js css
		add_action( 'wp_enqueue_scripts', array( 'GeoSets', 'load_scripts' ) );

		//ajax
		add_action( 'wp_ajax_new_action', array( 'geoSets', 'new_action' ) );
		add_action( 'wp_ajax_delete_action', array( 'geoSets', 'delete_action' ) );
		add_action( 'wp_ajax_edit_action', array( 'geoSets', 'edit_action' ) );

		//cabinet page template
		add_filter( 'page_template', array( 'geoSets', 'geo_cabinet_page_template' ) );

		// register form  additional fields
		add_action( 'register_form', array( 'geoSets', 'geo_show_extra_register_fields' ) );
		add_action( 'register_post', array( 'geoSets', 'geo_check_extra_register_fields' ), 10, 3 );
		add_action( 'user_register', array( 'geoSets', 'geo_register_extra_fields' ), 100 );
		add_filter( 'gettext', array( 'geoSets', 'change_email_reg_text' ), 20, 3 );

		/* redirect users to front page after login */
		function redirect_to_front_page() {
			global $redirect_to;
			if ( ! isset( $_GET['redirect_to'] ) ) {
				$redirect_to = get_option( 'siteurl' );
			}
		}

		add_action( 'login_form', 'redirect_to_front_page' );

		load_plugin_textdomain( GeoSets::CONTENT, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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

		// devices
		$table_name_devices = $wpdb->prefix . GeoSets::DB_USERS_DEVICES;
		$sql_devices = "CREATE TABLE IF NOT EXISTS $table_name_devices (
		id int(11) NOT NULL AUTO_INCREMENT,
		serial_number varchar(64) NOT NULL COMMENT 'serial number or device',
		name tinytext NOT NULL COMMENT 'name devices',
		modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'modifications row time',
		password varchar(64) NOT NULL COMMENT 'password on device',
		device_points geometry NOT NULL COMMENT 'device coordinates',
		description text NOT NULL,
		charge tinyint(3) DEFAULT 0 COMMENT 'charge percent device',
		status tinyint(1) DEFAULT '0' COMMENT 'state active device',
		UNIQUE KEY id (id),
        INDEX (password),
        INDEX (serial_number),
        INDEX (status)
		) $charset_collate;";
		dbDelta( $sql_devices );

		// user-device table
		$table_name_user_devices = $wpdb->prefix . GeoSets::DB_USERS_USER_DEVICES;
		$sql_user_device = "CREATE TABLE IF NOT EXISTS $table_name_user_devices (
		user_id int(11) NOT NULL COMMENT 'user_id fk wp users table',
		device_id int(11) NOT NULL COMMENT 'device_id fk DB_USERS_DEVICES',
        INDEX (user_id),
        INDEX (device_id)
		) $charset_collate;";
		dbDelta( $sql_user_device );

		// routes
		$table_routes = $wpdb->prefix . GeoSets::DB_USERS_ROUTES;
		$sql_routes = "CREATE TABLE IF NOT EXISTS $table_routes (
		id int(11) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL COMMENT 'name devices',
		height int(6) COMMENT 'height of routes',
		device_id int(11) NOT NULL COMMENT 'device_id for route',
		user_id int(11) NOT NULL COMMENT 'user_id route',
		routes_points geometry NOT NULL COMMENT 'routes coordinates',
		create_time datetime DEFAULT NOW() NOT NULL COMMENT 'create route time',
		modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'modifications row time',
		status tinyint(1) DEFAULT '0' COMMENT 'status route, 0 - disabled, 1 - revision, 2- active',
		UNIQUE KEY id (id),
        INDEX (device_id),
        INDEX (user_id),
        INDEX (user_id, device_id, status)
		) $charset_collate;";
		dbDelta( $sql_routes );

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
		add_menu_page(
			__( 'List Points', $content ),
			__( 'List Points', $content ),
			'read',
			__FILE__,
			array(
				'GeoSets',
				'geo_toplevel_page'
			),
			'dashicons-location' );
		add_submenu_page(
			__FILE__,
			__( 'Settings', $content ),
			__( 'Settings', $content ),
			'administrator',
			'geo_settings_page',
			array(
				'GeoSets',
				'geo_settings_page'
			),
			'dashicons-admin-settings'
		);
		add_submenu_page(
			__FILE__,
			__( 'Devices', $content ),
			__( 'Devices', $content ),
			'publish_pages',
			'geo_dispatcher_device_page',
			array(
				'GeoSets',
				'geo_dispatcher_device_page'
			),
			'dashicons-admin-settings'
		);
		add_submenu_page(
			__FILE__,
			__( 'Tracks', $content ),
			__( 'Tracks', $content ),
			'publish_pages',
			'geo_dispatcher_track_page',
			array(
				'GeoSets',
				'geo_dispatcher_track_page'
			),
			'dashicons-admin-settings'
		);
	}

	/**
	 * user points
	 */
	public static function get_user_data() {
		global $current_user;
		$db = new GeoSets();
		// current user data points from DB
		$data = $db->getByUserId( $current_user->ID, true );
		echo '<script> var user_points =' . json_encode( $data ) . '</script>';
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
        <div id='map'>" . home_url() . "</div>
        <div class='cabinet '>
        <a href='" . get_site_url() . "/cabinet/'>" . __( 'Cabinet', GeoSets::CONTENT ) . "</a> |
        <a href='" . wp_logout_url( home_url() ) . "' title='" . __( 'Logout', GeoSets::CONTENT ) . "'>" . __( 'Logout', GeoSets::CONTENT ) . "</a>
        </div>

        <div class='info-block error' style='visibility:hidden;'></div>

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
                " . self::type_list_select() . "
				<label for='description'>" . __( 'Description', GeoSets::CONTENT ) . "</label>
                <textarea name='description'></textarea>

                <input type='hidden' name='points' value=''/>
                <input type='hidden' name='id' value=''/>
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

		self::get_user_data();
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
		wp_localize_script( 'geo', 'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'token_action' ),
				'user_id'  => get_current_user_id(),
				'limit'    => get_option( 'limit' ),
				'lang'     => explode( '_', get_locale() )[0],
				'coord'    => self::get_user_last_point( get_current_user_id() )
			)
		);

		// js data translated messages
		self::translation_js_message();

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
					switch ( $actionType ) {
						case 'new_action':
							if ( ! empty( $data ) && is_array( $data ) ) {
								$res = $db->insert( $data );
								if ( $res ) {
									$response = array(
										'error'  => array(),
										'action' => 'new_action',
										'state'  => 'success',
										'data'   => $res
									);
								} else {
									$response = array(
										'error'  => array( 'Point not create' ),
										'action' => 'new_action',
										'state'  => 'error',
										'data'   => $res
									);
								}
							}
							break;
						case 'edit_action':
							if ( ! empty( $data ) && is_array( $data ) && isset( $data['id'] ) ) {
								$res = $db->update( $data, array( 'id' => $data['id'] ) );
								if ( $res ) {
									$response = array(
										'error'  => array(),
										'action' => 'edit_action',
										'state'  => 'success',
										'data'   => $res
									);
								} else {
									$response = array(
										'error'  => array( 'Point not create' ),
										'action' => 'edit_action',
										'state'  => 'error',
										'data'   => $res
									);
								}
							}
							break;
						case 'delete_action':
							if ( ! empty( $data ) && is_array( $data ) ) {
								$row_id = $data['id'];
								if ( $row_id ) {
									$res = $db->delete( array( 'id' => $row_id ) );
								}

								if ( $res ) {
									$response = array(
										'error'  => array(),
										'action' => 'delete_action',
										'state'  => 'success',
										'data'   => $res
									);
								} else {
									$response = array(
										'error'  => array( 'Point not delete' ),
										'action' => 'delete_action',
										'state'  => 'error',
										'data'   => $res
									);
								}
							}
							break;
					}

					wp_send_json( $response );
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
		$removeData = array( 'token', 'unlim', 'action', '_wp_http_referer', 'type_object' );
		foreach ( $input as $name => $value ) {
			if ( ! in_array( $name, $removeData ) ) {
				if ( ( $name == 'start_time' || $name == 'end_time' ) && ( ! isset( $input['unlim'] ) || $input['unlim'] == '0' ) && ! empty( $value ) ) {
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

	/**
	 * delete action
	 */
	public static function delete_action() {
		self::new_action();
	}

	/**
	 * edit action
	 */
	public static function edit_action() {
		self::new_action();
	}

	/**
	 *  register settings page
	 */
	public static function register_mysettings() {
		//register our settings
		register_setting( 'geo-settings-group', 'limit' );
		register_setting( 'geo-settings-group', 'typeList' );
	}

	/**
	 *
	 */
	public static function geo_settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Geo settings', self::CONTENT ) ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'geo-settings-group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Geo object limit', self::CONTENT ) ?></th>
						<td>
							<input type="text" name="limit" value="<?php echo get_option( 'limit' ); ?>"/>
							<ul>
								<li class="description"><?php _e( 'Limit user sets on one page on one object', self::CONTENT ) ?></li>
							</ul>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Options types', self::CONTENT ) ?></th>
						<td>
							<textarea cols="45" rows="10"
							          name="typeList"><?php echo get_option( 'typeList' ); ?></textarea>
							<ul>
								<li class="description"><?php _e( 'Set options type for select dropdown, user "code value" [space] "description" on one row.<br/>View example:<br/> 15 Type1<br/> 20 Type2', self::CONTENT ) ?></li>
							</ul>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>"/>
				</p>

			</form>
		</div>
	<?php
	}

	/**
	 * get html typeList options
	 * @return string
	 */
	public static function type_list_select() {
		$data        = get_option( 'typeList' );
		$data_ar_row = explode( "\n", $data );
		$selected    = '';
		$html        = '';
		$html .= '<select class="" name="type">';
		foreach ( $data_ar_row as $row ) {
			$row_ar = explode( ' ', $row );
			$html .= '<option ' . $selected . ' value="' . trim( $row_ar[0] ) . '">' . trim( $row_ar[1] ) . '</option>';
		}
		$html .= '';
		$html .= '</select>';

		return $html;
	}

	/**
	 * get Type name
	 *
	 * @param $id
	 *
	 * @return string
	 */
	public static function getTypeListName( $id ) {
		$data        = get_option( 'typeList' );
		$data_ar_row = explode( "\n", $data );
		$name        = '';
		foreach ( $data_ar_row as $row ) {
			$row_ar = explode( ' ', $row );
			if ( trim( $row_ar[0] ) === $id ) {
				$name = $row_ar[1];
			}
		}
		if ( ! empty( $name ) ) {
			return $name;
		} else {
			return $id;
		}
	}

	/**
	 * @param $page_template
	 *
	 * @return string
	 */
	public static function geo_cabinet_page_template( $page_template ) {
		if ( is_page( 'cabinet' ) ) {
			$page_template = dirname( __FILE__ ) . '/cabinet.php';
		}

		return $page_template;
	}

	/**
	 * show table
	 */
	public static function showTable() {
		global $current_user;
		get_currentuserinfo();

		$db = new GeoSets();
		// current user data points from DB
		$data    = $db->getByUserId( $current_user->ID );
		$content = GeoSets::CONTENT;
		$columns = array(
			'id'          => __( '#', $content ),
			'name'        => __( 'Name', $content ),
			'description' => __( 'Description', $content ),
			'start_time'  => __( 'Start time', $content ),
			'end_time'    => __( 'End time', $content ),
			'type'        => __( 'Type', $content ),
			'points'      => __( 'Coordinates', $content ),
			'modify_time' => __( 'Modify time', $content ),
			'status'      => __( 'Status', $content ),
			'actions'     => __( 'Action', $content )
		);

		function colHeadHtml( $columns ) {
			//<th scope="col" id="id" class="manage-column column-id" >#</th>
			$html = '<tr>';
			foreach ( $columns as $key => $row ) {
				$html .= '<th scope="col" id="' . $key . '" class="manage-column column-' . $key . '">' . $row . '</th>';
			}
			$html .= '</tr>';

			return $html;
		}

		function bodyHtml( $columns, $data ) {
			//<td class="id column-id">22</td>
			$html = '';

			if ( empty( $data ) ) {
				$countColumns = count( $columns );

				return '<tr><td class="colspanchange" colspan="' . $countColumns . '">' . __( 'Elements not found.', GeoSets::CONTENT ) . '</td></tr>';
			}

			// mixin
			$data = array_map( function ( $row ) {
				$row['actions']     = '<a class="remove" href="#" data-id ="' . $row['id'] . '" >x</a>';
				$row['type']        = GeoSets::getTypeListName( $row['type'] );
				$row['start_time']  = GeoSets::convertMysqlDateTime( $row['start_time'] );
				$row['end_time']    = GeoSets::convertMysqlDateTime( $row['end_time'] );
				$row['modify_time'] = GeoSets::convertMysqlDateTime( $row['modify_time'] );

				return $row;
			}, $data );

			foreach ( $data as $row ) {
				$html .= '<tr>';
				$td = array();

				foreach ( $row as $key => $element ) {
					foreach ( $columns as $k => $v ) {
						if ( $k === $key ) {
							$td[ $key ] = '<td class="column-' . $key . '">' . $element . '</td>';
						}
					}
				}

				foreach ( $columns as $k => $v ) {
					$html .= $td[ $k ];
				}


				$html .= '</tr>';
			}

			return $html;
		}

		?>
		<!-- table -->
		<div class="wrapper">
			<h2><?php _e( 'Your points', $content ) ?>, <?php echo $current_user->display_name; ?></h2>
			<table class="wp-list-table widefat fixed">
				<thead><?php echo colHeadHtml( $columns ); ?></thead>
				<tfoot><?php echo colHeadHtml( $columns ); ?></tfoot>
				<tbody><?php echo bodyHtml( $columns, $data ); ?></tbody>
			</table>
		</div>
	<?php
	}

	/**
	 * Convert mysql format datetime to view format
	 * 'dd.mm.yyyy hh:mm:ss' to 'yyyy-mm-dd hh:mm:ss'
	 *
	 * @param $date
	 *
	 * @param bool $emptyTime if sets to datetime = '0000-00-00 00:00:00' return ''
	 *
	 * @return mixed
	 */
	public static function convertMysqlDateTime( $date, $emptyTime = true ) {
		if ( $emptyTime && $date === '0000-00-00 00:00:00' ) {
			return '';
		} else {
			return preg_replace( '/^(\d{4})-(\d{2})-(\d{2})/', '$3.$2.$1', $date );
		}
	}

	/**
	 * Remove the text at the bottom of the Custom fields box in WordPress Post/Page Editor.
	 *
	 * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/gettext
	 */
	public static function change_email_reg_text( $translated_text, $untranslated_text, $domain ) {

		$custom_field_text = 'A password will be e-mailed to you.';

		if ( $untranslated_text === $custom_field_text ) {
			return __( 'If you leave password fields empty one will be generated for you. Password must be at least eight characters long.', self::CONTENT );
		}

		return $translated_text;
	}

	/**
	 * Reg extra field in registration
	 *
	 * @param int $user_id update user date fields
	 */
	public static function geo_register_extra_fields( $user_id ) {
		$userdata = array();

		$userdata['ID']          = $user_id;
		$userdata['description'] = $_POST['description'];

		if ( $_POST['password'] !== '' ) {
			$userdata['user_pass'] = $_POST['password'];
		}
		$new_user_id = wp_update_user( $userdata );
	}

	/**
	 * additional register field for registration form
	 * password, repeat password, description
	 */
	public static function geo_show_extra_register_fields() {
		?>
		<p>
			<label for="password"><?php _e( 'Password', self::CONTENT ); ?><br/>
				<input id="password" class="input" type="password" tabindex="30" size="25" value=""
				       name="password"/>
			</label>
		</p>
		<p>
			<label for="repeat_password"><?php _e( 'Repeat New Password', self::CONTENT ); ?><br/>
				<input id="repeat_password" class="input" type="password" tabindex="40" size="25" value=""
				       name="repeat_password"/>
			</label>
		</p>
		<p>
			<label for="description"><?php _e( 'Biographical Info', self::CONTENT ); ?><br/>
				<textarea id="description" style="font-size:14px;" class="input" rows="10"
				          name="description"></textarea>
			</label>
		</p>
	<?php
	}

	/**
	 * Check the form for errors
	 *
	 * @param $login
	 * @param $email
	 * @param $errors
	 */
	public static function geo_check_extra_register_fields( $login, $email, $errors ) {
		if ( $_POST['password'] !== $_POST['repeat_password'] ) {
			$errorMessage = __( '<strong>ERROR</strong>: Passwords must match', self::CONTENT );
			$errors->add( 'passwords_not_matched', $errorMessage );
		}
		if ( strlen( $_POST['password'] ) < 8 ) {
			$errorMessage = __( '<strong>ERROR</strong>: Passwords must be at least eight characters long', self::CONTENT );
			$errors->add( 'password_too_short', $errorMessage );
		}
	}

	/**
	 * Translate messages
	 */
	public static function translation_js_message() {
		// add info
		wp_localize_script( 'geo', 'translate',
			array(
				'm_save'       => __( 'Saved.', self::CONTENT ),
				'm_deleted'    => __( 'Deleted.', self::CONTENT ),
				'm_error'      => __( 'Server Error.', self::CONTENT ),
				'm_fail_error' => __( 'Error save data on server. Try again leter.', self::CONTENT ),
				'm_row_delete' => __( 'Row deleted!', self::CONTENT ),
				'm_confirm'    => __( 'Your have delete?', self::CONTENT ),
				'm_limit'      => __( 'Limit point on shape! Use less then ', self::CONTENT )
			)
		);
	}

	/**
	 * Get last user saved shape
	 *
	 * @param int $id ID element in geo table
	 *
	 * @return string poligon WKT
	 */
	public static function get_user_last_point( $id ) {
		if ( $id ) {
			$db     = new GeoSets();
			$result = $db->getLastShapeByUserId( $id );
			$result = ! empty( $result ) && isset( $result[0] ) ? $result[0] : null;

			return isset( $result['points'] ) ? $result['points'] : '';
		} else {
			return '';
		}

	}

	/**
	 * view for cabinet dispatcher device page
	 */
	public static function geo_dispatcher_device_page() {
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

		<div class="wrapper">
			<h2><?php _e( 'Your managment Devices', $content ) ?></h2>
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
	 * view for cabinet dispatcher tracks page
	 */
	public function geo_dispatcher_track_page()
	{

	}

}