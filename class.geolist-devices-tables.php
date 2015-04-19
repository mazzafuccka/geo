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

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
require_once( 'class.geosets.php' );

/**
 * Class view custom list tables
 */
class GeoListDevicesTables extends WP_List_Table {

	private $data = array();

	/**
	 * @return array
	 */
	public function get_columns() {
		$content = GeoSets::CONTENT;
		$columns = array(
			'id'          => __( '#', $content ),
			'serial_number'        => __( 'Serial Number', $content ),
			'name' => __( 'Name', $content ),
			'modify_time'  => __( 'Edit time', $content ),
			'password'    => __( 'Password', $content ),
			'device_points'        => __( 'Device Coordinates', $content ),
			'description'      => __( 'Description', $content ),
			'charge' => __( 'Charged', $content ),
			'status'      => __( 'Status', $content )
		);

		return $columns;
	}

	/**
	 *  Prepare data
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->getData();
	}

	/**
	 * types output columns
	 * @param $item
	 * @param $column_name
	 *
	 * @return mixed
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'serial_number':
			case 'name':
			case 'modify_time':
			case 'password':
			case 'end_time':
			case 'device_points':
			case 'description':
			case 'charge':
			case 'status':
			case 'actions':
				return '<a class="remove" href="#" data-id ="'.$item['id'].'" >x</a>';break;
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * getData
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * sets Data
	 *
	 * @param $data
	 */
	public function setData( $data ) {
		$this->data = $data;
	}
}