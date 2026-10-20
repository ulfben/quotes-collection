<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * @package quotes-collection
 * @since 2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function quotescollection_block_category( $categories ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'quotes-collection',
				'title' => __( 'Quotes Collection', 'quotes-collection' ),
			),
		)
	);
}

add_filter( 'block_categories_all', 'quotescollection_block_category', 5 );

require_once __DIR__ . '/quotes/quotes.php';
require_once __DIR__ . '/random-quote/random-quote.php';
