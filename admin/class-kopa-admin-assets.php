<?php
/**
 * Load assets.
 *
 * @author 		Kopatheme
 * @category 	Admin
 * @package 	KopaFramework/Admin
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Kopa_Admin_Assets' ) ) {

/**
 * Kopa_Admin_Assets Class
 */
class Kopa_Admin_Assets {

	/**
	 * Hook in tabs.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_styles() {
		global $wp_scripts;

		$screen = get_current_screen();

		// Admin styles for admins pages only
		wp_register_style( 'kopa_admin', KF()->framework_url() . '/assets/css/admin.css', array(), KOPA_FRAMEWORK_VERSION );

		// font awesome
		wp_register_style( 'kopa_font_awesome', KF()->framework_url() . '/assets/css/font-awesome.css', array(), '4.0.3' );

		// style for custom layout feature
		wp_register_style( 'kopa_custom_layout', KF()->framework_url() . '/assets/css/custom-layout.css', array(), KOPA_FRAMEWORK_VERSION );

		// check admin pages to enqueue styles
		if ( in_array( $screen->id, kopa_get_screen_ids() ) ) {
			wp_enqueue_style( 'kopa_font_awesome' );
			wp_enqueue_style( 'kopa_admin' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'thickbox' );
		}

		do_action( 'kopa_admin_css' );
	}


	/**
	 * Enqueue scripts
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function admin_scripts() {
		global $wp_query, $post;

		$screen       = get_current_screen();
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// $suffix       = false ? '' : '.min';

		// Register scripts
		wp_register_script( 'kopa_admin', KF()->framework_url() . '/assets/js/admin'.$suffix.'.js', array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ), KOPA_FRAMEWORK_VERSION );

		wp_register_script( 'kopa_dynamic_sidebar', KF()->framework_url() .'/assets/js/admin-sidebar'.$suffix.'.js', array( 'jquery', 'jquery-ui-sortable', 'thickbox' ), KOPA_FRAMEWORK_VERSION );

		wp_register_script( 'kopa_dynamic_layout', KF()->framework_url() .'/assets/js/admin-layout'.$suffix.'.js', array( 'jquery' ), KOPA_FRAMEWORK_VERSION );

		// script for custom layout feature
		wp_register_script( 'kopa_custom_layout', KF()->framework_url() . '/assets/js/custom-layout'.$suffix.'.js', array( 'jquery' ), KOPA_FRAMEWORK_VERSION );

		// KopaFramework admin pages
	    if ( in_array( $screen->id, kopa_get_screen_ids() ) ) {
			wp_enqueue_script( 'kopa_admin' );
			wp_localize_script( 'kopa_admin', 'kopa_google_fonts', kopa_google_font_property_list_array() );
			wp_localize_script( 'kopa_admin', 'kopa_google_font_families', kopa_google_font_list() );
			wp_localize_script( 'kopa_admin', 'kopa_system_fonts', kopa_system_font_list() );
			wp_localize_script( 'kopa_admin', 'kopa_font_styles', kopa_font_style_options() );
			wp_localize_script( 'kopa_admin', 'kopa_custom_font_attributes', array(
				'name' => array(
					'type'        => 'text',
					'placeholder' => __( 'Enter font name', 'kopa-framework' ),
					'required'    => false,
					'value'       => __( 'Custom font', 'kopa-framework' ),
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
			) );
			wp_localize_script( 'kopa_admin', 'kopa_admin_l10n', array(
				'upload' => __( 'Upload', 'kopa-framework' ),
				'remove' => __( 'Remove', 'kopa-framework' ),
				'confirm_reset'   => __( 'Click OK to reset. Any selected settings will be lost!', 'kopa-framework' ),
				'confirm_import'  => __( 'Click OK to import. Any selected settings will be lost!', 'kopa-framework' ),
				'confirm_delete'  => __( 'Are you sure you want to delete?', 'kopa-framework' ),
			) );

			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}

			wp_enqueue_script( 'kopa_dynamic_sidebar' );
			wp_localize_script( 'kopa_dynamic_sidebar', 'kopa_sidebar_attributes_l10n', array(
				'ajax_url'          => admin_url('admin-ajax.php'),
				'warning'           => __( 'Warning', 'kopa-framework' ),
				'error'             => __( 'Error', 'kopa-framework' ),
				'info'              => __( 'Info', 'kopa-framework' ),
				'confirm_message'   => __( 'Are you sure you want to delete?', 'kopa-framework' ),
				'close'             => __( 'Close', 'kopa-framework' ),
				'remove'            => __( 'Delete', 'kopa-framework' ),
				'advanced_settings' => __( 'Advanced Settings', 'kopa-framework' ),
				'attributes'        => array(
					'name'          => __( 'Name', 'kopa-framework' ),
					'description'   => __( 'Description', 'kopa-framework' ),
					'before_widget' => __( 'Before Widget', 'kopa-framework' ),
					'after_widget'  => __( 'After Widget', 'kopa-framework' ),
					'before_title'  => __( 'Before Title', 'kopa-framework' ),
					'after_title'   => __( 'After Title', 'kopa-framework' ),
				),
			) );

			wp_enqueue_script( 'kopa_dynamic_layout' );
		}
	}
   
}

}

return new Kopa_Admin_Assets();
