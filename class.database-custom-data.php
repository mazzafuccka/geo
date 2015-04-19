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

		// columns
		$columns = $valuesType = $values = array();
		foreach ( $data as $key => $row ) {
			if ( $key === 'points' ) {
				$columns[]    = $key;
				$valuesType[] = "GeomFromText('" . $row . "')";
			} else {
				$columns[]    = $key;
				$values[]     = $row;
				$valuesType[] = self::typeValue( $row );
			}
		}
		$columns_string = implode( ',', $columns );
		$columns_string = '(' . $columns_string . ')';

		$typeValue_string = implode( ',', $valuesType );
		$typeValue_string = '(' . $typeValue_string . ')';

		$sql = $wpdb->prepare(
			"INSERT INTO $this->tableName $columns_string VALUES $typeValue_string",
			$values
		);
		$wpdb->query( $sql );

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

		$string = array();
		$id     = $conditionValue['id'];
		// data update
		foreach ( $data as $key => $row ) {
			if ( $key === 'points' ) {
				$string[] = $key . " = GeomFromText('" . $row . "')";
			} else {
				$values[] = $row;
				$string[] = $key . ' = ' . self::typeValue( $row );
			}
		}

		$columns_string = implode( ',', $string );
		array_push( $values, $id );

		$sql = $wpdb->prepare(
			"UPDATE $this->tableName SET $columns_string WHERE id = %d",
			$values
		);

		$updated = $wpdb->query( $sql );

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
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public function getById( $id ) {
		global $wpdb;

		if ( ! empty( $id ) ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->tableName . ' WHERE id = %d', $id );

			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return array();
	}

	/**
	 * Search rows by user points
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function getByUserIdPoints( $user_id, $extired = false ) {
		global $wpdb;

		$addWhere = '';

		if ( $extired == true ) {
			$addWhere = ' and (end_time > NOW() or end_time is NULL) and status = 1';
		}
		if ( ! empty( $user_id ) ) {
			$sql = $wpdb->prepare( 'SELECT
				id,
				user_id,
				name,
				modify_time,
				start_time,
				end_time,
				AsText(points) as points,
				description,
				type,
				status
 			FROM ' . $this->tableName . ' WHERE user_id = %d ' . $addWhere, $user_id );

			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return array();
	}

	/**
	 * Search rows by user
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function getByUserId( $user_id ) {
		global $wpdb;

		if ( ! empty( $user_id ) ) {
			$sql = $wpdb->prepare( 'SELECT *
 			FROM ' . $this->tableName . ' WHERE user_id = %d ', $user_id );

			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return array();
	}

	/**
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public static function typeValue( $value ) {
		if ( is_int( $value ) ) {
			return '%d';
		} else if ( is_float( $value ) ) {
			return '%f';
		} else {
			return '%s';
		}
	}

	/**
	 * Search matest
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function getLastShapeByUserId( $user_id ) {
		global $wpdb;

		$addWhere = ' and (end_time > NOW() or end_time is NULL) and status = 1';

		if ( ! empty( $user_id ) ) {
			$sql = $wpdb->prepare( 'SELECT
				id,
				user_id,
				name,
				modify_time,
				start_time,
				end_time,
				AsText(points) as points,
				description,
				type,
				status
 			FROM ' . $this->tableName . ' WHERE user_id = %d ' . $addWhere . ' ORDER BY modify_time DESC LIMIT 1 ', $user_id );

			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return array();
	}
}