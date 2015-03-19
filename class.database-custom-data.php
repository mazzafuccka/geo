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
 * Abstract class which has helper functions to get data from the database CRUD
 */
abstract class DataBaseCustomData {
	/**
	 * The current table name
	 *
	 * @var boolean
	 */
	private $tableName = false;

	/**
	 * Constructor for the database class to inject the table name
	 *
	 * @param String $tableName - The current table name
	 */
	public function __construct( $tableName ) {
		$this->tableName = $tableName;
	}

	/**
	 * Insert data into the current data
	 *
	 * @param  array $data - Data to enter into the database table
	 *
	 * @return InsertQuery Object
	 */
	public function insert( array $data ) {
		global $wpdb;

		if ( empty( $data ) ) {
			return false;
		}

		$wpdb->insert( $this->tableName, $data );

		return $wpdb->insert_id;
	}

	/**
	 * Update a table record in the database
	 *
	 * @param  array $data - Array of data to be updated
	 * @param  array $conditionValue - Key value pair for the where clause of the query
	 *
	 * @return Updated object
	 */
	public function update( array $data, array $conditionValue ) {
		global $wpdb;

		if ( empty( $data ) ) {
			return false;
		}

		$updated = $wpdb->update( $this->tableName, $data, $conditionValue );

		return $updated;
	}

	/**
	 * Delete row on the database table
	 *
	 * @param  array $conditionValue - Key value pair for the where clause of the query
	 *
	 * @return Int - Num rows deleted
	 */
	public function delete( array $conditionValue ) {
		global $wpdb;

		$deleted = $wpdb->delete( $this->tableName, $conditionValue );

		return $deleted;
	}

	/**
	 * Get by id
	 * @param $id
	 *
	 * @return array
	 */
	public function getById ($id){
		global $wpdb;

		if(!empty($id))
		{
			$sql = $wpdb->prepare('SELECT * FROM '.$this->tableName.' WHERE id = %d', $id);
			return $wpdb->get_results($sql, ARRAY_A);
		}

		return array();
	}

	/**
	 * Serach rows by user
	 * @param $user_id
	 *
	 * @return array
	 */
	public function getByUserId ($user_id){
		global $wpdb;

		if(!empty($user_id))
		{
			$sql = $wpdb->prepare('SELECT * FROM '.$this->tableName.' WHERE user_id = %d', $user_id);
			return $wpdb->get_results($sql, ARRAY_A);
		}

		return array();
	}
}