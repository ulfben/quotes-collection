<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * The main plugin class
 *
 * @package Quotes Collection
 * @since 2.0
 */

class Quotes_Collection {

	/** Plugin version **/
	const PLUGIN_VERSION = '2.6.0';

	public $refresh_link_text;
	public $auto_refresh_max;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts_and_styles' ) );
		add_action( 'wp_ajax_quotescollection', array( $this, 'ajax_response' ) );
		add_action( 'wp_ajax_nopriv_quotescollection', array( $this, 'ajax_response' ) );
		add_action( 'init', array( $this, 'initialize_options' ), 5 );
		add_action( 'wp_initialize_site', array( $this, 'initialize_site' ), 200 );
	}

	/** Install storage for sites created after network activation. */
	public function initialize_site( $site ) {
		$active = get_network_option( $site->network_id, 'active_sitewide_plugins', array() );
		if ( ! isset( $active[ plugin_basename( dirname( __DIR__ ) . '/quotes-collection.php' ) ] ) ) {
			return;
		}
		switch_to_blog( $site->blog_id );
		Quotes_Collection_DB::install_db();
		restore_current_blog();
	}

	public static function activate( $network_wide = false ) {
		// WordPress verifies permissions and the appropriate nonce before this hook.
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( $site_id );
				Quotes_Collection_DB::install_db();
				restore_current_blog();
			}
		} else {
			Quotes_Collection_DB::install_db(); }
	}


	/** Instantiate the plugin classes. Hooked to 'plugins_loaded' action **/
	public static function load() {
		global $quotescollection;
		global $quotescollection_db;
		global $quotescollection_admin;
		global $quotescollection_shortcode;

		if ( null === $quotescollection ) {
			$quotescollection = new self();
		}

		if ( null === $quotescollection_db ) {
			$quotescollection_db = new Quotes_Collection_DB();
		}

		if ( is_admin() && null === $quotescollection_admin ) {
			$quotescollection_admin = new Quotes_Collection_Admin();
		}

		if ( null === $quotescollection_shortcode ) {
			$quotescollection_shortcode = new Quotes_Collection_Shortcode();
		}
	}

	/** Load scripts and styles required at the front end **/
	public function load_scripts_and_styles() {

		// Enqueue scripts required for the ajax refresh functionality
		wp_enqueue_script(
			'quotescollection', // handle
			quotescollection_url( 'js/quotes-collection.js' ), // source
			array( 'jquery' ), // dependencies
			self::PLUGIN_VERSION, // version
			false // load in header, because quotecollectionTimer() has to be loaded before it's called
		);
		wp_localize_script(
			'quotescollection',
			'quotescollectionAjax',
			array(
				// URL to wp-admin/admin-ajax.php to process the request
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),

				// generate a nonce with a unique ID "myajax-post-comment-nonce"
				// so that you can check it later when an AJAX request is sent
				'nonce'            => wp_create_nonce( 'quotescollection' ),

				'nextQuote'        => $this->refresh_link_text,
				'loading'          => __( 'Loading...', 'quotes-collection' ),
				'error'            => __( 'Error getting quote', 'quotes-collection' ),
				'autoRefreshMax'   => $this->auto_refresh_max,
				'autoRefreshCount' => 0,
			)
		);

		// Enqueue styles for the front end
		if ( ! is_admin() ) {
			wp_register_style(
				'quotescollection',
				quotescollection_url( 'css/quotes-collection.css' ),
				array(),
				self::PLUGIN_VERSION
			);
			wp_enqueue_style( 'quotescollection' );
		}
	}

	/** Initialize the plugin options **/
	public function initialize_options() {
		$this->refresh_link_text = __( 'Next quote &raquo;', 'quotes-collection' );
		$this->auto_refresh_max  = 20;
		if ( $options = get_option( 'quotescollection' ) ) {
			if ( isset( $options['refresh_link_text'] ) && $options['refresh_link_text'] ) {
				$this->refresh_link_text = $options['refresh_link_text'];
			}
			if ( isset( $options['auto_refresh_max'] ) && $options['auto_refresh_max'] ) {
				$this->auto_refresh_max = $options['auto_refresh_max'];
			}
		} else {
			$options = array(
				'refresh_link_text' => $this->refresh_link_text,
				'auto_refresh_max'  => $this->auto_refresh_max,
			);

			add_option( 'quotescollection', $options );
		}
	}


	/** Ajax response **/
	public function ajax_response() {
		check_ajax_referer( 'quotescollection' );

		$char_limit = isset( $_POST['char_limit'] ) && is_scalar( $_POST['char_limit'] ) ? absint( $_POST['char_limit'] ) : 0;
		$tags       = isset( $_POST['tags'] ) && is_string( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '';
		$orderby    = isset( $_POST['orderby'] ) && 'random' === $_POST['orderby'] ? 'random' : 'quote_id';
		$current    = isset( $_POST['current'] ) && is_scalar( $_POST['current'] ) ? absint( $_POST['current'] ) : 0;
		$order      = 'random' === $orderby ? '' : 'DESC';
		$exclude    = 'random' === $orderby && $current ? $current : '';
		$splice     = 'random' !== $orderby && $current ? $current : '';

		$args = array(
			'char_limit' => $char_limit,
			'tags'       => $tags,
			'orderby'    => $orderby,
			'order'      => $order,
			'splice'     => $splice,
			'exclude'    => $exclude,
			'public'     => 'yes',
		);

		global $quotescollection_db;

		if ( false === ( $quote_data = $quotescollection_db->get_quote( $args ) ) && $splice ) {
			$args['splice'] = '';
			$quote_data     = $quotescollection_db->get_quote( $args );
		}

		if ( $quote_data ) {
			$quote_data->prepare_data(); // Format the data for output before sending
			wp_send_json( $quote_data );
		} else {
			wp_send_json( null );
		}
	}


	/**
	 * Function to display a random (or not so random) quote with (or without)
	 * ajax refresh functionality. Used by the 'quotescollection_quote' template
	 * function and the 'Random Quote' widget.
	 */
	public function quote( $args = array() ) {

		global $quotescollection_db;

		$args_default = array(
			'instance'           => '',
			'show_author'        => 1,
			'show_source'        => 0,
			'ajax_refresh'       => 1,
			'random'             => 1,
			'auto_refresh'       => 0,
			'daily'              => false,
			'todays_quote'       => false,
			'tags'               => '',
			'char_limit'         => 500,
			'before'             => '',
			'after'              => '',
			'before_attribution' => '<div class="attribution">&mdash;&nbsp;',
			'after_attribution'  => '</div>',
			'echo'               => 1,
		);

		// Merge with default values
		$args  = array_merge( $args_default, $args );
		$daily = in_array( $args['daily'], array( true, 1, '1', 'true' ), true )
			|| in_array( $args['todays_quote'], array( true, 1, '1', 'true' ), true );

		$instance     = is_string( $args['instance'] ) ? sanitize_html_class( $args['instance'] ) : '';
		$show_author  = ( false == $args['show_author'] || 'false' === $args['show_author'] ) ? 0 : 1;
		$show_source  = ( true == $args['show_source'] && 'false' !== $args['show_source'] ) ? 1 : 0;
		$ajax_refresh = ( false == $args['ajax_refresh'] || 'false' === $args['ajax_refresh'] ) ? 0 : 1;
		$auto_refresh = 0;
		if ( $args['auto_refresh'] ) {
			if ( is_numeric( $args['auto_refresh'] ) ) {
				$auto_refresh = $args['auto_refresh'];
			} elseif ( true === $args['auto_refresh'] || 'true' === $args['auto_refresh'] ) {
				$auto_refresh = 5;
			}
		}
		if ( $daily ) {
			$ajax_refresh = 0;
			$auto_refresh = 0;
		}

		$random = ( false == $args['random'] || 'false' === $args['random'] ) ? 0 : 1;

		// Tags can only be passed as a string, comma separated
		$tags = ( is_string( $args['tags'] ) ) ? $args['tags'] : '';

		// Only a numeral can be passed as char_limit
		$char_limit = ( is_numeric( $args['char_limit'] ) ) ? $args['char_limit'] : 500;

		// By default, we fetch a random quote
		$orderby = 'random';
		if ( ! $random ) {
			$orderby = 'quote_id';
		}

		// Frame the conditions to fetch the quote
		$condition = array(
			'orderby'    => $orderby,
			'order'      => 'DESC',
			'tags'       => $tags,
			'char_limit' => $char_limit,
			'public'     => 'yes',
		);
		if ( $daily ) {
			foreach ( array( 'author', 'source' ) as $filter ) {
				if ( isset( $args[ $filter ] ) && is_string( $args[ $filter ] ) && '' !== $args[ $filter ] ) {
					$condition[ $filter ] = $args[ $filter ];
				}
			}
		}

		if ( ! ( $count = $quotescollection_db->count( $condition ) ) ) {
			return '';
		}

		$dynamic_fetch = 0;
		if ( ! $daily && ! ( isset( $args['editor_render'] ) && $args['editor_render'] )
			&& 'random' == $orderby
			&& ( $options = get_option( 'quotescollection' ) )
			&& isset( $options['dynamic_fetch'] )
			&& $options['dynamic_fetch'] == 'on'
			&& $count > 1
			) {
			$dynamic_fetch = 1;
		}

		$before             = ( is_string( $args['before'] ) ) ? stripslashes( $args['before'] ) : '';
		$after              = ( is_string( $args['after'] ) ) ? stripslashes( $args['after'] ) : '';
		$before_attribution = ( is_string( $args['before_attribution'] ) ) ? stripslashes( $args['before_attribution'] ) : '';
		$after_attribution  = ( is_string( $args['after_attribution'] ) ) ? stripslashes( $args['after_attribution'] ) : '';
		$display            = '';

		// And fetch the quote, only if dynamic fetch is off
		if ( ! $dynamic_fetch ) {
			$quote = $daily ? $quotescollection_db->get_daily_quote( $condition ) : Quotes_Collection_Quote::with_condition( $condition );
			if ( $quote ) {
				$curr_quote_id = $quote->quote_id;
				$display       = $quote->output_format(
					array(
						'show_author'        => $show_author,
						'show_source'        => $show_source,
						'before'             => $before,
						'after'              => $after,
						'before_attribution' => $before_attribution,
						'after_attribution'  => $after_attribution,
					)
				);
				if ( ! $display ) {
					return '';
				}
			} else {
				return '';
			}
		} else {
			$curr_quote_id = 0;
		}
		if ( $count > 1 && $instance && ( $dynamic_fetch || $ajax_refresh ) ) {
			$settings = array(
				'instanceID'        => $instance,
				'currQuoteID'       => (int) $curr_quote_id,
				'showAuthor'        => $show_author,
				'showSource'        => $show_source,
				'tags'              => $tags,
				'charLimit'         => (int) $char_limit,
				'orderBy'           => $orderby,
				'ajaxRefresh'       => $ajax_refresh,
				'autoRefresh'       => $auto_refresh ? max( 3, (int) $auto_refresh ) : 0,
				'dynamicFetch'      => $dynamic_fetch,
				'before'            => wp_kses_post( $before ),
				'after'             => wp_kses_post( $after ),
				'beforeAttribution' => wp_kses_post( $before_attribution ),
				'afterAttribution'  => wp_kses_post( $after_attribution ),
			);
			$data     = ' data-quotescollection="' . esc_attr( wp_json_encode( $settings ) ) . '"';
		} else {
			$data = ''; }
		$display = '<div class="quotescollection-quote-wrapper" id="' . esc_attr( $instance ) . '"' . $data . '>' . $display . '</div>';

		// If 'echo' argument is false, we return the display.
		if ( isset( $args['echo'] ) && ! $args['echo'] ) {
			return $display;
		} else { // Else we simply output the display
			echo wp_kses_post( $display );
		}

		return true;
	}
}
