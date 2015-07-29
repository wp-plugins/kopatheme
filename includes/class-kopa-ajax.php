<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Kopa Framework Kopa_Ajax
 *
 * AJAX Event Handler
 *
 * @class 		Kopa_Admin_Ajax
 * @package		KopaFramework/Classes
 * @category	Class
 * @author 		Kopatheme
 * @since       1.0.0
 */
class Kopa_Ajax {

	/**
	 * Hook into ajax events
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {

		// woocommerce_EVENT => nopriv
		$ajax_events = array(
			'remove_sidebar' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_kopa_' . $ajax_event, array( $this, $ajax_event ) );

			if ( $nopriv )
				add_action( 'wp_ajax_nopriv_kopa_' . $ajax_event, array( $this, $ajax_event ) );
		}
	
	}

	/**
	 * Remove sidebar in sidebar manager
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function remove_sidebar() {
		$sidebar_id = apply_filters( 'kopa_remove_sidebar_id', $_POST['sidebar_id'] );

		// data for sending back to the frontend
		$allow_delete = true; // determines this sidebar can be deleted or not
		$warnings = array();  // warning messages
		$errors = array();    // error messages

		/**
		 * check this sidebar contained widgets or not
		 */
		// get all sidebars widgets data
		$sidebars_widgets = get_option( 'sidebars_widgets' );

		if ( isset( $sidebars_widgets[ $sidebar_id ] ) && ! empty( $sidebars_widgets[ $sidebar_id ] ) ) {
			$warnings[] = __( 'This sidebar is currently containing widgets.', 'kopa-framework' );
		}

		/**
		 * check this sidebar is being used in layout or not
		 */
	    $options_settings = Kopa_Admin_Settings::get_settings_arguments();

		if ( $options_settings ) {
			foreach ( $options_settings as $option ) {
				if ( empty( $option['type'] ) || empty( $option['id'] ) ) {
					continue;
				}

				if ( 'layout_manager' === $option['type'] && ! empty( $option['id'] ) ) {
					
					$option_value = Kopa_Admin_Settings::get_option( $option['id'] );

					if ( ! empty( $option_value['sidebars'] ) && is_array( $option_value['sidebars'] ) ) {
						foreach ( $option_value['sidebars'] as $layout_id => $sidebars ) {
							// if this sidebar is being used in this $layout_id
							if ( is_array( $sidebars ) && in_array( $sidebar_id, $sidebars ) ) {
								$layout_name = isset( $option['layouts'][ $layout_id ]['title'] ) ? $option['layouts'][ $layout_id ]['title'] : '';
								$page_title = isset( $option['title'] ) ? $option['title'] : '';
								$errors[] = "&nbsp;&nbsp;&nbsp;&nbsp;" . sprintf( __( '%1$s of %2$s.', 'kopa-framework' ), $layout_name, $page_title );
							}
						}
					}
				}
			}
		}

		// warning messages
		// if ( ! empty( $warnings ) ) {
		// 	$warnings[] = __( 'Delete it will also delete all widgets are inside it.', 'kopa-framework' );
		// }

		// if the sidebar is being used in some layouts
		// do not delete it
		if ( ! empty( $errors ) ) {
			$allow_delete = false;
			array_unshift( $errors, '<strong>' . __( 'This sidebar is being used in:', 'kopa-framework' ) . '</strong>' );
			$errors[] = '<strong>' . __('You cannot delete this sidebar.', 'kopa-framework') . '</strong>';
		}

		$data = array(
			'allow_delete'    => $allow_delete,
			'warnings'        => $warnings,
			'errors'          => $errors,
		);

		echo json_encode( $data );

		die();
	}
}

new Kopa_Ajax();