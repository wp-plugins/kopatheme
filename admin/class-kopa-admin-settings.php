<?php
/**
 * Kopa Admin Settings Class.
 *
 * @author 		Kopatheme
 * @category 	Admin
 * @package 	KopaFramework/Admin
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Kopa_Admin_Settings' ) ) {

/**
 * Kopa_Admin_Settings
 */
class Kopa_Admin_Settings {

	/**
	 * @access private
	 * @static
	 * @var array settings objects
	 */
	private static $settings = array();
	
	/**
	 * @access private
	 * @static
	 * @var array all settings arguments
	 */
	private static $settings_arguments = array();
	
	/**
	 * @access private
	 * @static
	 * @var array error messages
	 */
	private static $errors = array();

	/**
	 * @access private
	 * @static
	 * @var array info messages
	 */
	private static $messages = array();

	/**
	 * Include the settings page classes
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_settings_pages() {
		if ( empty( self::$settings ) ) {
			$settings = array();

			include_once( 'settings/class-kopa-settings-page.php' );

			$settings[] = include( 'settings/class-kopa-settings-theme-options.php' );
			$settings[] = include( 'settings/class-kopa-settings-sidebar-manager.php' );
			$settings[] = include( 'settings/class-kopa-settings-layout-manager.php' );
			$settings[] = include( 'settings/class-kopa-settings-backup-manager.php' );

			self::$settings = apply_filters( 'kopa_get_settings_pages', $settings );

			// merge all settings arguments to an array
			foreach ( $settings as $setting_obj ) {
				$options_settings = $setting_obj->get_page_settings();
				self::$settings_arguments = wp_parse_args( self::$settings_arguments, $options_settings );
			} // end outer foreach
		}
		return self::$settings;
	}

	/**
	 * Get all settings arguments
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_settings_arguments() {
		return self::$settings_arguments;
	}

	/**
	 * Save the settings
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function save() {
		global $kopa_current_tab;

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'kopa-settings' ) ) {
    		die( __( 'Action failed. Please refresh the page and retry.', 'kopa-framework' ) );
		}

	    // Trigger actions
		// restore default settings
		if ( isset( $_POST['kopa_reset'] ) ) {
			if ( isset( $_POST['kopa_restore_default'] ) ) {
				$restore_options = $_POST['kopa_restore_default'];
				$restore_options = explode( ',', $restore_options );

				foreach ( $restore_options as $tab_id ) {
					do_action( 'kopa_settings_reset_' . $tab_id );
				}
			} else {
				do_action ( 'kopa_settings_reset_' . $kopa_current_tab );
			}
			self::add_message( __( 'Default options restored.', 'kopa-framework' ) );
		}
		// save current tab settings
		else {
	   		do_action( 'kopa_settings_save_' . $kopa_current_tab );
			self::add_message( __( 'Your settings have been saved.', 'kopa-framework' ) );
		}

		do_action( 'kopa_settings_saved' );
	}

	/**
	 * Add a message
	 *
	 * @param string $text error|info message
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_message( $text ) {
		self::$messages[] = $text;
	}

	/**
	 * Add an error
	 *
	 * @param string $text
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_error( $text ) {
		self::$errors[] = $text;
	}

	/**
	 * Output messages + errors
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function show_messages() {
		if ( sizeof( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				echo '<div class="wrap"><div class="kopa_message error fade inline"><p><strong>' . esc_html( $error ) . '</strong></p></div></div>';
			}
		} elseif ( sizeof( self::$messages ) > 0 ) {
			foreach ( self::$messages as $message ) {
				echo '<div class="wrap"><div class="kopa_message updated fade inline"><p><strong>' . esc_html( $message ) . '</strong></p></div></div>';
			}
		}
	}

	/**
	 * Settings page.
	 *
	 * Handles the display of the main theme settings page in admin.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function output() {
		global $kopa_current_tab, $kopa_show_save_button;

		do_action( 'kopa_settings_start' );

		// Include settings pages
		self::get_settings_pages();

		// Get current tab/section
		$kopa_current_tab = empty( $_GET['tab'] ) ? 'theme-options' : sanitize_title( $_GET['tab'] );

		// Determines whether or not show save submit button
		$kopa_show_save_button = true;
		if ( 'backup-manager' === $kopa_current_tab ) {
			$kopa_show_save_button = false;
		}
		$kopa_show_save_button = apply_filters( 'kopa_settings_show_save_button', $kopa_show_save_button, $kopa_current_tab );

		// Save settings if data has been posted
	    if ( ! empty( $_POST ) ) {
	    	self::save();
	    }

	    // Add any posted messages
	    if ( ! empty( $_GET['kopa_error'] ) ) {
	    	self::add_error( stripslashes( $_GET['kopa_error'] ) );
	    }

    	if ( ! empty( $_GET['kopa_message'] ) ) {
	    	self::add_message( stripslashes( $_GET['kopa_message'] ) );
		}

	    self::show_messages();

		// Get tabs for the settings page
	    $tabs = apply_filters( 'kopa_settings_tabs_array', array() );

	    include 'views/html-admin-settings.php';
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param string $option_name Option id
	 * @param string $default Force default value
	 * @param array $value Option arguments
	 * @return string|array $option_value Option value
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_option( $option_name, $default = null, $value = array() ) {
		$options = get_theme_mods(); // get all theme options
		$option_value = null;
		$option_value_isset = false; // flag to check option value is set
		$type = ''; // option type

		/**
		 * Get option value
		 */
		if ( isset( $options[ $option_name ] ) ) {
			$option_value =  $options[ $option_name ];
			$option_value_isset = true;
		}
		// return force default value
		elseif ( $default ) {
			$option_value =  $default;
			$option_value_isset = true;
		}
		// return default value from third argument if not empty
		elseif ( isset( $value['default'] ) && 
			 isset( $value['id'] ) && 
			 $option_name === $value['id'] ) {
			$option_value = $value['default'];
			$option_value_isset = true;
		} 

		// get option type
		if ( isset( $value['type'] ) ) {
			$type = $value['type'];			
		}

		// fall back for backend and frontend get_option
		if ( empty( $type ) || ! $option_value_isset || empty( $value ) ) {
			$option = self::get_option_arguments( $option_name );

			if ( empty( $type ) && isset( $option['type'] ) ) {
				$type = $option['type']; // get option type
			}

			if ( ! $option_value_isset && isset( $option['default'] ) ) {
				$option_value = $option['default']; 
				$option_value_isset = true;
			}

			if ( empty( $value ) ) {
				$value = $option;
			}
		}

		// sanitize the option value
		$type = sanitize_title( $type );

		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes_deep', $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		// return the filter value
		return apply_filters( 'kopa_get_option_' . $type, $option_value, $value );
	}

	/**
	 * Get option arguments from option id
	 * 
	 * @param string $option_name option id
	 * @return array $option_arg option argument
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_option_arguments( $option_name ) {
		$settings_arguments = self::$settings_arguments;

		foreach ( $settings_arguments as $option ) {
			if ( isset( $option['id'] ) && $option_name === $option['id'] ) {
				return $option;
			}
		} // end foreach

		return array();
	}

	/**
	 * Sanitize option arguments, make sure option 
	 * arguments does not missing essential arguments 
	 *
	 * @uses wp_parse_args() to sanitize missing option arguments
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function sanitize_option_arguments( $option = array() ) {
		return wp_parse_args( $option, array(
			// common arguments
			'type'    => '',
			'id'      => '',
			'title'   => '',
			'class'   => '',
			'css'     => '',
			'default' => '',
			'desc'    => '',
		) );
	}

	/**
	 * Output admin fields.
	 *
	 * Loops though the theme options array and outputs each field.
	 *
	 * @param array $options Opens array to output
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function output_fields( $options ) {
		
		$title_counter = 0;
		$option_wrap_start = '';
		$option_wrap_end = '';
		$output = '';

		foreach ( $options as $value ) {
			if ( ! isset( $value['type'] ) ) continue;
	    	
			$value = self::sanitize_option_arguments( $value );

	    	// folding class
	    	$fold = '';
	    	if ( array_key_exists( 'fold', $value ) ) {
				if ( self::get_option( $value['fold'] ) ) {
					$fold = 'kopa_fold_'.$value['fold'].' ';
				} else {
					$fold = 'kopa_fold_'.$value['fold'].' kopa_hide ';
				}
			}

			// option classes
	    	$class = 'kopa_section';
	    	$class .= ' kopa_section_' . $value['type'];
	    	if ( $value['class'] ) {
		    	$class .= ' ' . $value['class'];
	    	}

	    	// start option wrap
	    	$option_wrap_start = '<div id="kopa_section_'.esc_attr( $value['id'] ).'" class="'.esc_attr( $class.' '.$fold ).'">';
	    	
	    	if ( $value['title'] ) {
				$option_wrap_start .= '<h4 class="kopa_heading">'.esc_html( $value['title'] ).'</h4>';
	    	}

			$option_wrap_start .= '<div class="kopa_option">';

			if ( $value['desc'] ) {
				$option_wrap_start .= '<div class="kopa_description">'.wpautop( esc_html( $value['desc'] ) ).'</div>';
			}
			$option_wrap_start .= '<div class="kopa_controls">';
			
			// end option wrap
			$option_wrap_end = '</div></div></div>';

			// get option value
			$option_value = self::get_option( $value['id'], null, $value );

	    	switch ( $value['type'] ) {
	    		case 'title':
	    			$value = wp_parse_args( $value, array(
	    				'icon' => ''
	    			) );

	    			$title_counter++;

	    			if ( $title_counter >= 2 ) {
	    				$output .= '</div>';
	    			}

	    			$output .= '<div class="kopa_tab_pane" id="kopa_'.esc_attr( $value['id'] ).'">';
    			break;

	    		case 'groupstart':
	    			$output .= '<div id="kopa_section_'.esc_attr( $value['id'] ).'" class="kopa_section_group">';
	    	
			    	if ( $value['title'] ) {
						$output .= '<h4 class="kopa_heading_group">'.esc_html( $value['title'] ).'</h4>';
			    	}

			    	$output .= '<div class="kopa_group_content">';
		    	break;

			    case 'groupend':
			    	$output .= '</div></div>';
		    	break;	

	    		case 'text':
	            case 'email':
	            case 'number':
	            case 'url':
	            case 'color':
	            case 'password':
	    			$default_color = '';
	    			$field_class = '';

	    			if ( 'color' === $value['type'] ) {
    					$field_class = ' kopa_color';

    					if ( $value['default'] ) {
	    					$default_color = ' data-default-color="' .esc_attr( $value['default'] ) . '" ';
    					} // end check empty option value
	    			} // end check color type

	    			if ( $value['type'] != 'password' ) {
	    				$value['type'] = 'text';
	    			}

	    			$output .= $option_wrap_start;
					$output .= '<input 
						class="'.esc_attr( $field_class ).'" 
						style="'.esc_attr( $value['css'] ).'" 
						type="'.esc_attr( $value['type'] ).'" 
						name="'.esc_attr( $value['id'] ).'" 
						id="'.esc_attr( $value['id'] ).'" 
						value="'.esc_attr( $option_value ).'"'.
						$default_color.'>';
					$output .= $option_wrap_end;
    			break;

	    		case 'textarea':
	    			$output .= $option_wrap_start;
	    			$output .= '<textarea 
	    				style="'.esc_attr( $value['css'] ).'" 
	    				name="'.esc_attr( $value['id'] ).'" 
	    				id="'.esc_attr( $value['id'] ).'">'.
	    				esc_textarea( $option_value ).
	    				'</textarea>';
	    			$output .= $option_wrap_end;
    			break;

	    		case 'select':
	    		case 'multiselect':
	    			$output .= $option_wrap_start;

	    			$output .= '<select 
	    				style="'.esc_attr( $value['css'] ).'" 
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

					$fold = '';
					if ( array_key_exists('folds', $value) ) {
						$fold = 'kopa_fold ';
					}

					$output .= '<label>';
					$output .= '<input 
						class="'.$fold.'" 
	    				style="'.esc_attr( $value['css'] ).'" 
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
							style="'.esc_attr( $value['css'] ).'" 
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
	    					style="'.esc_attr( $value['css'] ).'" 
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

					$output .= $option_wrap_start;
					$output .= '<input type="text" 
						class="kopa_upload" 
						style="'.esc_attr( $value['css'] ).'" 
						name="'.esc_attr( $value['id'] ).'" 
						id="'.esc_attr( $value['id'] ).'" 
						placeholder="'.esc_attr__( 'No file chosen', 'kopa-framework' ).'" 
						value="'.esc_attr( $option_value ).'" 
						data-type="'.esc_attr( $value['mimes'] ).'">';

					// upload button
					if ( function_exists( 'wp_enqueue_media' ) ) {
						if ( $option_value == '' ) {
							$output .= '<input id="kopa_upload_'.esc_attr( $value['id'] ).'" class="kopa_upload_button kopa_button button" type="button" value="'.esc_attr__( 'Upload', 'kopa-framework' ).'">';
						} else {
							$output .= '<input id="kopa_remove_'.esc_attr( $value['id'] ).'" class="kopa_remove_file kopa_button button" type="button" value="'.esc_attr__( 'Remove', 'kopa-framework' ).'">';
						}
					} else {
						$output .= '<p class="kopa_upload_notice">' .esc_html__( 'Upgrade your version of WordPress for full media support.', 'kopa-framework' ) . '</p>';
					}

					// preview image
					$preview_class = '';
					if ( empty( $option_value ) ) {
						$preview_class = 'kopa_hide';
					}

					$output .= '<div class="kopa_screenshot '.esc_attr( $preview_class ).'" id="'.esc_attr( $value['id'] ).'_image">';

					if ( $option_value ) {
						$remove = '<a class="kopa_remove_image">'.__( 'Remove', 'kopa-framework' ).'</a>';
						$image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico*)/i', $option_value );
						if ( $image ) {
							$output .= '<img src="' . esc_attr( $option_value ) . '" alt="" />' . $remove;
						} else {
							$parts = explode( "/", $option_value );
							for( $i = 0; $i < sizeof( $parts ); ++$i ) {
								$title = $parts[$i];
							}

							// No output preview if it's not an image.
							$output .= '';

							// Standard generic output if it's not an image.
							$title = __( 'View File', 'kopa-framework' );
							$output .= '<div class="kopa_no_image"><span class="kopa_file_link"><a href="' . esc_attr( $option_value ) . '" target="_blank" rel="external">'.$title.'</a></span></div>';
						}
					}

					$output .= '</div>';

					$output .= $option_wrap_end;
				break;

				case 'select_font':
					$option_value = wp_parse_args( $option_value, array(
						'family'           => '',
						'style'            => '',
						'size'             => '',
						'color'            => '',
					) );

					$preview = isset( $value['preview'] ) ? $value['preview'] : __( 'preview text', 'kopa-framework' );

					$output .= $option_wrap_start;

					// select section
					// font family
					$output .= '<select 
						class="kopa_select_font" 
						id="'.esc_attr( $value['id'] ).'" 
						name="'.esc_attr( $value['id'] ).'[family]" 
						data-main-id="'.esc_attr( $value['id'] ).'">';
					foreach ( $value['options'] as $key => $val ) {
						// check font groups
						if ( is_array( $val ) ) {
							if ( isset( $value['groups'][ $key ] ) ) {
								$output .= '<optgroup label="'.esc_attr( $value['groups'][ $key ] ).'">';
							} // end check group exists
							foreach ( $val as $font_val => $font_label ) {
								$output .= '<option value="'.esc_attr( $font_val ).'" '.selected( $font_val, $option_value['family'], false ).'>'.$font_label.'</option>';
							}
							if ( isset( $value['groups'][ $key ] ) ) {
								$output .= '</optgroup>';
							} // end check group exists
						} else {
							$output .= '<option value="'.esc_attr( $key ).'" '.selected( $key, $option_value['family'], false ).'>'.$val.'</option>';
						} // end check font groups
					}
					$output .= '</select>';

					// font weight / style
					$output .= '<select class="kopa_select_font_style" id="'.esc_attr( $value['id'] ).'_style" name="'.esc_attr( $value['id'] ).'[style]" data-main-id="'.esc_attr( $value['id'] ).'">';
					$output .= '<option value="'.esc_attr( $option_value['style'] ).'" selected="selected">'.$option_value['style'].'</option>';
					$output .= '</select>';

					// font size
					$output .= '<input type="text" class="kopa_select_font_size" name="'.esc_attr( $value['id'] ).'[size]" data-main-id="'.esc_attr( $value['id'] ).'" value="'.esc_attr( $option_value['size'] ).'">';

					// font color
					$output .= '<input class="kopa_select_font_color" name="'.esc_attr( $value['id'] ).'[color]" value="'.esc_attr( $option_value['color'] ).'" data-main-id="'.esc_attr( $value['id'] ).'" data-default-color="'.esc_attr( $option_value['color'] ).'">';

					// preview section
					$output .= '<p 
						class="kopa_google_font_preview" 
						id="'.esc_attr( $value['id'] ).'_preview">'.esc_html( $preview ).'</p>';
					$output .= $option_wrap_end;
				break;

				case 'custom_font_manager':
					$orders = array();
					$custom_font_attributes = array(
						'name' => array(
							'type'        => 'text',
							'placeholder' => __( 'Enter font name', 'kopa-framework' ),
							'required'    => false,
						),
						'woff' => array(
							'type'        => 'upload',
							'placeholder' => __( 'Upload .woff font file', 'kopa-framework' ),
							'mimes'       => 'font/woff',
						),
						'ttf' => array(
							'type'        => 'upload',
							'placeholder' => __( 'Upload .ttf font file', 'kopa-framework' ),
							'mimes'       => 'font/truetype',
						),
						'eot' => array(
							'type'        => 'upload',
							'placeholder' => __( 'Upload .eot font file', 'kopa-framework' ),
							'mimes'       => 'font/eot',
						),
						'svg' => array(
							'type'        => 'upload',
							'placeholder' => __( 'Upload .svg font file', 'kopa-framework' ),
							'mimes'       => 'font/svg',
						),
					);

					$output .= $option_wrap_start;

					$output .= '<div class="kopa_custom_font_list">';

					if ( $option_value && is_array( $option_value ) ) {
						foreach ( $option_value as $font_index => $font_item ) {
							$orders[] = $font_index;

							$output .= '<div class="kopa_custom_font_item">';
							
							// top
							$output .= '<div class="kopa_custom_font_top">';
							
							$output .= '<div class="kopa_custom_font_title_action">';
							$output .= '</div>'; // kopa_custom_font_title_action

							$output .= '<div class="kopa_custom_font_title">';
							$output .= '<strong>'.$font_item['name'].'</strong>';
							$output .= '</div>'; // kopa_custom_font_title

							$output .= '</div>'; // kopa_custom_font_top

							// inside
							$output .= '<div class="kopa_custom_font_inside kopa_hide">';
							
							foreach ( $custom_font_attributes as $attribute => $attribute_data ) {

								$attribute_classes = 'kopa_custom_font_item_' . $attribute;
								if ( 'upload' === $attribute_data['type'] ) {
									$attribute_classes .= ' kopa_upload';
								}

								$attribute_required = false;
								if ( isset( $attribute_data['required'] ) && $attribute_data['required'] ) {
									$attribute_required = true;
								}

								$attribute_mimes = '';
								if ( isset( $attribute_data['mimes'] ) && $attribute_data['mimes'] ) {
									$attribute_mimes = $attribute_data['mimes'];
								}

								$output .= '<div class="kopa_section"><div class="kopa_controls">';

								$output .= '<input class="'.esc_attr( $attribute_classes ).'" 
										type="text" 
										name="'.esc_attr( $value['id'].'['.$font_index.']['.$attribute.']' ).'"  
										placeholder="'.esc_attr( $attribute_data['placeholder'] ).'" '.
										($attribute_required ? 'required ' : '') .
										'value="'.esc_attr( $font_item[ $attribute ] ).'" 
										data-type="'.esc_attr( $attribute_mimes ).'">';

								// upload button
								if ( 'upload' === $attribute_data['type'] && function_exists( 'wp_enqueue_media' ) ) {
									if ( $font_item[ $attribute ] == '' ) {
										$output .= '<input class="kopa_upload_button kopa_button button" type="button" value="'.esc_attr__( 'Upload', 'kopa-framework' ).'">';
									} else {
										$output .= '<input class="kopa_remove_file kopa_button button" type="button" value="'.esc_attr__( 'Remove', 'kopa-framework' ).'">';
									}
								}

								$output .= '</div></div>'; // kopa_section
							}

							$output .= '<div class="kopa_custom_font_control_actions">';
							$output .= '<a class="kopa_custom_font_remove" href="#">'.esc_html__( 'Delete', 'kopa-framework' ).'</a>';
							$output .= ' | ';
							$output .= '<a class="kopa_custom_font_close" href="#">'.esc_html__( 'Close', 'kopa-framework' ).'</a>';
							$output .= '</div>'; // kopa_custom_font_control_actions
							$output .= '</div>'; // kopa_custom_font_inside

							$output .= '</div>'; // kopa_custom_font_item
						}
					}

					$output .= '</div>'; // kopa_custom_font_list

					// get list of font orders
					$data_orders = '';
					if ( $orders ) {
						$data_orders = implode(',', $orders);
					}

					$output .= '<input class="button kopa_add_font_button" type="button" value="'.esc_attr__( 'Add New Font', 'kopa-framework' ).'" data-name="'.esc_attr( $value['id'] ).'" data-orders="'.esc_attr( $data_orders ).'">';
					$output .= $option_wrap_end;
				break;

				case 'import':
					$output .= $option_wrap_start;
					$output .= '<input type="file" name="kopa_import_file" size="25">';
					$output .= '<input type="submit" class="button-secondary kopa_import" name="kopa_backup_import" value="'.esc_attr__( 'Import', 'kopa-framework' ).'">';
					$output .= $option_wrap_end;
				break;

				case 'export':
					$output .= $option_wrap_start;

					foreach ( $value['options'] as $key => $val ) {
						$output .= '<label><input value="'.esc_attr( $key ).'" type="radio" name="kopa_export_type" '.checked( $value['default'], $key, false ).'> '.esc_html( $val ).'</label><br>';
					}

					$output .= '<input type="submit" class="button-secondary kopa_export" name="kopa_backup_export" value="'.esc_attr__( 'Download Export File', 'kopa-framework' ).'">';

					$output .= $option_wrap_end;
				break;

				case 'sidebar_manager':
					global $wp_registered_sidebars;

					// hold merge data of registered sidebars may be by the theme
					// and registered sidebars by the sidebar manager
					if ( $option_value && is_array( $option_value ) ) {
						$temp_sidebars = wp_parse_args( $wp_registered_sidebars, $option_value );
					} else {
						$temp_sidebars = $wp_registered_sidebars;
					}

					$sidebar_atts = array(
			    		'name'          => __( 'Name', 'kopa-framework' ),
			    		'description'   => __( 'Description', 'kopa-framework' ),
			    		'before_widget' => __( 'Before Widget', 'kopa-framework' ),
			    		'after_widget'  => __( 'After Widget', 'kopa-framework' ),
			    		'before_title'  => __( 'Before Title', 'kopa-framework' ),
			    		'after_title'   => __( 'After Title', 'kopa-framework' ),
			    	);

					$data_register_sidebars = 0;
					$data_register_sidebar_ids = '';

					// if not empty, get the number of register sidebars
					$data_register_sidebars = count( $temp_sidebars );

					// get register sidebar ids
					foreach ( $temp_sidebars as $sidebar_id => $sidebar_args ) {
						$data_register_sidebar_ids .= $sidebar_id . ',';
					}

					$output .= '<div id="kopa_section_'.esc_attr( $value['id'] ).'" class="kopa_section kopa_section_'.esc_attr( $value['type'] ).'">';
					
					// add new sidebar section
					$output .= '<div class="kopa_section_add_new_sidebar">';
					$output .= '<h4 class="kopa_heading">'.esc_html( $value['title'] ).'</h4>';
					$output .= '<div class="kopa_option">';
					$output .= '<div class="kopa_description">'.esc_html( $value['description'] ).'</div>';
						$output .= '<div class="kopa_controls">';
							$output .= '<input type="text" class="kopa_sidebar_add_field" name="sidebar" id="kopa_'.esc_attr( $value['id'] ).'_add_field">';
							$output .= '<a class="kopa_sidebar_add_button kopa_button_inactive" href="#" data-registered-sidebars="'.esc_attr( $data_register_sidebars ).'" data-register-sidebar-ids="'.esc_attr( $data_register_sidebar_ids ).'" data-name="'.esc_attr( $value['id'] ).'" data-container-id="kopa_'.esc_attr( $value['id'] ).'">'.esc_html__( 'Add New', 'kopa-framework' ).'</a>';
						$output .= '</div>'; // kopa_controls
					$output .= '</div>'; // kopa_option
					$output .= '</div>'; // kopa_section_add_new_sidebar

					// sidebar manager
					$output .= '<div class="kopa_section_sidebars">';
					$output .= '<ul id="kopa_'.esc_attr( $value['id'] ).'" class="kopa_sidebar_sortable kopa_ui_sortable">';

					if ( $option_value && is_array( $option_value ) ) {

						foreach ( $option_value as $sidebar_id => $sidebar_args ) {
							
							$sidebar_value = array();

							// get current sidebar arguments
							foreach ( $sidebar_atts as $key => $label ) {
								if ( isset( $option_value[ $sidebar_id ][ $key ] ) ) {
									$sidebar_value[ $key ] = $option_value[ $sidebar_id ][ $key ];
								} elseif ( isset( $sidebar_args[ $key ] ) ) {
									$sidebar_value[ $key ] = $sidebar_args[ $key ];
								// } elseif( isset( $value['default_atts'][$key] ) ) {
								// 	$sidebar_value[ $key ] = $value['default_atts'][ $key ];
								} else {
									$sidebar_value[ $key ] = '';
								}
							}

							$output .= '<li class="kopa_sidebar">';
								$output .= '<div class="kopa_sidebar_header">';
									$output .= '<div class="kopa_sidebar_title_action"></div>';
									$output .= '<strong>'.esc_html( $sidebar_value['name'] ).'</strong>';
								$output .= '</div>'; // kopa_sidebar_header

								$output .= '<div class="kopa_sidebar_body">';

								// checkbox folding for advanced settings
								$output .= '<label><input class="kopa_sidebar_advanced_settings" type="checkbox"> '.esc_html__( 'Advanced Settings', 'kopa-framework' ).'</label>';

							// print sidebar attribute fields
							foreach ( $sidebar_atts as $key => $label ) {
								
								$id = 'kopa_'.$sidebar_id.'_'.$key;
								$name = $value['id'] . '[' . $sidebar_id . ']' . '[' . $key . ']';
								
								$output .= '<div class="kopa_sidebar_'.esc_attr( $key ).'">';
								// $output .= '<label for="'.esc_attr( $id ).'">'.$label.'</label>';
								$output .= '<input type="text" class="kopa_sidebar kopa_sidebar_attr" name="'.esc_attr( $name ).'" id="'.esc_attr( $id ).'" value="'.esc_attr( $sidebar_value[ $key ] ).'" placeholder="'.esc_attr( $label ).'">';
								$output .= '</div>';
									
							} // end foreach sidebar_atts

								$output .= '<div class="kopa_sidebar_control_actions">';
									$output .= '<a class="kopa_sidebar_delete_button" href="#" data-sidebar-id="'.esc_attr( $sidebar_id ).'">'.esc_html__( 'Delete', 'kopa-framework' ).'</a>';
									$output .= ' | ';
									$output .= '<a class="kopa_sidebar_close_button" href="#">'.esc_html__( 'Close', 'kopa-framework' ).'</a>';
									$output .= '<span class="spinner"></span>';
								$output .= '</div>'; // kopa_sidebar_control_actions

								$output .= '</div>'; // kopa_sidebar_body
								
							$output .= '</li>';
						
						} // end foreach sidebar options

					} // end check empty sidebar settings
					
					$output .= '</ul>';
					$output .= '</div>';

					$output .= '</div>';
				break;

				case 'layout_manager':
					$value = wp_parse_args( $value, array(
						'layouts' => array(),
					) );

					global $wp_registered_sidebars;

					$output .= '<div id="kopa_section_group_'.esc_attr( $value['id'] ).'" class="kopa_section_group kopa_section_group_layout">';
					$output .= '<h2 class="kopa_heading_group">'.esc_html( $value['title'] ).'</h2>';
					$output .= '<div class="kopa_group_content">';

					// layout images
					foreach ( $value['layouts'] as $layout_id => $layout_args ) {
						$output .= '<div id="'.esc_attr( $value['id'] . '_' . $layout_id . '_' . 'image' ).'" class="kopa_section_layout_image">';
						$output .= '<img src="'.esc_attr( $layout_args['preview'] ).'" alt="'.esc_attr( $layout_args['title'] ).'">';
						$output .= '</div>';
					}

					// select layout section
					$output .= '<div id="kopa_section_select_layout_'.esc_attr( $value['id'] ).'" class="kopa_section kopa_section_select_layout">';
					$output .= '<h4 class="kopa_heading">'.esc_html__( 'Select layout', 'kopa-framework' ).'</h4>';
					$output .= '<div class="kopa_option">';
					$output .= '<div class="kopa_controls">';
					$output .= '<select name="'.esc_attr( $value['id'] ).'[layout_id]" id="select-layout-'.esc_attr( $value['id'] ).'" data-layout-section-id="'.esc_attr( $value['id'] ).'">';
					
						foreach ( $value['layouts'] as $layout_id => $layout_args ) {
							$selected_layout_id = null;
							if ( isset( $option_value['layout_id'] ) ) {
								$selected_layout_id = $option_value['layout_id'];
							}
							$output .= '<option value="'.esc_attr( $layout_id ).'" '.selected( $selected_layout_id, $layout_id, false ).'>'.esc_html( $layout_args['title'] ).'</option>';
						}

					$output .= '</select>';
					$output .= '</div>'; // kopa_controls
					$output .= '</div>'; // kopa_option
					$output .= '</div>'; // kopa_section_select_layout

					// widget areas
					foreach ( $value['layouts'] as $layout_id => $layout_args ) {

						$output .= '<div id="'.esc_attr( $value['id'] . '_' . $layout_id ).'" class="kopa_section_select_area_container">';

						foreach ( $layout_args['positions'] as $position_index => $position ) {

							$output .= '<div id="kopa_section_select_area_'.esc_attr( $position_index . '_' . $layout_id ).'" class="kopa_section kopa_section_select_area">';
							$output .= '<h4 class="kopa_heading">'.esc_html( $value['positions'][ $position ] ).'</h4>';
							$output .= '<div class="kopa_option">';
							$output .= '<div class="kopa_controls">';
							$output .= '<select name="'.esc_attr( $value['id'] ).'[sidebars]['.esc_attr( $layout_id ).']['.$position.']">';
							$output .= '<option value="">'.esc_html__( '&mdash;Select sidebar&mdash;', 'kopa-framework' ).'</option>';
							
							// print all registered sidebars
							foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar_args ) {

								$selected_value = null;
								if ( isset( $option_value['sidebars'][ $layout_id ][ $position ] ) ) {
									$selected_value = $option_value['sidebars'][ $layout_id ][ $position ];
								}

								$output .= '<option value="'.esc_attr( $sidebar_id ).'" '.selected( $selected_value, $sidebar_id, false ).'>'.esc_html( $sidebar_args['name'] ).'</option>';
							}

							$output .= '</select>';
							$output .= '</div>'; // kopa_controls
							$output .= '</div>'; // kopa_option
							$output .= '</div>'; // kopa_section_select_area
						}

						$output .= '</div>'; // kopa_section_select_area_container

					}

					$output .= '</div>'; // kopa_group_content
					$output .= '</div>'; // kopa_section_group_layout
				break;

				case 'restore_default':
					$output .= $option_wrap_start;

					foreach ( $value['options'] as $key => $val ) {
						$output .= '<label><input value="'.esc_attr( $key ).'" type="radio" name="kopa_'.esc_attr( $value['type'] ).'" '.checked( $value['default'], $key, false ).'> '.esc_html( $val ).'</label><br>';
					}

					$output .= '<input type="submit" class="button-secondary kopa_reset" name="kopa_reset" value="'.esc_attr__( 'Restore Defaults', 'kopa-framework' ).'">';

					$output .= $option_wrap_end;
				break;

				// Default: run an action
	            default:
	            	$output .= apply_filters( 'kopa_admin_field_' . $value['type'], '', $option_wrap_start, $option_wrap_end, $value );
	            break;

	    	} // end switch option type

		} // end foreach

		if ( $title_counter ) {
			$output .= '</div>';
		} // end check if have title options

		echo '<div class="kopa_tab_content">' . $output . '</div>';

	}

	/**
	 * Save admin fields.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array $options Opens array to output
	 * @return bool
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function save_fields( $options ) {
	    if ( empty( $_POST ) )
	    	return false;

	    // Options to update will be stored here
	    $update_options = array();

	    // Loop options and get values to save
	    foreach ( $options as $value ) {

	    	if ( ! isset( $value['id'] ) ) {
	    		continue;
	    	}

	    	$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

	    	// Get the option name
	    	$option_value = null;

	    	if ( isset( $_POST[ $value['id'] ] ) ) {
    			$option_value = $_POST[ $value['id'] ];
    		}

    		// Custom handling
    		do_action( 'kopa_update_option_' . $type, $value );

	    	// For a value to be submitted to database it must pass through a sanitization filter
	    	if ( has_filter( 'kopa_sanitize_option_' . $type ) ) {
	    		$option_value = apply_filters( 'kopa_sanitize_option_' . $type, $option_value, $value );
	    	}

	    	if ( ! is_null( $option_value ) ) {
				$update_options[ $value['id'] ] = $option_value;
			}

	    	// Custom handling
	    	do_action( 'kopa_update_option', $value );
	    }

	    // Hook to run after validation
		do_action( 'kopa_options_after_validate', $update_options );

	    // Now save the options
	    foreach( $update_options as $name => $value ) {
	    	set_theme_mod( $name, $value );
	    }

	    return true;
	}

	/**
	 * Get the default values for all the theme options
	 *
	 * Get an array of all default values as set in
	 * options.php. The 'id','std' and 'type' keys need
	 * to be defined in the configuration array. In the
	 * event that these keys are not present the option
	 * will not be included in this function's output.
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function reset_fields( $options ) {
		if ( empty( $_POST ) )
			return false;

		foreach ( $options as $value ) {

			if ( ! isset( $value['id'] ) ) {
	    		continue;
	    	}

	    	if ( ! isset( $value['default'] ) ) {
	    		continue;
	    	}

	    	$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

	    	if ( 'title'      === $type ||
				 'groupstart' === $type ||
				 'groupend'   === $type ) {
				continue;
			}

	    	// get option value
	    	$option_value = null;

	    	if ( isset( $value['default'] ) ) {
		    	$option_value = $value['default'];
	    	}

	    	// get option type
	    	$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

	    	// For a value to be submitted to database it must pass through a sanitization filter
	    	if ( has_filter( 'kopa_sanitize_option_' . $type ) ) {
	    		$option_value = apply_filters( 'kopa_sanitize_option_' . $type, $option_value, $value );
	    	}
			
			set_theme_mod( $value['id'], $option_value );

		}

		return true;
	}
}

}
