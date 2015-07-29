<?php
/**
 * Kopa Framework Metabox
 *
 * This module allows you to define custom metabox for built-in or custom post types 
 *
 * @author 		Kopatheme
 * @category 	Metabox
 * @package 	KopaFramework
 * @since       1.0.5
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Kopa_Admin_Meta_Box' ) ) {

/**
 * Kopa_Admin_Meta_Box Class
 */
class Kopa_Admin_Meta_Box {

	/**
	 * @access private
	 * @var array meta boxes settings
	 */
	private $settings = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;

		add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );
	}

	/**
	 * Metabox scripts
	 * 
	 * @since 1.0.5
	 * @access public
	 */
	public function meta_box_scripts() {
		$screen = get_current_screen();
		$metabox = $this->settings;

		if ( in_array( $screen->id, (array) $metabox['pages'] ) ) {
			wp_enqueue_script( 'kopa_media_uploader' );
		}
	}

	/**
	 * Add metaboxes
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function add_meta_boxes() {
		$metabox = $this->settings;

		foreach ( (array) $metabox['pages'] as $page ) {
	    	add_meta_box( $metabox['id'], $metabox['title'], array( $this, 'output' ), $page, $metabox['context'], $metabox['priority'], $metabox['fields'] );
	    }
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type
	 *
	 * @param  int $post_id
	 * @param  object $post
	 * 
	 * @since 1.0.5
	 * @access public
	 */
	public function save_meta_boxes( $post_id, $post ) {
		$metabox = $this->settings;

		/* don't save if $_POST is empty */
		if ( empty( $_POST ) )
			return $post_id;

		/* don't save during autosave */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		/* verify nonce */
		if ( ! isset( $_POST[ $metabox['id'] . '_nonce'] ) || ! wp_verify_nonce( $_POST[ $metabox['id'] . '_nonce'], $metabox['id'] ) )
			return $post_id;

		/* check permissions */
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		// Options to update will be stored here
	    $update_options = array();

		foreach ( $metabox['fields'] as $value ) {
			if ( ! isset( $value['id'] ) ) {
				continue;
			}

			$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

			// Get the option name
	    	$option_value = null;

	    	if ( isset( $_POST[ $value['id'] ] ) ) {
    			$option_value = $_POST[ $value['id'] ];
    		}

    		// For a value to be submitted to database it must pass through a sanitization filter
	    	if ( has_filter( 'kopa_sanitize_option_' . $type ) ) {
	    		$option_value = apply_filters( 'kopa_sanitize_option_' . $type, $option_value, $value );
	    	}

	    	if ( ! is_null( $option_value ) ) {
				$update_options[ $value['id'] ] = $option_value;
			}
		}

		// Now save the options
	    foreach( $update_options as $name => $value ) {
	    	update_post_meta( $post_id, $name, $value );
	    }

	    return true;
	}

	/**
	 * Output meta box fields
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function output( $post, $args ) {
		$metabox = $this->settings;

		$option_wrap_start = '';
		$option_wrap_end = '';
		$output = '';

		$output .= '<div class="kopa-metabox-wrapper">';

		/* Use nonce for verification */
        $output .= '<input type="hidden" name="' . $metabox['id'] . '_nonce" value="' . wp_create_nonce( $metabox['id'] ) . '">';
        
        /* meta box description */
        if ( isset( $metabox['desc'] ) && ! empty( $metabox['desc'] ) ) {
        	$allowed_tags = array(
				'abbr'      => array( 'title' => true ),
				'acronym'   => array( 'title' => true ),
				'code'      => true,
				'em'        => true,
				'strong'    => true,
				'a'         => array(
					'href'  => true,
					'title' => true,
				),
			);
			$metabox['desc'] = wp_kses( $metabox['desc'], $allowed_tags );
        	$output .= '<p>' . $metabox['desc'] . '</p>';
        }

        $_loop_index = 0;

        foreach ( $metabox['fields'] as $value ) {
        	
        	if ( ! isset( $value['type'] ) ) continue;

        	$value = Kopa_Admin_Settings::sanitize_option_arguments( $value );

			$option_wrap_start = '<p>';
			if ( $value['title'] ) {
				$option_wrap_start .= '<strong>'.esc_html( $value['title'] ).'</strong>';
				$option_wrap_start .= '<br>';
			}
			if ( $value['desc'] ) {
				$option_wrap_start .= '<span>'.$value['desc'].'</span>';
				$option_wrap_start .= '<br>';
			}

			$option_wrap_end = '</p>';

			$option_wrap_start = apply_filters('kopa_admin_meta_box_wrap_start', $option_wrap_start, $value, $_loop_index);
			$option_wrap_end   = apply_filters('kopa_admin_meta_box_wrap_end', $option_wrap_end, $value, $_loop_index);			

			// get option value
			$option_value = get_post_meta( $post->ID, $value['id'] );

			if ( empty( $option_value ) ) {
				$option_value = $value['default'];
			} elseif ( isset( $option_value[0] ) ) {
				$option_value = $option_value[0];
			} else {
				$option_value = '';
			}

        	switch( $value['type'] ) {
				case 'text':
				case 'email':
				case 'number':
				case 'password':
				case 'url':
					if ( $value['type'] != 'password' ) {
	    				$value['type'] = 'text';
	    			}

					$output .= $option_wrap_start;
					$output .= '<input 
						class="large-text" 
						type="'.esc_attr( $value['type'] ).'" 
						name="'.esc_attr( $value['id'] ).'" 
						id="'.esc_attr( $value['id'] ).'" 
						value="'.esc_attr( $option_value ).'">';
					$output .= $option_wrap_end;
				break;

				case 'textarea':
					$value = wp_parse_args( $value, array(
						'rows' => '',
					) );

					$output .= $option_wrap_start;
					$output .= '<textarea 
						class="large-text" 
						rows="'.esc_attr( $value['rows'] ).'"
	    				name="'.esc_attr( $value['id'] ).'" 
	    				id="'.esc_attr( $value['id'] ).'">'.
	    				esc_textarea( $option_value ).
	    				'</textarea>';
					$output .= $option_wrap_end;
				break;

				case 'select':
	    		case 'multiselect':
	    			$value = wp_parse_args( $value, array(
	    				'size' => '',
	    			) );

	    			$output .= $option_wrap_start;

	    			$output .= '<select 
	    				class="large-text widefat" 
	    				size="'.esc_attr( $value['size'] ).'"
	    				name="'.esc_attr( $value['id'] ).( 'multiselect' === $value['type'] ? '[]' : '' ).'" 
	    				id="'.esc_attr( $value['id'] ).'"'.
	    				( 'multiselect' === $value['type'] ? ' multiple="multiple"' : '' ).
	    				'>';

					foreach ( $value['options'] as $key => $val ) {
						$output .= '<option value="'.esc_attr( $key ).'" ';
						if ( is_array( $option_value ) ) {
							$output .= selected( in_array( $key, $option_value ), true, false );
						} else {
							$output .= selected( $key, $option_value, false );
						}
						$output .= '>'.esc_html( $val ).'</option>';
					}

					$output .= '</select>';

					$output .= $option_wrap_end;
				break;

				case 'checkbox':
					$value = wp_parse_args( $value, array(
						'label' => '',
					) );

					if ( ! isset( $value['checkboxgroup'] ) || 
						 ( isset( $value['checkboxgroup'] ) && 'start' == $value['checkboxgroup'] ) ) {
						$output .= $option_wrap_start;
					}

					$output .= '<label>';
					$output .= '<input 
						type="'.esc_attr( $value['type'] ).'" 
						name="'.esc_attr( $value['id'] ) .'" 
						id="'.esc_attr( $value['id'] ).'" value="1"'.
						( checked( $option_value, 1, false ) ).
						'>';
					$output .= ' '.esc_html( $value['label'] ).'</label>';
					$output .= '<br>';

					if ( ! isset( $value['checkboxgroup'] ) || 
					     ( isset( $value['checkboxgroup'] ) && 'end' == $value['checkboxgroup'] ) ) {
						$output .= $option_wrap_end;
					} 
				break;

				case 'multicheck':
					$output .= $option_wrap_start;

					foreach ( $value['options'] as $key => $val ) {
						$name = $value['id'].'['.$key.']';
						$checked = isset( $option_value[ $key ] ) ? checked( $option_value[ $key ], 1, false ) : '';

						$output .= '<label>';
						$output .= '<input 
							type="checkbox" 
							name="'.esc_attr( $name ).'" 
							id="'.esc_attr( $value['id'] ).'" 
							value="1" '.$checked.
							'>';
						$output .= ' '.esc_html( $val ).'</label>';
						$output .= '<br>';
					}

					$output .= $option_wrap_end;
				break;

				case 'radio':
					$output .= $option_wrap_start;
					
					foreach ( $value['options'] as $key => $val ) {
						$output .= '<label>';
						$output .= '<input 
							type="'.esc_attr( $value['type'] ).'" 
							name="'.esc_attr( $value['id'] ).'" 
							id="'.esc_attr( $value['id'].'_'.$key ).'" 
							value="'.esc_attr( $key ).'" '.
							checked( $key, $option_value, false ).'>';
						$output .= ' '.esc_html( $val ).'</label>';
						$output .= '<br>';
					}

					$output .= $option_wrap_end;
				break;

				case 'upload':
					// make sure mimes key is set
					$value = wp_parse_args( $value, array(
						'mimes' => '',
					) );

					$output .= '<div class="kopa_section">';
					$output .= $option_wrap_start;

					$output .= '<input type="text" 
						class="large-text kopa_upload" 
						name="'.esc_attr( $value['id'] ).'" 
						id="'.esc_attr( $value['id'] ).'" 
						value="'.esc_attr( $option_value ).'" 
						data-type="'.esc_attr( $value['mimes'] ).'">';

					$output .= '<br>';

					if ( function_exists( 'wp_enqueue_media' ) ) {
						if ( $option_value == '' ) {
							$output .= '<a style="margin-top: 3px" class="kopa_upload_button button">'.esc_html__( 'Upload', 'kopa-framework' ).'</a>';
						} else {
							$output .= '<a style="margin-top: 3px" class="kopa_remove_file button">'.esc_html__( 'Remove', 'kopa-framework' ).'</a>';
						}
					} else {
						$output .= '<small class="kopa_upload_notice">'.esc_html__( 'Upgrade your version of WordPress for full media support.', 'kopa-framework' ).'</small>';
					}
					$output .= $option_wrap_end;

					$output .= '<p class="kopa_screenshot">';

					if ( $option_value ) {
						$remove = '<a class="button kopa_remove_image">'.esc_html__( 'Remove', 'kopa-framework' ).'</a>';
						$image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico*)/i', $option_value );
						if ( $image ) {
							$output .= '<img style="max-width: 300px" src="' . esc_attr( $option_value ) . '" alt=""><br>' . $remove;
						}
					}

					$output .= '</p>';
					$output .= '</div>';
				break;

				default:
					$output .= apply_filters( 'kopa_admin_meta_box_field_' . $value['type'], '', $option_wrap_start, $option_wrap_end, $value, $option_value);
				break;
        	}

        	$_loop_index++;
        } // end foreach

        $output .= '</div>'; // .kopa-metabox-wrapper

        // finally, output fields
        echo $output;
	}

} // end class Kopa_Admin_Meta_Box

}