<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * @package Quotes Collection
 * @since 2.0
 */

// if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;
$quotescollection_sites = is_multisite() ? get_sites(
	array(
		'fields' => 'ids',
		'number' => 0,
	)
) : array( get_current_blog_id() );
foreach ( $quotescollection_sites as $quotescollection_site_id ) {
	if ( is_multisite() ) {
		switch_to_blog( $quotescollection_site_id );
	}
	delete_option( 'quotescollection' );
	delete_option( 'widget_quotescollection' );
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'quotescollection' ) );
	if ( is_multisite() ) {
		restore_current_blog();
	}
}
delete_site_option( 'quotescollection' );
delete_site_option( 'widget_quotescollection' );
