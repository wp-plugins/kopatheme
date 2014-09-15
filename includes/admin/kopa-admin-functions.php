<?php
/**
 * KopaFramework Admin Functions
 *
 * @author      Kopatheme
 * @category    Core
 * @package     KopaFramework/Admin/Functions
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get all KopaFramework screen ids
 *
 * @return array list of setting page id
 *
 * @since 1.0.0
 */
function kopa_get_screen_ids() {
	$menu = Kopa_Admin_Menus::menu_settings();
	$menu_slug = empty( $menu['menu_slug'] ) ? 'kopa-framework' : $menu['menu_slug'];
	$kopa_screen_id = sanitize_title( $menu_slug );

    return apply_filters( 'kopa_screen_ids', array(
    	'appearance_page_' . $kopa_screen_id,
    ) );
}