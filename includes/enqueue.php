<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Enqueue scripts and styles for the settings page.
 * 
 * @param string $hook The current admin page.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_enqueue_admin_scripts( $hook ) {
    if ( 'settings_page_crwp_settings' === $hook ) {
        // Enqueue Select2 library.
        wp_enqueue_script( 'select2-js', plugin_dir_url( __FILE__ ) . 'assets/js/select2.min.js', [ 'jquery' ], null, true );
        wp_enqueue_style( 'select2-css', plugin_dir_url( __FILE__ ) . 'assets/css/select2.min.css' );

        // Enqueue custom admin JS.
        wp_enqueue_script( 'crwp-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/crwp-admin.js', [ 'jquery', 'select2-js' ], CRWP_VERSION, true );
        wp_localize_script( 'crwp-admin-js', 'crwp_admin', [
            'nonce' => wp_create_nonce( 'crwp_ajax_nonce' ),
        ] );

        // Enqueue custom admin CSS.
        wp_enqueue_style( 'crwp-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/crwp-admin.css', [], CRWP_VERSION );
    }
}
add_action( 'admin_enqueue_scripts', 'crwp_enqueue_admin_scripts' );
