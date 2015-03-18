<?php

/*
Plugin Name: GeoSets
Plugin URI: https://geoSets.drdim.ru/
Description: Geo positions plugins interfaces
Version: 1.0
Author: drdim
Author URI: https://github.com/drdim
License: GPL2
*/
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
// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'GEOSETS', '1.0' );
define( 'GEOSETS__MINIMUM_WP_VERSION', '3.6' );
define( 'GEOSETS__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEOSETS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( GEOSETS__PLUGIN_DIR . 'class.geosets.php' );
register_activation_hook( __FILE__, array( 'GeoSets', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'GeoSets', 'plugin_deactivation' ) );
add_action( 'init', array( 'GeoSets', 'init' ) );

// for admin page init
if ( is_admin() ) {
    require_once( GEOSETS__PLUGIN_DIR. 'class.geosets-admin.php' );
    add_action( 'init', array( 'GeoSetsAdmin', 'init' ) );
}






