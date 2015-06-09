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
	public function __construct( $table = null ) {
		global $wpdb;
		if ( $table ) {
			$tableName = $wpdb->prefix . $table;
		} else {
			$tableName = $wpdb->prefix . GeoSets::DB_USERS_POINTS;
		}
		parent::__construct( $tableName );
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

		add_action( 'showTable_devices', array( 'GeoSets', 'showTable_devices' ) );
		add_action( 'showTable_routes', array( 'GeoSets', 'showTable_routes' ) );

		//call register settings function
		add_action( 'admin_init', array( 'GeoSets', 'register_mysettings' ) );

		//js css
		add_action( 'wp_enqueue_scripts', array( 'GeoSets', 'load_scripts' ) );

		//ajax
		add_action( 'wp_ajax_new_action', array( 'geoSets', 'new_action' ) );
		add_action( 'wp_ajax_delete_action', array( 'geoSets', 'delete_action' ) );
		add_action( 'wp_ajax_edit_action', array( 'geoSets', 'edit_action' ) );

		add_action( 'wp_ajax_save_path', array( 'geoSets', 'save_path' ) );

		add_action( 'wp_ajax_accept_area', array( 'geoSets', 'accept_area' ) );

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
		status tinyint(1) DEFAULT '2' COMMENT 'status objects, 0 - disabled, 1 - active, 2 - moderate',
		UNIQUE KEY id (id),
        INDEX (start_time, end_time),
        INDEX (user_id)
	) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// devices
		$table_name_devices = $wpdb->prefix . GeoSets::DB_USERS_DEVICES;
		$sql_devices        = "CREATE TABLE IF NOT EXISTS $table_name_devices (
			id int(12) not null auto_increment,
			name char(80),
			serial_number char(80),
 			modify_time datetime,
 			password char(40),
			lat decimal(8, 5),
			lng decimal(8, 5),
			alt decimal(6,1),
			description text,
			status int( 8 ),
			charge int( 8 ),
                        PRIMARY KEY (id)
			) $charset_collate;";
		dbDelta( $sql_devices );

		// user-device table
		$table_name_user_devices = $wpdb->prefix . GeoSets::DB_USERS_USER_DEVICES;
		$sql_user_device         = "CREATE TABLE IF NOT EXISTS $table_name_user_devices (
			id int(12) not null auto_increment,
			user_id int(14),
			device_id int(14),
			PRIMIARY KEY (id)
			) $charset_collate;";
		dbDelta( $sql_user_device );

		// routes
		$table_routes = $wpdb->prefix . GeoSets::DB_USERS_ROUTES;
		$sql_routes   = "CREATE TABLE IF NOT EXISTS $table_routes (
		id int(11) NOT NULL AUTO_INCREMENT,
		name tinytext NOT NULL COMMENT 'name devices',
		height int(6) COMMENT 'height of routes',
		device_id int(11) NOT NULL COMMENT 'device_id for route',
		user_id int(11) NOT NULL COMMENT 'user_id route',
		pass char(30),
		routes_points geometry NOT NULL COMMENT 'routes coordinates',
		create_time datetime DEFAULT NOW() NOT NULL COMMENT 'create route time',
		modify_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'modifications row time',
		typ int(4) comment '1 - wait and return, 2- drop cargo and return, 3- hold position',
		status tinyint(2) DEFAULT '2' COMMENT 'status route, 0 - disabled, 1 - revision, 2 - active',
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
		$data = $db->_prepare_select( $db->getByUserIdPoints( $current_user->ID, true ) );
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
		$data = $db->getByUserIdPoints( $current_user->ID );

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
		global $wpdb;

		$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
		$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    		mysql_query("set names utf8");
	
		$poly_arr="";

		$res = mysql_query("select AsText(points) from ".$wpdb->prefix.GeoSets::DB_USERS_POINTS." where status=1");

		for ($i=0; $i<mysql_num_rows($res); $i++)
		{
			$poly = mysql_result($res, $i, 0);

			$sk = str_replace('POLYGON((', '', $poly);
			$coords = explode(',', str_replace(')', '', $sk));
			
			$poly_obj = "[";

			foreach ($coords as $coord)
			{
				$arr = explode(' ',$coord);
				$poly_obj .= "   new google.maps.LatLng(".$arr[0].", ".$arr[1]."),\n";
			}
			
			$poly_obj .= "],\n";
			
			$poly_arr .= $poly_obj;			
		} 

		// get devices list!

		$res = mysql_query("select devs.id,name,lat,lng,alt,charge,status from ".
			$wpdb->prefix. GeoSets::DB_USERS_USER_DEVICES ." udev left join ". $wpdb->prefix. GeoSets::DB_USERS_DEVICES.
				" devs on devs.id=udev.device_id where udev.user_id=".$current_user->ID) or die(mysql_error());

		$dev_list = "<h3>Ваши устройства:</h3><table><tr><th>Device</th><th>Altitude</th><th>Battery</th></tr>";
		$arr = "var devices_list = [";
		for ($i=0; $i<mysql_num_rows($res); $i++)
		{
			$did = mysql_result($res, $i, 0);
			$d_nam = mysql_result($res, $i, 1);
			$d_lat = mysql_result($res, $i, 2);	
			$d_lng = mysql_result($res, $i, 3);
			$alt = mysql_result($res, $i, 4);
			$charge = mysql_result($res, $i, 5);
			
			$dev_list .= "<tr><td>$d_nam</td><td>$alt m</td><td>$charge %</td></tr>\n"; 

			$arr .= "{ 'name': '$d_nam', 'lat':'$d_lat', 'lng':'$d_lng', 'alt':'$alt', 'charge':'$charge' },\n";
	
		}
		$arr .= "];";
		$dev_list .= "</table>";

		mysql_close($lnk);

		$html = "\n\n
		<script type='text/javascript' src='wp-content/plugins/geo-master/js/ajax_path.js' ></script>
		<script type='text/javascript' > var allPolygons = [ $poly_arr ]; $arr </script>

		<!--content-->
        <div id='map'>" . home_url() . "</div>
        <div class='cabinet '>
        <a href='" . get_site_url() . "/cabinet/'>" . __( 'Cabinet', GeoSets::CONTENT ) . "</a> |
        <a href='" . wp_logout_url( home_url() ) . "' title='" . __( 'Logout', GeoSets::CONTENT ) . "'>" . __( 'Logout', GeoSets::CONTENT ) . "</a>
        </div>

        <div class='info-block error' style='visibility:hidden;'></div>

	<div id='device_list' style='width:450px;display: inline-block; float: left;'>$dev_list</div>

        <div id='panel'>
            <div class='info'>
            <h2>" . __( 'Edit Object', GeoSets::CONTENT ) . "</h2>
            <!-- block edited information -->
            <form id='object_form' method='post' enctype='multipart/form-data'>
                <div id='area-stat'  class='status' style='display:none;'>
	                <label for='name'>" . __( 'Status', GeoSets::CONTENT ) . ":</label>
	                <span id='xxxa' data-name='status'></span><br/>
                </div>
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
                <input type='hidden' name='id' id='area_id' value=''/>
                <input type='hidden' name='type_object' value='polygon'/>
                <input type='hidden' name='action' value='new_action'/>
                <input type='hidden' name='user_id' value='" . $current_user->ID . "'/>
                " . wp_nonce_field( 'token_action', 'token' ) . "
            </form>
			</div>
            <div class='actions'>
           		<button id='save-button'>" . __( 'Save', GeoSets::CONTENT ) . "</button>
                <button id='delete-button'>" . __( 'Delete', GeoSets::CONTENT ) . "</button><br><br>
		<button id='accept-btn' >Accept this area</button>
            </div>
        </div>

  <div id='panel-path'>
            <div class='info'>
            <h2>Path is build</h2>
            <!-- block edited information -->
            <form id='path_form' method='post' enctype='multipart/form-data'>
                <label for='name'>Name</label>
                <input type='text' value='' id='pname' name='name' required/>

		<label for='pathtype'>Type</label><select id='ptyp' size=1 name=pathtype tyle='font-size: 12px;'>
		<option value=1 style='font-size: 12px;'>Wait for customer and return back</option>
		<option value=2 style='font-size: 12px;'>Drop cargo and return back</option>
		<option value=3 style='font-size: 12px;'>Hold this position</option>
		</select> 

                <input id='polypath' type='hidden' name='points' value=''/>
		<input id='path-state' type='hidden' name=state value=1>
                <input type='hidden' name='id' value=''/>
                <input type='hidden' name='type_object' value='polyline'/>
                <input type='hidden' name='action' value='save_path'/>
                <input type='hidden' name='user_id' value='" . $current_user->ID . "'/>
                " . wp_nonce_field( 'token_action', 'token' ) . "
            </form>
			</div>
            <div class='actions'>
           	<button id='path-save-button'>Save</button>
           	<button id='path-save-button2'>Save and Run!</button><br><br>
                <button id='path-delete-button'>Delete</button>
                
            </div>
        </div>

	<script language=JavaScript>
		$(function() {
    			$('#accept-btn').hide();
			$('#accept-btn').click(function() {
  				var aid = $('#area_id').val();
				jQuery.ajax({
				type: 'POST',   // Adding Post method
				url: 'wp-admin/admin-ajax.php', // Including ajax file
				data: {'action': 'accept_area', 'areaid': aid}, // Sending data
				success: function(data){ // Show returned data using the function.
					var infoBlock = $('.info-block');
					infoBlock.html('Area accepted!');
					if (!infoBlock.hasClass('success')) {
        					infoBlock.addClass('success').removeClass('error');
      					}
					
      					infoBlock.css('visibility', 'visible');
					setTimeout(function() { $('.info-block').css('visibility', 'hidden'); $('#panel').slideUp('slow'); }, 2000);
					}
				});
			});	
		});
	</script>
        ";

		self::get_user_data();
		echo $html;
	}

	/**
	 * method load require scripts
	 */
	public static function load_scripts() {
		global $current_user;

		wp_register_script( 'gmaps-draw', '//maps.googleapis.com/maps/api/js?sensor=false&libraries=drawing' );
		wp_register_script( 'datetime', plugins_url( '/js/vendor/jquery.datetimepicker.js', __FILE__ ) );

		if ( is_user_logged_in() ) 
		{
			$userRole = ($current_user->caps);
			$role = key($userRole);
			unset($userRole);
			
			if (strstr($role, 'administrator') || strstr($role, 'editor'))
			{	
				wp_enqueue_script('geo',plugins_url( '/js/main.js', __FILE__ ),
						array( 'jquery', 'gmaps-draw', 'datetime' ) );
			}

			if (strstr($role, 'subscriber'))
			{
				wp_enqueue_script('geo',plugins_url( '/js/main_mini.js', __FILE__ ), 
						array( 'jquery', 'gmaps-draw', 'datetime' )  );
			
			}
		}

		
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
										'error'  => array( 'Point not create (db error)' ),
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
										'error'  => array( 'Point not create (on edit, db error)' ),
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


	public static function accept_area() 
	{
		global $current_user, $wpdb;
		get_currentuserinfo();

		if ( is_user_logged_in() ) {
		
			$area = 1*$_POST['areaid'];
			
			// check here if user got permission to do that....
			$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
			$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    			mysql_query("update ".$wpdb->prefix.GeoSets::DB_USERS_POINTS." set status=1 where id=$area ");
			$response = array(
			'error'  => array(),
			'action' => 'accept_area',
			'state'  => 'success',
			'data'   => ''
			);
			mysql_close($lnk);

		} else {

			$response = array(
			'error'  => array('You are not logged id!'),
			'action' => 'accept_area',
			'state'  => 'error',
			'data'   => ''
			);

		}

		
		wp_send_json( $response );

	}


	public static function save_path() 
	{
		global $current_user;
		get_currentuserinfo();

		if ( is_user_logged_in() ) {
		

		$height = 1; // later make normal altitude
		$dev_id = 0; // for test, later normal
		

		$state = 1*$_POST['state'];
		$nam = mysql_escape_string($_POST['name']);
		$line = mysql_escape_string($_POST['path']);
		$typ = mysql_escape_string($_POST['typ']);		

		$pw = GeoSets::generatePassword(6);

		global $wpdb; 

		$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
		$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    		mysql_query("set names utf8");

		if (mysql_query("insert into ".$wpdb->prefix.GeoSets::DB_USERS_ROUTES." values (NULL, '$nam', $height, $dev_id, ".$current_user->ID.
			", '$pw', GeomFromText('$line'), NOW(), NOW(), $typ, $state)"))
		{

			$response = array(
			'error'  => array(),
			'action' => 'save_path',
			'state'  => 'success',
			'data'   => ''
			);

		} else {
			$response = array(
			'error'  => array('failed save to DB'),
			'action' => 'save_path',
			'state'  => 'error',
			'data'   => ''
			);

		}

		wp_send_json( $response );
		mysql_close($lnk);

		} else {


			$response = array(
			'error'  => array('You are not logged id!'),
			'action' => 'save_path',
			'state'  => 'error',
			'data'   => ''
			);

			wp_send_json( $response );
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
				} elseif ( $name == 'status' ) {
					$result[ $name ] = $value;
				} else {
					$result[ $name ] = $value;
				}
			}
		}

		// add modify time
		$result['modify_time'] = date( "Y-m-d H:i:s" );
		// set default to 2 - moderation
		if ( empty( $result['status'] ) ) {
			$result['status'] = 2;
		}

		// add user_id
		$result['user_id'] = isset( $result['user_id'] ) && $user->ID == (int) $result['user_id'] ? (int) $result['user_id'] : $user->ID;

		return $result;
	}

	/**
	 * Prepare select data for objects
	 *
	 * @param array $data
	 *
	 * @return array $data modify array data
	 */
	private function _prepare_select( $data ) {
		if ( ! empty( $data ) ) {
			foreach ( $data as &$row ) {
				if ( is_array( $row ) ) {
					foreach ( $row as $key => &$element ) {
						switch ( $key ) {
							case 'status' :
								$element = GeoSets::getStatusName( $element );
								break;
						}
					}
				}

			}
		}

		return $data;
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

		$fl = file('../wp-content/plugins/geo-master/js/ajax_path.js') or die('cant open '.getcwd());
		$ln = explode("\"", $fl[0]);
		
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

					<tr valign=top>
						<th scope='row'>Pathfinding AJAX script:</th>
						<td><input type=text name='path_ajax' style='width:400px;' value='<?php echo $ln[1]; ?>' ></td>
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
	 * Get Status Text Name
	 *
	 * @param $status
	 *
	 * @return string|void
	 */
	public static function getStatusName( $status ) {
		$content = GeoSets::CONTENT;

		switch ( $status ) {
			case '0' :
				return __( 'Disabled', $content );
				break;
			case '1' :
				return __( 'Active', $content );
				break;
			case '2' :
				return __( 'Treatment', $content );
				break;
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
	 *  выводим таблицу полигонов пользователя
	 */
	public static function showTable() {
		global $current_user;
		get_currentuserinfo();

		$db = new GeoSets();
		// current user data points from DB
		$data    = $db->getByUserIdPoints( $current_user->ID );
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
				$row['status']      = GeoSets::getStatusName( $row['status'] );

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


	// создаем таблицу со списком устройств, если нету
	private static function init_devices_table()
	{
		global $wpdb;
		print "[+] init_devices_table() called<br>\n";
		
		$wpdb->query(
			$wpdb->prepare("create table ".$wpdb->prefix.GeoSets::DB_USERS_DEVICES.
			" (id int(12) not null auto_increment,
			name char(80),
			serial_number char(80),
 			modify_time datetime,
 			password char(40),
			lat decimal(8, 5),
			lng decimal(8, 5),
			alt decimal(6,1),
			description text,
			status int( 8 ),
			charge int( 8 ),
                        PRIMARY KEY (id)
			);")
		);


	}


	// создаем таблицу со списком устройств, если нету
	private static function init_user_devices_table()
	{
		global $wpdb; 

		$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
		$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    		mysql_query("set names utf8");
		print "[+] init_user_devices_table() called<br>\n";
		
		mysql_query("create table ".$wpdb->prefix.GeoSets::DB_USERS_USER_DEVICES." (
			id int(12) not null auto_increment,
			user_id int(14),
			device_id int(14),
			PRIMARY KEY (id)
			);") or die(mysql_error());

		mysql_close($lnk);


	}


	public static function showTable_routes() {
		global $current_user;
		global $wpdb;
		get_currentuserinfo();

		$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
		$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    		mysql_query("set names utf8");
	
		if (isset($_POST['route_id']) && isset($_POST['act']))
		{
			$rid = 1*$_POST['route_id'];
			$act = $_POST['act'];

			if ($act == 'delete')
				mysql_query("delete from ".$wpdb->prefix.GeoSets::DB_USERS_ROUTES." where id=$rid");

			if ($act == 'start')
				mysql_query("update ".$wpdb->prefix.GeoSets::DB_USERS_ROUTES." set status=2 where id=$rid");

			if ($act == 'stop')
				mysql_query("update ".$wpdb->prefix.GeoSets::DB_USERS_ROUTES." set status=0 where id=$rid");
			
		}

		echo "<script type='text/javascript' >
		function checkform() 
		
			var aresure = confirm('Are you sure?');
			return aresure;
		}
		</script><h2>Your routes</h2>";
		print "<table class=\"wp-list-table widefat fixed\">
		<thead><tr><th scope=\"col\" id=\"id\" class=\"manage-column column-id\">#</th>
		<th scope=\"col\" id=\"name\" class=\"manage-column column-name\">Name</th>
		<th scope=\"col\" id=\"description\" class=\"manage-column column-description\">Height</th>
		<th scope=\"col\" id=\"points\" class=\"manage-column column-end_time\">Device_id</th>
		<th scope=\"col\" id=\"charge\" class=\"manage-column column-type\">Pass</th>
		<th scope=\"col\" id=\"serial\" class=\"manage-column column-points\">Type</th>
		<th scope=\"col\" id=\"modify_time\" class=\"manage-column column-modify_time\">Modify time</th>
		<th scope=\"col\" id=\"status\" class=\"manage-column column-status\">Status</th><th></th></tr></thead>\n\n";


		print "<tfoot><tr><th scope=\"col\" id=\"id\" class=\"manage-column column-id\">#</th>
		<th scope=\"col\" id=\"name\" class=\"manage-column column-name\">Name</th>
		<th scope=\"col\" id=\"description\" class=\"manage-column column-description\">Height</th>
		<th scope=\"col\" id=\"points\" class=\"manage-column column-end_time\">Device_id</th>
		<th scope=\"col\" id=\"charge\" class=\"manage-column column-type\">Pass</th>
		<th scope=\"col\" id=\"serial\" class=\"manage-column column-points\">Type</th>
		<th scope=\"col\" id=\"modify_time\" class=\"manage-column column-modify_time\">Modify time</th>
		<th scope=\"col\" id=\"status\" class=\"manage-column column-status\">Status</th><th></th></tr></tfoot><tbody>\n\n";


		$res = mysql_query("select id, name, height, device_id dev, pass, typ, modify_time, status,(select name from ".
			$wpdb->prefix.GeoSets::DB_USERS_DEVICES." where id=dev ) from ".$wpdb->prefix.GeoSets::DB_USERS_ROUTES.
			" where user_id=".$current_user->ID." order by status desc") or die(mysql_error());

		for ($i=0; $i<mysql_num_rows($res); $i++)
		{
			$r_id = 1*mysql_result($res, $i, 0); 
			$typ = 1*mysql_result($res, $i, 5);
			$stat = 1*mysql_result($res, $i, 7);

			$type = "Wait client and return";
			if ($typ == 2)
				$type = "Drop cargo and return";
			if ($typ == 3)
				$type = "Hold this position";

			$state = "Stopped";
			if ($stat == 0)
				$state = "Inactive";
			if ($stat == 2)
				$state = "Running";

			if ($stat == 2)
			{
				$controls = "<form method=POST onsubmit='return checkform();'><input type=hidden name=route_id value=$r_id ><input type=hidden name='act' value='stop'>\n";
				$controls .= "<input type=submit value='Stop'></form>";
			} else {
				$controls = "<form method=POST onsubmit='return checkform();'><input type=hidden name=route_id value=$r_id ><input type=hidden name='act' value='start'>\n";
				$controls .= "<input type=submit value='Start!'></form>";
				$controls .= "<form method=POST onsubmit='return checkform();'><input type=hidden name=route_id value=$r_id ><input type=hidden name='act' value='delete'>\n";
				$controls .= "<input type=submit value='Delete'></form>";
			}

			print "<tr><td>".mysql_result($res, $i, 0)."</td><td>".mysql_result($res, $i, 1)."</td><td>".mysql_result($res, $i, 2)."</td><td>";
			print mysql_result($res, $i, 8)." (dev id ".mysql_result($res, $i, 3).")</td><td>".mysql_result($res, $i, 4);
			print "</td><td>$type</td><td>".mysql_result($res, $i, 6)."</td><td>$state</td><td>$controls</td></tr>\n\n";
		}
		print "</tbody></table>\n\n";

		mysql_close($lnk);
	}

	// выводим в кабинете список устройств, привязанных к юзеру
	public static function showTable_devices() {
		global $current_user;
		global $wpdb;
		get_currentuserinfo();

		$lnk = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("cant connect to db");
		$db = mysql_select_db(DB_NAME, $lnk) or die("cant select db");
    		mysql_query("set names utf8");


		if (isset($_POST['serial']) && isset($_POST['pass']))	
		{
			$serial = mysql_escape_string($_POST['serial']);
			$pass = mysql_escape_string($_POST['pass']);
			
			$res = mysql_query("select id from ".$wpdb->prefix.GeoSets::DB_USERS_DEVICES." where serial_number='$serial' and password='$pass' ") 
				or die(mysql_error());
			$a = 1*mysql_result($res, 0, 0);
			if ($a > 0)
			{
				mysql_query("insert into ".$wpdb->prefix.GeoSets::DB_USERS_USER_DEVICES." values (NULL, ".$current_user->ID.", $a)");
				print "[+] ok!  ";	
			} else print "<script language=Javascript>alert('Incorrect password for device!');</script>";
		}

		echo "<h2>Your devices</h2>";
		print "<table class=\"wp-list-table widefat fixed\">
		<thead><tr><th scope=\"col\" id=\"id\" class=\"manage-column column-id\">#</th>
		<th scope=\"col\" id=\"name\" class=\"manage-column column-name\">Name</th>
		<th scope=\"col\" id=\"description\" class=\"manage-column column-description\">Description</th>
		<th scope=\"col\" id=\"points\" class=\"manage-column column-end_time\">Points</th>
		<th scope=\"col\" id=\"charge\" class=\"manage-column column-type\">Charge</th>
		<th scope=\"col\" id=\"serial\" class=\"manage-column column-points\">Serial number</th>
		<th scope=\"col\" id=\"modify_time\" class=\"manage-column column-modify_time\">Modify time</th>
		<th scope=\"col\" id=\"status\" class=\"manage-column column-status\">Status</th></tr></thead>\n\n";


		print "<tfoot><tr><th scope=\"col\" id=\"id\" class=\"manage-column column-id\">#</th>
		<th scope=\"col\" id=\"name\" class=\"manage-column column-name\">Name</th>
		<th scope=\"col\" id=\"description\" class=\"manage-column column-description\">Description</th>
		<th scope=\"col\" id=\"points\" class=\"manage-column column-end_time\">Points</th>
		<th scope=\"col\" id=\"charge\" class=\"manage-column column-type\">Charge</th>
		<th scope=\"col\" id=\"serial\" class=\"manage-column column-points\">Serial number</th>
		<th scope=\"col\" id=\"modify_time\" class=\"manage-column column-modify_time\">Modify time</th>
		<th scope=\"col\" id=\"status\" class=\"manage-column column-status\">Status</th></tr></tfoot><tbody>\n\n";

		// проверяем существование таблицы, если нету - создаем
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix.GeoSets::DB_USERS_DEVICES."' ") != $wpdb->prefix.GeoSets::DB_USERS_DEVICES)
		{
			print "[+] no table finded, create new one...<br>\n";
			GeoSets::init_devices_table();	
		}

		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix.GeoSets::DB_USERS_USER_DEVICES."' ") != 
			$wpdb->prefix.GeoSets::DB_USERS_USER_DEVICES)
			GeoSets::init_user_devices_table();	
/*
		$lines = $wpdb->get_results("select id,serial_number,name,modify_time,lat,lng,alt,description,charge,status from ". 
			$wpdb->prefix. GeoSets::DB_USERS_USER_DEVICES ." udev left join ". $wpdb->prefix. GeoSets::DB_USERS_DEVICES.
			" devs on devs.id=udev.device_id where udev.user_id=".$current_user->ID) or $wpdb ->print_error();		
*/


		$res = mysql_query("select devs.id,serial_number,name,modify_time,lat,lng,alt,description,charge,status from ".
			$wpdb->prefix. GeoSets::DB_USERS_USER_DEVICES ." udev left join ". $wpdb->prefix. GeoSets::DB_USERS_DEVICES.
				" devs on devs.id=udev.device_id where udev.user_id=".$current_user->ID) or die(mysql_error());

		for ($i=0; $i<mysql_num_rows($res); $i++)
		{
			print "<tr><td>".mysql_result($res, $i, 'id')."</td><td>".mysql_result($res, $i, 'name')."</td><td>";
			print mysql_result($res, $i, 'description')."</td><td>".mysql_result($res, $i, 'lat').", ";
			print mysql_result($res, $i,'lng')." (alt ".mysql_result($res, $i,'alt')." meters)</td><td>".mysql_result($res, $i,'charge')."</td>";
			print "<td>".mysql_result($res, $i,'serial_number')."</td><td>".mysql_result($res, $i,'modify_time').
				"</td><td>".mysql_result($res, $i,'status')."</td></tr>\n\n";
		}
		
		print "</tbody></table><br><form method=POST>
		Serial: <input type=text name=serial style='width: 70px;'> Password: <input type=text name=pass style='width: 70px;'> <input type=submit value='Add'><br>
		</form><hr>\n";

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
				'm_save'           => __( 'Saved.', self::CONTENT ),
				'm_deleted'        => __( 'Deleted.', self::CONTENT ),
				'm_error'          => __( 'Server Error.', self::CONTENT ),
				'm_fail_error'     => __( 'Error save data on server. Try again leter.', self::CONTENT ),
				'm_row_delete'     => __( 'Row deleted!', self::CONTENT ),
				'm_row_not_delete' => __( 'Row not deleted!', self::CONTENT ),
				'm_confirm'        => __( 'Your have delete?', self::CONTENT ),
				'm_limit'          => __( 'Limit point on shape! Use less then ', self::CONTENT )
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

	// простая функция генерации паролей
	private static function generatePassword($length = 8) {
    		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    		$count = mb_strlen($chars);

    	for ($i = 0, $result = ''; $i < $length; $i++) {
       	 	$index = rand(0, $count - 1);
        	$result .= mb_substr($chars, $index, 1);
    	}

    		return $result;
	}
	/**
	 * view for cabinet dispatcher device page
	 */
	public static function geo_dispatcher_device_page() {
		global $current_user;
		global $wpdb;

		// добавляем девайс, генерим пароль если он не указан
		if (isset($_POST['serial']))
		{
			$nam = mysql_escape_string($_POST['nam']);
			$serial = mysql_escape_string($_POST['serial']);
			$pass = mysql_escape_string($_POST['pass']);
			$dscr = mysql_escape_string($_POST['descr']);

			if ($pass == '')
				$pass = GeoSets::generatePassword(6);

			if ($nam != '' && $serial != '' && $pass !='')
				$wpdb->query("insert into ".$wpdb->prefix.GeoSets::DB_USERS_DEVICES.
			" values (NULL, '$nam', '$serial', NOW(), '$pass', 0.0, 0.0, 0.0, '$dscr', 0, 100)") or $wpdb->print_error();
			print "[+] added device.<br>\n";
		}	

		// удаление устройств, удаляем сам девайс и все привязки к нему пользователей
		if (isset($_POST['del_device']))
		{
			$dev = 1*$_POST['del_device'];
			
			if ($dev > 0)
			{
				$wpdb->query("delete from ".$wpdb->prefix.GeoSets::DB_USERS_DEVICES." where id=$dev");
				$wpdb->query("delete from ".$wpdb->prefix.GeoSets::DB_USERS_USER_DEVICES." where device_id=$dev");
			}
		}

		get_currentuserinfo();
		// table user points
		/* $content = self::CONTENT;

		// List table
		if ( ! class_exists( 'GeoListDevicesTables' ) ) {
			require_once( 'class.geolist-devices-tables.php' );
		}
		$db = new GeoSets( GeoSets::DB_USERS_DEVICES );
		// current user data from DB
		// $data = $db->getDevicesByUserId( $current_user->ID );
			*/

		?>

		<div class="wrapper"><br><button id='add_dev_btn'>Add device</button>
		<script type='text/javascript' src='http://code.jquery.com/jquery-1.11.3.min.js' ></script>
		<script type="text/javascript">

		$( document ).ready(function() {
			$('#add_dev_form').hide();

			$( "#add_dev_btn" ).click(function() {
				  $('#add_dev_form').slideToggle();
			});
		});
			
		</script>	
		<div id='add_dev_form'><form method=POST>
			<table>
			<tr><td>Name:</td><td><input type=text name=nam></td></tr>
			<tr><td>Serial:</td><td><input type=text name=serial></td></tr>
			<tr><td>Password:</td><td><input type=text name=pass></td></tr>
                        <tr><td>Descr:</td><td><textarea name=descr></textarea></td></tr></table>
			<button >Add</button></form>
			
		</div>

			<h2><?php _e( 'Your managment Devices', $content ) ?></h2>
			<?php
		print "
		<script type='text/javascript' >
		function checkform(frm) 
		{
			var aresure = confirm('Are you sure you want to delete?');
			return aresure;
		}
		</script>

		<table class=\"wp-list-table widefat fixed\">
		<thead><tr>
		<th scope=\"col\" id=\"name\" class=\"manage-column column-name\">Name</th>
		<th scope=\"col\" id=\"pass\" class=\"manage-column column-pass\">Password</th>
		<th scope=\"col\" id=\"description\" class=\"manage-column column-description\">Description</th>
		<th scope=\"col\" id=\"points\" class=\"manage-column column-end_time\">Points</th>
		<th scope=\"col\" id=\"charge\" class=\"manage-column column-type\">Charge</th>
		<th scope=\"col\" id=\"serial\" class=\"manage-column column-points\">Serial number</th>
		<th scope=\"col\" id=\"modify_time\" class=\"manage-column column-modify_time\">Modify time</th>
		<th scope=\"col\" id=\"status\" class=\"manage-column column-status\">Status</th>
		<th scope=\"col\"></th>		
		</tr></thead>\n\n";


		print "<tfoot><tr>
		<th scope=\"col\" id=\"name\" class=\"manage-column column-name\">Name</th>
		<th scope=\"col\" id=\"pass\" class=\"manage-column column-name\">Password</th>
		<th scope=\"col\" id=\"description\" class=\"manage-column column-description\">Description</th>
		<th scope=\"col\" id=\"points\" class=\"manage-column column-end_time\">Points</th>
		<th scope=\"col\" id=\"charge\" class=\"manage-column column-type\">Charge</th>
		<th scope=\"col\" id=\"serial\" class=\"manage-column column-points\">Serial number</th>
		<th scope=\"col\" id=\"modify_time\" class=\"manage-column column-modify_time\">Modify time</th>
		<th scope=\"col\" id=\"status\" class=\"manage-column column-status\">Status</th>
		<th scope=\"col\"></th></tr></tfoot><tbody>\n\n";

		// проверяем существование таблицы, если нету - создаем
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix.GeoSets::DB_USERS_DEVICES."' ") != $wpdb->prefix.GeoSets::DB_USERS_DEVICES)
		{
			print "[+] no table with device list, create new one!<br>\n";
			GeoSets::init_devices_table();	
		}

		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix.GeoSets::DB_USERS_USER_DEVICES."' ") != 
			$wpdb->prefix . GeoSets::DB_USERS_USER_DEVICES)
		{
			print "[+] no table with user_devices, create new one!<br>\n";
			GeoSets::init_user_devices_table();	
		}

		$res = mysql_query("select id,serial_number,password,name,modify_time,lat,lng,alt,description,charge,status from ".
			$wpdb->prefix. GeoSets::DB_USERS_DEVICES." where 1"); 

		for ($i=0; $i<mysql_num_rows($res); $i++)
		{
			$dev_id = 1*mysql_result($res, $i, 'id');
			print "<tr><td style='width: 20px;'>".mysql_result($res, $i, 'name')."</td><td>".mysql_result($res, $i, 'password');
			print "</td><td>".mysql_result($res, $i, 'description')."</td><td>".mysql_result($res, $i, 'lat').", ";
			print mysql_result($res, $i,'lng')." (alt ".mysql_result($res, $i,'alt')." meters)</td><td>".mysql_result($res, $i,'charge')."% </td>";
			print "<td>".mysql_result($res, $i,'serial_number')."</td><td>".mysql_result($res, $i,'modify_time')."</td><td>".
			mysql_result($res, $i,'status')."</td><td><form method=POST onsubmit='return checkform($(this).parrent);'><input type=hidden name=del_device value='$dev_id' >".
			"<input type=submit value='Delete'></form></td></tr>\n\n";
		}
		
		
		print "</tbody></table>";
/*
		$que = "select id,serial_number,name,modify_time,lat,lng,alt,description,charge,status from ". 
			 $wpdb->prefix. GeoSets::DB_USERS_DEVICES." where 1";
		$lines = $wpdb->get_results(  $wpdb->prepare($que) );	

		foreach ($lines as $dev)
		{
			print "<tr><td>".$dev['id']."</td><td>".$dev['name']."</td><td>".$dev['description']."</td>\n";
			print "<td>".$dev['lat'].", ".$dev['lng']." (alt ".$dev['alt']." meters)</td><td>".$dev['charge']."</td>\n";
			print "<td>".$dev['serial_number']."</td><td>".$dev['modify_time']."</td><td>".$dev['status']."</td></tr>\n\n";
		}


			$table = new GeoListDevicesTables();
			$table->setData( $data );
			$table->prepare_items();
			$table->display();    */
			?>
		</div>

		<?php
	}

	/**
	 * view for cabinet dispatcher tracks page
	 */
	public static function geo_dispatcher_track_page() {
		echo "It's OK!";
	}

}
