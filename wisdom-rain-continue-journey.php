<?php
/**
 * Plugin Name: Wisdom Rain Continue Journey
 * Description: Netflix-style personalized progress tracker for WRPA members.
 * Version: 1.0.0
 * Author: Wisdom Rain Dev Team
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wrcj-tracker.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wrcj-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wrcj-rest.php';

add_action( 'plugins_loaded', function () {
    WRCJ_Tracker::init();
    WRCJ_Widget::init();
    WRCJ_REST::init();
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'wrcj-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/wrcj-style.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'wrcj-tracker',
        plugin_dir_url( __FILE__ ) . 'assets/js/wrcj-tracker.js',
        array(),
        '1.0.0',
        true
    );

    wp_localize_script(
        'wrcj-tracker',
        'wrcjAjax',
        array( 'url' => admin_url( 'admin-ajax.php' ) )
    );
} );
