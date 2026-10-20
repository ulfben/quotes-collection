<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * Plugin Name: Quotes Collection
 * Plugin URI: https://github.com/ulfben/quotes-collection
 * Description: Collect and display quotations with blocks, widgets and shortcodes. Show random quotes, daily quotes or a complete collection.
 * Version: 2.6.0
 * Author: Srini G, Ulf Benjaminsson
 * Author URI: http://srinig.com/
 * Text Domain: quotes-collection
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/*
	Copyright 2007-2019 Srini G (email : s@srinig.com)
	Copyright 2026 Ulf Benjaminsson (maintenance and modernization)

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

/** Prevent direct access to the file **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


require_once __DIR__ . '/inc/class-quotes-collection.php';
require_once __DIR__ . '/inc/class-quotes-collection-db.php';
require_once __DIR__ . '/inc/class-quotes-collection-quote.php';
require_once __DIR__ . '/inc/class-quotes-collection-widget.php';
require_once __DIR__ . '/inc/class-quotes-collection-shortcode.php';
if ( is_admin() ) {
	require_once __DIR__ . '/inc/class-quotes-collection-admin-list-table.php';
	require_once __DIR__ . '/inc/class-quotes-collection-admin.php';
}
if ( function_exists( 'register_block_type' ) ) {
	include_once __DIR__ . '/blocks/blocks.php';
}

register_activation_hook( __FILE__, array( 'Quotes_Collection', 'activate' ) );
add_action( 'plugins_loaded', array( 'Quotes_Collection', 'load' ) );
add_action( 'widgets_init', array( 'Quotes_Collection_Widget', 'register' ) );


/**
 * The template function that generates a random quote
 *
 * @param array $args {
 *     'show_author'    => true,
 *     'show_source'    => false,
 *     'ajax_refresh'   => true,
 *     'random'         => true,
 *     'auto_refresh'   => false,
 *     'tags'           => '',
 *     'char_limit'     => 500,
 *     'echo'           => true,
 * }
 *
 * @return string containing the quote block, if 'echo' is passed in as false
 * @return bool true when quote is already echoed, ie., when echo is true
 * @return bool false on error
 *
 * @link http://wordpress.org/plugins/quotes-collection/other_notes
 */
function quotescollection_quote( $args = null ) {

	global $quotescollection;
	global $quotescollection_instances;

	if ( null === $args || ( ! is_string( $args ) && ! is_array( $args ) ) ) {
		$args = array();
	} elseif ( is_string( $args ) ) { // If args are passed as a string
		wp_parse_str( $args, $parsed_args );
		$args = $parsed_args;
	}

	if ( null === $quotescollection_instances ) {
		$quotescollection_instances = 0;
	}

	++$quotescollection_instances;
	$args['instance'] = 'tf_quotescollection_' . $quotescollection_instances;

	return $quotescollection->quote( $args );
}

/** Returns the plugin's url. If $path is passed, it's appended. */
/**
 * Read the plugin's admin fields without retaining PHP's request slashes.
 *
 * Authorization and action-specific nonces are checked by process_requests().
 * Read-only list filters deliberately work without a nonce.
 *
 * @return array Sanitized, explicitly supported request fields.
 */
function quotescollection_request() {
	$request = array(
		'id'       => 0,
		'quote_id' => 0,
		'action'   => '',
		'action2'  => '',
		'page'     => 'quotes-collection',
	);
	$fields  = array( 'id', 'quote_id', 'action', 'action2', 'page', 'submit', 'quote', 'author', 'source', 'tags', 'public', 's', 'orderby', 'order', 'refresh_link_text', 'auto_refresh_max', 'dynamic_fetch', 'user_level_manage_quotes', 'bulkcheck' );
	foreach ( $fields as $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This accessor also serves read-only filters; mutations verify their own nonce and capability.
		if ( ! isset( $_REQUEST[ $key ] ) ) {
			continue;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The value is type-checked and sanitized immediately below.
		$value = wp_unslash( $_REQUEST[ $key ] );
		if ( 'bulkcheck' === $key ) {
			$request[ $key ] = is_array( $value ) ? array_map( 'absint', array_filter( $value, 'is_scalar' ) ) : array();
		} elseif ( is_scalar( $value ) ) {
			if ( in_array( $key, array( 'id', 'quote_id', 'auto_refresh_max' ), true ) ) {
				$request[ $key ] = absint( $value );
			} elseif ( in_array( $key, array( 'quote', 'author', 'source' ), true ) ) {
				$request[ $key ] = wp_kses_post( (string) $value );
			} else {
				$request[ $key ] = sanitize_text_field( (string) $value );
			}
		}
	}
	return $request;
}

/** Returns the plugin's url. If $path is passed, it's appended. */
function quotescollection_url( $path = '' ) {
	// If $path comes with a slash, remove it as the function be adding
	if ( $path && '/' == $path[0] ) {
		$path = substr( $path, 1 );
	}
	return plugins_url( $path, __FILE__ );
}

/** Returns the plugin's home directory. If $path is passed, it's appended. */
function quotescollection_rel_path( $path = '' ) {
	// If $path comes with a slash, remove it as we'll be adding
	if ( $path && '/' == $path[0] ) {
		$path = substr( $path, 1 );
	}
	return dirname( plugin_basename( __FILE__ ) ) . '/' . $path;
}
