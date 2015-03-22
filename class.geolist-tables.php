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
class GeoListTables extends WP_List_Table {

	private $data = array();

	/**
	 * @return array
	 */
	public function get_columns() {
		$content = GeoSets::CONTENT;
		$columns = array(
			'id'          => __( '#', $content ),
			'name'        => __( 'Name', $content ),
			'description' => __( 'Description', $content ),
			'start_time'  => __( 'Start time', $content ),
			'end_time'    => __( 'End time', $content ),
			'type'        => __( 'Type', $content ),
			'points'      => __( 'Coordinates', $content ),
			'modify_time' => __( 'Edit time', $content ),
			'status'      => __( 'Status', $content ),
			'actions'      => __( 'Action', $content )
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
		// todo by types
		switch ( $column_name ) {
			case 'id':
			case 'name':
			case 'description':
			case 'start_time':
			case 'end_time':
			case 'type':
			case 'points':
			case 'modify_time':
			case 'status':

			default:
				return $item[ $column_name ];
			case 'actions':
				return '<a class="remove" href="#" data-id ="'.$item['id'].'" >x</a>';
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