<?php
/**
 * Modified for ulfben's fork on 2026-10-20; see the changelog in readme.txt.
 *
 * Quotes Collection Widget
 *
 * @package Quotes Collection
 * @since 2.0
 */

class Quotes_Collection_Widget extends WP_Widget {

	/**
	 * Constructor. Sets up the widget name, description, etc.
	 */
	public function __construct() {
		parent::__construct(
			'quotescollection', // Base ID
			_x( 'Random Quote', 'widget name', 'quotes-collection' ), // Name
			array( 'description' => _x( 'Random quote from your quotes collection', 'widget description', 'quotes-collection' ) ) // Args
		);
	}

	/**
	 * Register the widget. Should be hooked to 'widgets_init'.
	 */
	public static function register() {
		register_widget( 'Quotes_Collection_Widget' );
	}

	/**
	 * Front end output
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$args     = wp_parse_args(
			$args,
			array(
				'before_widget' => '',
				'after_widget'  => '',
				'before_title'  => '',
				'after_title'   => '',
			)
		);
		$instance = wp_parse_args( $instance, $this->default_options() );
		global $quotescollection;
		// Ensure that 'widget_id' is defined in $args
		$widget_id = isset( $args['widget_id'] ) ? $args['widget_id'] : 'default_widget_id';
		if ( $instance ) {
			$options['title']        = isset( $instance['title'] ) ? $instance['title'] : esc_html__( 'Random Quote', 'quotes-collection' );
			$options['show_author']  = isset( $instance['show_author'] ) ? $instance['show_author'] : 1;
			$options['show_source']  = isset( $instance['show_source'] ) ? $instance['show_source'] : 1;
			$options['ajax_refresh'] = isset( $instance['ajax_refresh'] ) ? $instance['ajax_refresh'] : 1;
			$options['auto_refresh'] = isset( $instance['auto_refresh'] ) ? $instance['auto_refresh'] : 0;
			$options['random']       = isset( $instance['random_refresh'] ) ? $instance['random_refresh'] : 1;
			$options['daily']        = $instance['daily'];
			if ( $options['auto_refresh'] ) {
				$options['auto_refresh'] = isset( $instance['refresh_interval'] ) ? $instance['refresh_interval'] : 5;
			}
			$options['char_limit'] = $instance['char_limit'];
			$options['tags']       = $instance['tags'];
		} else {  // Default options
			$options           = $this->default_options();
			$options['random'] = $options['random_refresh'];
		}

		$options['order']    = 'DESC';
		$options['echo']     = 0;
		$options['instance'] = 'w_' . str_replace( '-', '_', $widget_id );

		if ( $quote = $quotescollection->quote( $options ) ) {

			echo wp_kses_post( $args['before_widget'] );
			if ( $options['title'] ) {
				echo wp_kses_post( $args['before_title'] ) . esc_html( $options['title'] ) . wp_kses_post( $args['after_title'] ) . "\n";
			}
			echo wp_kses_post( $quote );
			echo wp_kses_post( $args['after_widget'] );
		}
	}


	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->default_options() );

		if ( $instance ) {
			$options['title']            = isset( $instance['title'] ) ? $instance['title'] : esc_html__( 'Random Quote', 'quotes-collection' );
			$options['show_author']      = isset( $instance['show_author'] ) ? $instance['show_author'] : 1;
			$options['show_source']      = isset( $instance['show_source'] ) ? $instance['show_source'] : 1;
			$options['ajax_refresh']     = isset( $instance['ajax_refresh'] ) ? $instance['ajax_refresh'] : 1;
			$options['auto_refresh']     = isset( $instance['auto_refresh'] ) ? $instance['auto_refresh'] : 0;
			$options['random_refresh']   = isset( $instance['random_refresh'] ) ? $instance['random_refresh'] : 1;
			$options['refresh_interval'] = isset( $instance['refresh_interval'] ) ? $instance['refresh_interval'] : 5;
			if ( isset( $instance['char_limit'] ) && is_numeric( $instance['char_limit'] ) ) {
				$options['char_limit'] = $instance['char_limit'];
			} else {
				$options['char_limit'] = esc_html__( 'none', 'quotes-collection' );
			}
			$options['tags'] = $instance['tags'];
		} else {
			$options = $this->default_options();
		}

		$show_author_checked = $show_source_checked = $ajax_refresh_checked = $auto_refresh_checked = $random_refresh_checked = '';
		$int_select          = array(
			'5'  => '',
			'10' => '',
			'15' => '',
			'20' => '',
			'30' => '',
			'60' => '',
		);
		if ( $options['show_author'] ) {
			$show_author_checked = ' checked="checked"';
		}
		if ( $options['show_source'] ) {
			$show_source_checked = ' checked="checked"';
		}
		if ( $options['ajax_refresh'] ) {
			$ajax_refresh_checked = ' checked="checked"';
		}
		if ( $options['auto_refresh'] ) {
			$auto_refresh_checked = ' checked="checked"';
		}
		if ( $options['random_refresh'] ) {
			$random_refresh_checked = ' checked="checked"';
		}
		$int_select[ $options['refresh_interval'] ] = ' selected="selected"';

		echo '<p>';
		echo '<label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' . esc_html__( 'Title', 'quotes-collection' ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $options['title'] ) . '">';
		echo '</p>';

		echo '<p><input type="checkbox" id="' . esc_attr( $this->get_field_id( 'daily' ) ) . '" name="' . esc_attr( $this->get_field_name( 'daily' ) ) . '" ' . checked( $instance['daily'], 1, false ) . ' />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'daily' ) ) . '">' . esc_html__( 'Daily quote', 'quotes-collection' ) . '</label>';
		echo '<br/><small>' . esc_html__( 'One quote per day in the site timezone. Overrides refresh settings.', 'quotes-collection' ) . '</small></p>';

		echo '<p>';
		echo '<input type="checkbox" id="' . esc_attr( $this->get_field_id( 'show_author' ) ) . '" name="' . esc_attr( $this->get_field_name( 'show_author' ) ) . '"' . esc_attr( $show_author_checked ) . ' />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'show_author' ) ) . '">' . esc_html__( 'Show author?', 'quotes-collection' ) . '</label>';
		echo '</p>';

		echo '<p>';
		echo '<input type="checkbox" id="' . esc_attr( $this->get_field_id( 'show_source' ) ) . '" name="' . esc_attr( $this->get_field_name( 'show_source' ) ) . '"' . esc_attr( $show_source_checked ) . ' />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'show_source' ) ) . '">' . esc_html__( 'Show source?', 'quotes-collection' ) . '</label>';
		echo '</p>';

		echo '<p>';
		echo '<input type="checkbox" id="' . esc_attr( $this->get_field_id( 'ajax_refresh' ) ) . '" name="' . esc_attr( $this->get_field_name( 'ajax_refresh' ) ) . '"' . esc_attr( $ajax_refresh_checked ) . ' />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'ajax_refresh' ) ) . '">' . esc_html__( 'Refresh feature', 'quotes-collection' ) . '</label>';
		echo '</p>';

		echo "<p style=\"text-align:left;\"><small><a id=\"quotescollection-adv-key\" style=\"cursor:pointer;\" onclick=\"jQuery('div#quotescollection-adv-opts').slideToggle();\">" . esc_html__( 'Advanced options', 'quotes-collection' ) . ' &raquo;</a></small></p>';
		echo '<div id="quotescollection-adv-opts" style="display:none">';

		echo '<p>';
		echo '<input type="checkbox" id="' . esc_attr( $this->get_field_id( 'random_refresh' ) ) . '" name="' . esc_attr( $this->get_field_name( 'random_refresh' ) ) . '"' . esc_attr( $random_refresh_checked ) . ' />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'random_refresh' ) ) . '">' . esc_html__( 'Random refresh', 'quotes-collection' ) . '</label>';
		echo '<br/><span class="setting-description"><small>' . esc_html__( 'Unchecking this will rotate quotes in the order added, latest first.', 'quotes-collection' ) . '</small></span></p>';
		echo '</p>';

		echo '<p>';
		echo '<input type="checkbox" id="' . esc_attr( $this->get_field_id( 'auto_refresh' ) ) . '" name="' . esc_attr( $this->get_field_name( 'auto_refresh' ) ) . '"' . esc_attr( $auto_refresh_checked ) . ' />';
		echo '<label for="' . esc_attr( $this->get_field_id( 'auto_refresh' ) ) . '">' . esc_html__( 'Auto refresh', 'quotes-collection' ) . '</label>';
		echo ' <label for="' . esc_attr( $this->get_field_id( 'refresh_interval' ) ) . '"">';
		printf(
			/* translators: %s: Input field that accepts the refresh interval time in seconds, in numeric form */
			esc_html__( 'every %s sec', 'quotes-collection' ),
			'<input type="number" id="' . esc_attr( $this->get_field_id( 'refresh_interval' ) ) . '" name="' . esc_attr( $this->get_field_name( 'refresh_interval' ) ) . '" value="' . esc_attr( $options['refresh_interval'] ) . '" min="3" max="60" step="1" style="width:3em;" />'
		);
		echo '</label>';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . esc_attr( $this->get_field_id( 'tags' ) ) . '">' . esc_html__( 'Tags filter', 'quotes-collection' ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'tags' ) ) . '" name="' . esc_attr( $this->get_field_name( 'tags' ) ) . '" type="text" value="' . esc_attr( $options['tags'] ) . '">';
		echo '<br/><span class="setting-description"><small>' . esc_html__( 'Comma separated', 'quotes-collection' ) . '</small>';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . esc_attr( $this->get_field_id( 'char_limit' ) ) . '">' . esc_html__( 'Character limit', 'quotes-collection' ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'char_limit' ) ) . '" name="' . esc_attr( $this->get_field_name( 'char_limit' ) ) . '" type="text" value="' . esc_attr( $options['char_limit'] ) . '">';
		echo '</p>';

		echo '</div> <!-- #quotescollection-adv-opts -->';
		return null;
	}


	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$new_instance = wp_parse_args(
			$new_instance,
			array(
				'refresh_interval' => 5,
				'tags'             => '',
				'char_limit'       => '',
			)
		);
		$old_instance = wp_parse_args( $old_instance, $this->default_options() );
		$instance     = array();

		$instance['title']        = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( stripslashes( $new_instance['title'] ) ) : '';
		$instance['show_author']  = ( isset( $new_instance['show_author'] ) && $new_instance['show_author'] ) ? 1 : 0;
		$instance['show_source']  = ( isset( $new_instance['show_source'] ) && $new_instance['show_source'] ) ? 1 : 0;
		$instance['ajax_refresh'] = ( isset( $new_instance['ajax_refresh'] ) && $new_instance['ajax_refresh'] ) ? 1 : 0;
		$instance['auto_refresh'] = ( isset( $new_instance['auto_refresh'] ) && $new_instance['auto_refresh'] ) ? 1 : 0;
		$instance['daily']        = ! empty( $new_instance['daily'] ) ? 1 : 0;
		if ( is_numeric( $new_instance['refresh_interval'] ) ) {
			$instance['refresh_interval'] = max( 3, min( 60, (int) $new_instance['refresh_interval'] ) );
		} else {
			$instance['refresh_interval'] = $old_instance['refresh_interval'];
		}
		$instance['random_refresh'] = ( isset( $new_instance['random_refresh'] ) && $new_instance['random_refresh'] ) ? 1 : 0;
		$instance['tags']           = wp_strip_all_tags( stripslashes( $new_instance['tags'] ) );
		$instance['char_limit']     = wp_strip_all_tags( stripslashes( $new_instance['char_limit'] ) );
		if ( ! $instance['char_limit'] || ! is_numeric( $instance['char_limit'] ) ) {
			$instance['char_limit'] = esc_html__( 'none', 'quotes-collection' );
		}

		return $instance;
	}

	/**
	 * The default widget options
	 *
	 * @return array The default options
	 */
	private function default_options() {
		return array(
			'title'            => esc_html__( 'Random Quote', 'quotes-collection' ),
			'show_author'      => 1,
			'show_source'      => 0,
			'ajax_refresh'     => 1,
			'auto_refresh'     => 0,
			'daily'            => 0,
			'random_refresh'   => 1,
			'refresh_interval' => 5,
			'tags'             => '',
			'char_limit'       => 500,
		);
	}
}
