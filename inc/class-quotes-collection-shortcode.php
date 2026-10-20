<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * @package Quotes Collection
 * @since 2.0
 */

class Quotes_Collection_Shortcode {

	public function __construct() {
		add_shortcode( 'quotescollection', array( $this, 'do_shortcode' ) );
		add_shortcode( 'quotcoll', array( $this, 'do_shortcode' ) );
		add_shortcode( 'quotecoll', array( $this, 'do_shortcode' ) ); // just in case, somebody misspells the shortcode
		add_filter( 'the_content', array( $this, 'filter_content' ), 7 ); // Backwards compatibility
		add_filter( 'the_excerpt', array( $this, 'filter_content' ), 7 ); // Backwards compatibility
	}

	public function do_shortcode( $atts = array() ) {
		$atts             = shortcode_atts(
			array(
				'limit'              => 0,
				'id'                 => 0,
				'quote_id'           => 0,
				'daily'              => false,
				'todays_quote'       => false,
				'author'             => '',
				'source'             => '',
				'tags'               => '',
				'orderby'            => 'quote_id',
				'order'              => 'ASC',
				'paging'             => false,
				'limit_per_page'     => 10,
				'show_author'        => true,
				'show_source'        => true,
				'ajax_refresh'       => false,
				'random'             => true,
				'auto_refresh'       => true,
				'refresh_interval'   => 5,
				'char_limit'         => 500,
				'before'             => '<blockquote class="quotescollection-quote">',
				'after'              => '</blockquote>',
				'before_attribution' => '<footer class="attribution">&mdash;&nbsp;',
				'after_attribution'  => '</footer>',
			),
			$atts
		);
		$limit            = $atts['limit'] ?? 0;
		$id               = $atts['id'] ?? 0;
		$quote_id         = $atts['quote_id'] ?? 0;
		$author           = $atts['author'] ?? 0;
		$source           = $atts['source'] ?? 0;
		$tags             = $atts['tags'] ?? 0;
		$orderby          = $atts['orderby'] ?? 0;
		$order            = $atts['order'] ?? 0;
		$paging           = $atts['paging'] ?? 0;
		$limit_per_page   = $atts['limit_per_page'] ?? 0;
		$ajax_refresh     = $atts['ajax_refresh'] ?? 0;
		$auto_refresh     = $atts['auto_refresh'] ?? 0;
		$refresh_interval = $atts['refresh_interval'] ?? 0;
		$daily            = in_array( $atts['daily'], array( true, 1, '1', 'true' ), true )
			|| in_array( $atts['todays_quote'], array( true, 1, '1', 'true' ), true );

		if ( ! $daily && $ajax_refresh && $ajax_refresh !== 'false' ) {
				$atts['echo'] = 0;
			if (
					( $auto_refresh == 1 || $auto_refresh === true || $auto_refresh == 'true' )
					&& is_numeric( $refresh_interval )
				) {
				$atts['auto_refresh'] = $refresh_interval;
			} elseif ( $auto_refresh === false || $auto_refresh == 'false' ) {
				$atts['auto_refresh'] = 0;
			}
				return quotescollection_quote( $atts );
		}

		$db = new Quotes_Collection_DB();

		// Initialize the variable that holds args to be passed to the DB function to get the quotes
		// And set 'public' argument to 'yes', because we don't want to display private quotes in public
		$db_args = array( 'public' => 'yes' );

		// 'quote_id' is also a valid shortcode parameter synonymous to 'id'
		if ( ! empty( $quote_id ) && is_numeric( $quote_id ) ) {
			$id = $quote_id;
		}

		// If an id is specified, no need to process other attributes. Simply get the quote and return.
		if ( $id && is_numeric( $id ) ) {
			$db_args['quote_id'] = $id;
			if ( $quote = Quotes_Collection_Quote::with_condition( $db_args ) ) {
				return $quote->output_format( $atts );
			} else {
				return '';
			}
		}

		if ( $author ) {
			$db_args['author'] = $author;
		}
		if ( $source ) {
			$db_args['source'] = $source;
		}

		if ( $tags ) {
			$db_args['tags'] = $tags;
		}

		if ( $daily ) {
			$quote = $db->get_daily_quote( $db_args );
			return $quote ? $quote->output_format( $atts ) : '';
		}

		switch ( $orderby ) {
			case 'quote_id':
			case 'author':
			case 'source':
			case 'time_added':
			case 'random':
				$db_args['orderby'] = $orderby;
				break;
			case 'date_added':
				$db_args['orderby'] = 'time_added';
				break;
			case 'rand':
				$db_args['orderby'] = 'random';
				break;
			default:
				$db_args['orderby'] = 'quote_id';
		}

		if ( $order == 'DESC' || $order == 'desc' || $order == 'Desc' ) {
			$db_args['order'] = 'DESC';
		} else {
			$db_args['order'] = 'ASC';
		}

		$page_nav = '';

		if ( true === $paging || 'true' === $paging || 1 === $paging || '1' === $paging ) {
			$limit_per_page = max( 1, (int) $limit_per_page );

			$num_quotes = $db->count( $db_args );

			$total_pages = max( 1, (int) ceil( $num_quotes / $limit_per_page ) );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only pagination.
			$page = isset( $_GET['quotes_page'] ) && is_scalar( $_GET['quotes_page'] ) ? max( 1, absint( $_GET['quotes_page'] ) ) : 1;

			if ( $page > $total_pages ) {
				$page = $total_pages;
			}

			if ( $page_nav = $this->pagenav( $total_pages, $page, 0, 'quotes_page' ) ) {
				$page_nav = '<div class="quotescollection_pagenav">' . $page_nav . '</div>';
			}

			$start = ( $page - 1 ) * $limit_per_page;

			$db_args['num_quotes'] = $limit_per_page;
			$db_args['start']      = $start;

		} elseif ( $limit && is_numeric( $limit ) ) {
			$db_args['num_quotes'] = $limit;
		}

		if ( $quotes_data = $db->get_quotes( $db_args ) ) {
			return $page_nav . $this->output_format( $quotes_data, $atts ) . $page_nav;
		} else {
			return '';
		}
	}

	private function output_format( $quotes = array(), $options = array() ) {

		$display = '';

		foreach ( $quotes as $quote ) {
			$display .= $quote->output_format( $options );
		}

		return apply_filters( 'quotescollection_shortcode_output_format', $display );
	}

	private function pagenav( $total, $current = 1, $format = 0, $paged = 'paged', $url = '' ) {
		if ( $total == 1 && $current == 1 ) {
			return '';
		}

		if ( ! $url ) {
			$url = remove_query_arg( $paged );
		}
		$a = str_contains( $url, '?' ) ? '&' : '?';

		if ( ! $format || $format > 2 || $format < 0 || ! is_numeric( $format ) ) {
			if ( $total <= 8 ) {
				$format = 1;
			} else {
				$format = 2;
			}
		}

		if ( $current > $total ) {
			$current = $total;
		}
			$pagenav = '';

		if ( $format == 2 ) {
			$first_disabled = $prev_disabled = $next_disabled = $last_disabled = '';
			if ( $current == 1 ) {
				$first_disabled = $prev_disabled = ' disabled';
			}
			if ( $current == $total ) {
				$next_disabled = $last_disabled = ' disabled';
			}

			$pagenav .= '<a class="first-page' . $first_disabled . '" title="' . __( 'Go to the first page', 'quotes-collection' ) . '" href="' . esc_url( $url ) . '">&laquo;</a>&nbsp;&nbsp;';

			$pagenav .= '<a class="prev-page' . $prev_disabled . '" title="' . __( 'Go to the previous page', 'quotes-collection' ) . '" href="' . esc_url( $url . $a . $paged . '=' . ( $current - 1 ) ) . '">&#139;</a>&nbsp;&nbsp;';

			$pagenav .= '<span class="paging-input">' . $current . ' of <span class="total-pages">' . $total . '</span></span>';

			$pagenav .= '&nbsp;&nbsp;<a class="next-page' . $next_disabled . '" title="' . __( 'Go to the next page', 'quotes-collection' ) . '" href="' . esc_url( $url . $a . $paged . '=' . ( $current + 1 ) ) . '">&#155;</a>';

			$pagenav .= '&nbsp;&nbsp;<a class="last-page' . $last_disabled . '" title="' . __( 'Go to the last page', 'quotes-collection' ) . '" href="' . esc_url( $url . $a . $paged . '=' . $total ) . '">&raquo;</a>';

		} else {
			$pagenav = __( 'Goto page:', 'quotes-collection' );
			for ( $i = 1; $i <= $total; $i++ ) {
				if ( $i == $current ) {
					$pagenav .= "&nbsp;<strong>{$i}</strong>";
				} elseif ( $i == 1 ) {
					$pagenav .= '&nbsp;<a href="' . esc_url( $url ) . "\">{$i}</a>";
				} else {
					$pagenav .= '&nbsp;<a href="' . esc_url( $url . $a . $paged . '=' . $i ) . "\">{$i}</a>";
				}
			}
		}
		return $pagenav;
	}


	public function filter_content( $text ) {
		$start = strpos( $text, '[quote|id=' );
		if ( $start !== false ) {
			$text = preg_replace_callback( '/\[quote\|id=(\d+)\]/i', array( $this, 'displayquote' ), $text );
		}
		$start = strpos( $text, '[quote|random]' );
		if ( $start !== false ) {
			$text = preg_replace_callback( '/\[quote\|random\]/i', array( $this, 'displayquote' ), $text );
		}
		$start = strpos( $text, '[quote|all]' );
		if ( $start !== false ) {
			$text = preg_replace_callback( '/\[quote\|all\]/i', array( $this, 'do_shortcode' ), $text );
		}
		$start = strpos( $text, '[quote|author=' );
		if ( $start !== false ) {
			$text = preg_replace_callback( '/\[quote\|author=(.{1,})?\]/i', array( $this, 'displayquotes_author' ), $text );
		}
		$start = strpos( $text, '[quote|source=' );
		if ( $start !== false ) {
			$text = preg_replace_callback( '/\[quote\|source=(.{1,})?\]/i', array( $this, 'displayquotes_source' ), $text );
		}
		$start = strpos( $text, '[quote|tags=' );
		if ( $start !== false ) {
			$text = preg_replace_callback( '/\[quote\|tags=(.{1,})?\]/i', array( $this, 'displayquotes_tags' ), $text );
		}
		return $text;
	}

	private function displayquote( $matches ) {
		if ( ! isset( $matches[1] ) || ( isset( $matches[1] ) && ! $matches[1] ) || $matches[0] == '[quote|random]' ) {
			$atts = array(
				'orderby' => 'random',
				'limit'   => 1,
			);
		} else {
			$atts = array( 'id' => $matches[1] );
		}

		return $this->do_shortcode( $atts );
	}


	private function displayquotes_author( $matches ) {
		return $this->do_shortcode( array( 'author' => $matches[1] ) );
	}


	private function displayquotes_source( $matches ) {
		return $this->do_shortcode( array( 'source' => $matches[1] ) );
	}

	private function displayquotes_tags( $matches ) {
		return $this->do_shortcode( array( 'tags' => $matches[1] ) );
	}
}
