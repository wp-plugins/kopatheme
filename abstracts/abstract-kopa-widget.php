<?php
/**
 * Abstract Widget Class
 *
 * @author 		Kopatheme
 * @category 	Widgets
 * @package 	KopaFramework/Abstracts
 * @since       1.0.0
 * @extends 	WP_Widget
 * @folked      WC_Widget from Woocommerce
 */
abstract class Kopa_Widget extends WP_Widget {

	/**
	 * @access public
	 * @var string widget properties
	 */
	public $widget_cssclass;
	public $widget_description;
	public $widget_id;
	public $widget_name;
	public $widget_width;
	public $widget_height;

	/**
	 * @access public
	 * @var array form field arguments
	 */
	public $settings;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => $this->widget_cssclass,
			'description' => $this->widget_description
		);

		$control_ops = array(
			'width'  => $this->widget_width,
			'height' => $this->widget_height,
		);

		$this->WP_Widget( $this->widget_id, $this->widget_name, $widget_ops, $control_ops );

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	/**
	 * get_cached_widget function.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	function get_cached_widget( $args ) {
		$cache = wp_cache_get( $this->widget_id, 'widget' );

		if ( ! is_array( $cache ) )
			$cache = array();

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return true;
		}

		return false;
	}

	/**
	 * Cache the widget
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function cache_widget( $args, $content ) {
		$cache[ $args['widget_id'] ] = $content;

		wp_cache_set( $this->widget_id, $cache, 'widget' );
	}

	/**
	 * Flush the cache
	 * @return [type]
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function flush_widget_cache() {
		wp_cache_delete( $this->widget_id, 'widget' );
	}

	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 *
	 * @since 1.0.0
	 * @access public
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		if ( ! $this->settings )
			return $instance;

		foreach ( $this->settings as $key => $setting ) {
			if ( isset( $new_instance[ $key ] ) ) {
				if ( is_array( $new_instance[ $key ] ) ) {
					$instance[ $key ] = array_map( 'sanitize_text_field', (array) $new_instance[ $key ] );
				} elseif ( 'textarea' === $setting['type'] ) {
					// $instance[ $key ] = wp_kses_post( trim( $new_instance[ $key ] ) );

					/**
					 * @see update() of WP_Widget_Text
					 */
					if ( current_user_can('unfiltered_html') ) {
						$instance[ $key ] =  $new_instance[ $key ];
					} else {
						$instance[ $key ] = stripslashes( wp_filter_post_kses( addslashes($new_instance[ $key ]) ) ); // wp_filter_post_kses() expects slashed
					}
				} else {
					$instance[ $key ] = sanitize_text_field( $new_instance[ $key ] );
				}
			} elseif ( 'checkbox' === $setting['type'] ) {
				$instance[ $key ] = 0;
			} elseif ( 'multiselect' === $setting['type'] ) {
				$instance[ $key ] = array();
			}
		}

		$this->flush_widget_cache();

		return $instance;
	}

	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @param array $instance
	 * @return void
	 *
	 * @since 1.0.0
	 * @access public
	 */
	function form( $instance ) {

		if ( ! $this->settings )
			return;

		foreach ( $this->settings as $key => $setting ) {

			// sanitize setting arguments
			$setting = wp_parse_args( $setting, array(
				// common
				'type'    => '',
				'std'     => '',
				'label'   => '',
				// number
				'step'    => '',
				'min'     => '',
				'max'     => '',
				// select
				'options' => '',
				'size'    => '',
				// textarea
				'rows'    => '',
			) );

			$value   = isset( $instance[ $key ] ) ? $instance[ $key ] : $setting['std'];

			switch ( $setting['type'] ) {
				case "text" :
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $setting['label'] ); ?></label>
						<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" />
					</p>
					<?php
				break;
				case "number" :
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $setting['label'] ); ?></label>
						<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="number" step="<?php echo esc_attr( $setting['step'] ); ?>" min="<?php echo esc_attr( $setting['min'] ); ?>" max="<?php echo esc_attr( $setting['max'] ); ?>" value="<?php echo esc_attr( $value ); ?>" />
					</p>
					<?php
				break;
				case "select" :
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $setting['label'] ); ?></label>
						<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo $this->get_field_name( $key ); ?>">
							<?php foreach ( $setting['options'] as $option_key => $option_value ) : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $value ); ?>><?php echo esc_html( $option_value ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<?php
				break;
				case "multiselect" :
					?>
					<p>
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $setting['label'] ); ?></label>
						<select class="widefat" size="<?php echo esc_attr( $setting['size'] ); ?>" id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>[]" multiple="multiple">
							<?php foreach ( $setting['options'] as $option_key => $option_value ) : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( $option_key, (array) $value ), true ); ?>><?php echo esc_html( $option_value ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<?php
				break;
				case "checkbox" :
					?>
					<p>
						<input id="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" type="checkbox" value="1" <?php checked( $value, 1 ); ?> />
						<label for="<?php echo esc_attr( $this->get_field_id( $key ) ); ?>"><?php echo esc_html( $setting['label'] ); ?></label>
					</p>
					<?php
				break;
				case "textarea" :
					?>
					<p>
						<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo esc_html( $setting['label'] ); ?></label>
						<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( $key ) ) ?>" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" rows="<?php echo esc_attr( $setting['rows'] ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
					</p>
					<?php
				break;
			}
		}
	}
}