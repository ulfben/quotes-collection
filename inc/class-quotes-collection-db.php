<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * The Quotes Collection Database Class
 *
 * @package Quotes Collection
 * @since 2.0
 */

class Quotes_Collection_DB {

	const PLUGIN_DB_VERSION = '1.4';

	private $db;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/** Resolve the current site's table after switch_to_blog(). */
	private function table_name() {
		return $this->db->prepare( '%i', $this->db->prefix . 'quotescollection' );
	}

	/**
	 * Fetches quote entries from the database
	 *
	 * @param array $args = array()
	 * @see $this->frame_condition() for arguments that can be passed
	 * @return array of quote entries
	 */
	public function get_quotes_array( $args = array() ) {
			$sql = 'SELECT `quote_id`, `quote`, `author`, `source`, `tags`, `public`, `time_added`
			FROM ' . $this->table_name();

		if ( $args ) {
			$sql .= $this->frame_condition( $args );
		}

		if ( $quotes = $this->db->get_results( $sql, ARRAY_A ) ) {
			return $quotes;
		} else {
			return array();
		}
	}


	/**
	 * Fetches quote entries from the database and returns the array of
	 * Quotes_Collection_Quote objects
	 *
	 * @param array $args = array()
	 * @see $this->frame_condition() for arguments that can be passed
	 * @return array of Quotes_Collection_Quote objects
	 */
	public function get_quotes( $args = array() ) {
		if ( $quotes_array = $this->get_quotes_array( $args ) ) {
			$quotes = array();
			foreach ( $quotes_array as $quote_data ) {
				$quotes[] = new Quotes_Collection_Quote( $quote_data );
			}
			return $quotes;
		}
		return array();
	}

	/** Fetches a single quote from the database **/
	public function get_quote( $args = array() ) {
		$args['num_quotes'] = 1;
		if ( $quote_array = $this->get_quotes( $args ) ) {
			return $quote_array[0];
		} else {
			return false;
		}
	}

	/**
	 * Select one public quote for the site's calendar day, in quote-ID order.
	 *
	 * Filters define the rotation pool; pagination and sorting cannot change it.
	 * No global random state or persistent daily cache is needed.
	 *
	 * @param array                   $args Quote filters.
	 * @param DateTimeImmutable|null $now  Optional clock instant, for testing.
	 * @return Quotes_Collection_Quote|false The selected quote, or no match.
	 */
	public function get_daily_quote( $args = array(), ?DateTimeImmutable $now = null ) {
		$args['public'] = 'yes';
		unset( $args['num_quotes'], $args['start'], $args['orderby'], $args['order'] );
		$count = $this->count( $args );
		if ( 0 === $count ) {
			return false;
		}

		$local_date = ( $now ?? current_datetime() )->setTimezone( wp_timezone() )->format( 'Y-m-d' );
		// Interpret the local calendar date as UTC solely to count whole days,
		// so daylight-saving transitions cannot shorten or lengthen the cycle.
		$calendar_day = new DateTimeImmutable( $local_date, new DateTimeZone( 'UTC' ) );
		$day_number   = intdiv( $calendar_day->getTimestamp(), DAY_IN_SECONDS );

		$args['orderby'] = 'quote_id';
		$args['order']   = 'ASC';
		$args['start']   = ( ( $day_number % $count ) + $count ) % $count;
		return $this->get_quote( $args );
	}

	/**
	 * Fetches quote entry with a specific ID
	 *
	 * @param int $quote_id
	 * @return array the quote entry
	 */
	public function get_quote_with_id( $quote_id ) {
		return $this->get_quote( array( 'quote_id' => $quote_id ) );
	}

	/**
	 * Checks if our Quotes Collection table is found in the database
	 *
	 * @return bool true if found, false if not
	 */
	private function is_table_found() {
		$name = $this->db->prefix . 'quotescollection';
		return $name === $this->db->get_var( $this->db->prepare( 'SHOW TABLES LIKE %s', $this->db->esc_like( $name ) ) );
	}

	/**
	 * Validates the quote data before it can be safely stored in the database
	 *
	 * @param array $data the quote data
	 * @return array the validated data
	 */
	private function validate_data( $data = array() ) {
		if ( is_object( $data ) ) {
			$data = (array) $data; }
		if ( ! is_array( $data ) || ! isset( $data['quote'] ) || ! is_string( $data['quote'] ) || '' === trim( $data['quote'] ) ) {
			return array(); }
		foreach ( array( 'author', 'source', 'tags', 'public' ) as $key ) {
			if ( isset( $data[ $key ] ) && ! is_string( $data[ $key ] ) ) {
				return array(); }
		}
		$links = array(
			'a' => array(
				'href'  => array(),
				'title' => array(),
			),
		);
		return array(
			'quote'  => wp_kses_post( $data['quote'] ),
			'author' => wp_kses( $data['author'] ?? '', $links ),
			'source' => wp_kses( $data['source'] ?? '', $links ),
			'tags'   => implode( ',', array_map( 'trim', explode( ',', wp_strip_all_tags( $data['tags'] ?? '' ) ) ) ),
			'public' => isset( $data['public'] ) && in_array( $data['public'], array( 'yes', 'on' ), true ) ? 'yes' : 'no',
		);
	}


	/**
	 * Function to store a single quote in the db
	 *
	 * @param array $quote_data the quote data
	 */
	public function put_quote( $quote_data = array() ) {
		$data = $this->validate_data( $quote_data );
		if ( ! $data || ! $this->is_table_found() ) {
			return false; }
		$data['time_added'] = current_time( 'mysql' );
		$result             = $this->db->insert( $this->db->prefix . 'quotescollection', $data, array_fill( 0, count( $data ), '%s' ) );
		return false === $result ? false : $this->db->insert_id;
	}

	/**
	 * Function to store a bulk of quote entries. Used by the import
	 * functionality.
	 *
	 * @param array $quotes_data a multidimensional array of quote data
	 */
	public function put_quotes( $quotes_data = array() ) {
		if ( ! is_array( $quotes_data ) || ! array_is_list( $quotes_data ) ) {
			return false; }
		$validated = array();
		foreach ( $quotes_data as $quote_data ) {
			$data = $this->validate_data( $quote_data );
			if ( ! $data ) {
				return false; }
			$validated[] = $data;
		}
		if ( ! $validated ) {
			return 0; }
		$values = array();
		$rows   = array();
		foreach ( $validated as $data ) {
			array_push( $values, $data['quote'], $data['author'], $data['source'], $data['tags'], $data['public'], current_time( 'mysql' ) );
			$rows[] = '(%s, %s, %s, %s, %s, %s)';
		}
		$sql = 'INSERT INTO ' . $this->table_name() . ' (quote, author, source, tags, public, time_added) VALUES ' . implode( ', ', $rows );
		return $this->db->query( $this->db->prepare( $sql, $values ) );
	}

	/**
	 * Updates a quote entry in DB with new values
	 *
	 * @param array $quote_data the quote entry
	 */
	public function update_quote( $quote_data = array() ) {
		if ( is_object( $quote_data ) ) {
			$quote_data = (array) $quote_data; }
		$data = $this->validate_data( $quote_data );
		if ( ! $data ) {
			return false; }
		$id = absint( $quote_data['quote_id'] ?? 0 );
		if ( ! $id ) {
			return $this->put_quote( $quote_data ); }
		$data['time_updated'] = current_time( 'mysql' );
		return $this->db->update( $this->db->prefix . 'quotescollection', $data, array( 'quote_id' => $id ), array_fill( 0, count( $data ), '%s' ), array( '%d' ) );
	}


	/**
	 * Deletes a quote entry in the DB
	 *
	 * @param int $quote_id the ID of the entry to be deleted
	 */
	public function delete_quote( $quote_id ) {
		if ( is_numeric( $quote_id ) ) {
			$sql = $this->db->prepare( 'DELETE FROM ' . $this->table_name() . ' WHERE quote_id = %d', $quote_id );
			return $this->db->query( $sql );
		} else {
			return 0;
		}
	}


	/**
	 * Function to delete a bulk of quotes
	 *
	 * @param array $quote_ids an array of IDs of the entries to be deleted
	 */
	public function delete_quotes( $quote_ids ) {
		if ( ! is_array( $quote_ids ) || ! $quote_ids ) {
			return 0;
		}
		foreach ( $quote_ids as $quote_id ) {
			if ( ! is_numeric( $quote_id ) ) {
				return 0;
			}
		}
		$placeholders        = array_fill( 0, count( $quote_ids ), '%d' );
		$placeholders_string = implode( ', ', $placeholders );
		$sql                 = $this->db->prepare( 'DELETE FROM ' . $this->table_name() . " WHERE quote_id IN ($placeholders_string)", $quote_ids );
		return $this->db->query( $sql );
	}

	/**
	 * Function to make a set of entries private or public
	 *
	 * @param array  $quote_ids an array of IDs of the entries to be updated
	 * @param string $visibility should be'yes' or 'no'
	 */
	public function change_visibility( $quote_ids, $visibility = 'yes' ) {
		if ( ! is_array( $quote_ids ) || ! $quote_ids || ( $visibility != 'yes' && $visibility != 'no' ) ) {
			return 0;
		}
		foreach ( $quote_ids as $quote_id ) {
			if ( ! is_numeric( $quote_id ) ) {
				return -1;
			}
		}
		$placeholders = array_fill( 0, count( $quote_ids ), '%d' );
		$ids_format   = implode( ', ', $placeholders );
		$sql          = $this->db->prepare( 'UPDATE ' . $this->table_name() . " SET public = %s, time_updated = NOW() WHERE quote_id IN ($ids_format)", array_merge( array( $visibility ), $quote_ids ) );
		return $this->db->query( $sql );
	}

	/**
	 * Counts and returns the number of entries with a particular entries.
	 * If no parameter is passed, counts the total number of entries in DB
	 *
	 * @param array $condition
	 * @return int
	 */
	public function count( $condition = array() ) {
		unset( $condition['orderby'], $condition['order'], $condition['num_quotes'], $condition['start'] );
		$sql = 'SELECT COUNT(*) FROM ' . $this->table_name();
		if ( $condition ) {
			$sql .= $this->frame_condition( $condition );
		}
		$count = $this->db->get_var( $sql );
		return (int) $count;
	}

	public static function install_db() {

		$options = get_option( 'quotescollection' );
		if ( $options !== false && isset( $options['db_version'] ) && self::PLUGIN_DB_VERSION == $options['db_version'] ) {
			return;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'quotescollection';

		if ( ! defined( 'DB_CHARSET' ) || ! ( $db_charset = DB_CHARSET ) ) {
			$db_charset = 'utf8';
		}
		$db_charset = 'CHARACTER SET ' . $db_charset;
		$db_collate = '';
		if ( defined( 'DB_COLLATE' ) && $db_collate = DB_COLLATE ) {
			$db_collate = 'COLLATE ' . $db_collate;
		}

		// if table name already exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) == $table_name ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
			if ( false === $wpdb->query( "ALTER TABLE `{$table_name}` {$db_charset} {$db_collate}" ) ) {
				return;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
			if ( false === $wpdb->query( "ALTER TABLE `{$table_name}` MODIFY quote TEXT {$db_charset} {$db_collate}" ) ) {
				return;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
			if ( false === $wpdb->query( "ALTER TABLE `{$table_name}` MODIFY author VARCHAR(255) {$db_charset} {$db_collate}" ) ) {
				return;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
			if ( false === $wpdb->query( "ALTER TABLE `{$table_name}` MODIFY source VARCHAR(255) {$db_charset} {$db_collate}" ) ) {
				return;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
			if ( ! ( $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'tags'" ) ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
				if ( false === $wpdb->query( "ALTER TABLE `{$table_name}` ADD `tags` VARCHAR(255) {$db_charset} {$db_collate} AFTER `source`" ) ) {
					return;
				}
			}
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
			if ( ! ( $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'public'" ) ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema migration uses only the trusted WordPress prefix and charset constants; no request values.
				if ( false === $wpdb->query( "ALTER TABLE `{$table_name}` CHANGE `visible` `public` enum('yes', 'no') DEFAULT 'yes' NOT NULL" ) ) {
					return;
				}
			}
		} else {
			// Creating the table ... fresh!
			$sql = 'CREATE TABLE ' . $table_name . " (
				quote_id MEDIUMINT NOT NULL AUTO_INCREMENT,
				quote TEXT NOT NULL,
				author VARCHAR(255),
				source VARCHAR(255),
				tags VARCHAR(255),
				public enum('yes', 'no') DEFAULT 'yes' NOT NULL,
				time_added datetime NOT NULL,
				time_updated datetime,
				PRIMARY KEY  (quote_id)
			) {$db_charset} {$db_collate};";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- DDL uses the trusted WordPress table prefix and database charset constants; no request data.
			$results = $wpdb->query( $sql );
		}

		// Ensure $options is an array before modifying it
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		if ( $wpdb->last_error ) {
			return; }
		$options['db_version'] = self::PLUGIN_DB_VERSION;
		update_option( 'quotescollection', $options );
	}



	public static function uninstall_db() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}quotescollection" );
	}



	/**
	 * Frames the database query condition
	 *
	 * @param array $args = array (
	 *    'quote_id'   => 0,          // Number, quote_id of the quote to be fetched
	 *    'author'     => '',         // String, to fetch quote/s by a particular author
	 *    'source'     => '',         // String, to fetch quote/s with a particular source
	 *    'tags'       => '',         // String, comma separated tags, to fetch quote/s having one of the tags
	 *    'public'     => ''          // String, 'yes' or 'no'
	 *    'char_limit' => 0,          // Number, character limit
	 *    'exclude'    => 0,          // Number, quote_id of the particular quote to be excluded
	 *    'splice'     => 0,          // Number, quote_id, only quotes with IDs less than this will be fetched
	 *    'orderby'    => 'quote_id', // String, can be one of 'quote_id', 'author', 'source', 'time_added', 'random'
	 *    'order'      => 'ASC',      // String, 'ASC' or 'DESC'
	 *    'num_quotes' => 0,          // Number of quotes to be fetched
	 *    'start'      => 0,          // Number, quote_id, used in pagination along with num_quotes
	 * )
	 *
	 * @return string    The SQL condition
	 */
	private function frame_condition( $args = array() ) {
		$where = array();
		foreach ( array(
			'quote_id' => '=',
			'exclude'  => '<>',
			'splice'   => '<',
		) as $key => $operator ) {
			if ( isset( $args[ $key ] ) && is_numeric( $args[ $key ] ) ) {
				$where[] = $this->db->prepare( "quote_id $operator %d", max( 0, (int) $args[ $key ] ) );
			}
		}
		foreach ( array( 'author', 'source' ) as $key ) {
			if ( isset( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
				$where[] = $this->db->prepare( "$key = %s", wp_strip_all_tags( $args[ $key ] ) );
			}
		}
		if ( ! empty( $args['tags'] ) && is_string( $args['tags'] ) ) {
			$tags = array();
			foreach ( explode( ',', html_entity_decode( $args['tags'], ENT_QUOTES, 'UTF-8' ) ) as $tag ) {
				$tag = wp_strip_all_tags( trim( $tag ) );
				if ( '' === $tag ) {
					continue; }
				$like   = $this->db->esc_like( $tag );
				$tags[] = $this->db->prepare( '(tags = %s OR tags LIKE %s OR tags LIKE %s OR tags LIKE %s)', $tag, $like . ',%', '%,' . $like . ',%', '%,' . $like );
			}
			if ( $tags ) {
				$where[] = '(' . implode( ' OR ', $tags ) . ')'; }
		}
		if ( ! empty( $args['search'] ) && is_string( $args['search'] ) ) {
			$like    = '%' . $this->db->esc_like( wp_strip_all_tags( trim( $args['search'] ) ) ) . '%';
			$where[] = $this->db->prepare( '(quote LIKE %s OR author LIKE %s OR source LIKE %s OR tags LIKE %s)', $like, $like, $like, $like );
		}
		if ( isset( $args['char_limit'] ) && is_numeric( $args['char_limit'] ) && $args['char_limit'] > 0 ) {
			$where[] = $this->db->prepare( 'CHAR_LENGTH(quote) <= %d', (int) $args['char_limit'] );
		}
		if ( isset( $args['public'] ) && in_array( $args['public'], array( 'yes', 'no' ), true ) ) {
			$where[] = $this->db->prepare( 'public = %s', $args['public'] );
		}
		$sql     = $where ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$orderby = $args['orderby'] ?? '';
		if ( 'random' === $orderby ) {
			$sql .= ' ORDER BY RAND()';
		} elseif ( in_array( $orderby, array( 'quote_id', 'quote', 'author', 'source', 'time_added' ), true ) ) {
			$sql .= ' ORDER BY ' . $orderby;
			$sql .= in_array( $args['order'] ?? '', array( 'DESC', 'desc' ), true ) ? ' DESC' : ' ASC';
		}
		if ( isset( $args['num_quotes'] ) && is_numeric( $args['num_quotes'] ) ) {
			$sql .= $this->db->prepare( ' LIMIT %d, %d', max( 0, (int) ( $args['start'] ?? 0 ) ), max( 0, (int) $args['num_quotes'] ) );
		}
		return $sql;
	}
}
