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

add_action( 'wp_footer', function () {
    if ( ! is_singular() ) {
        return;
    }

    global $post;

    if ( ! $post || empty( $post->ID ) ) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pid = '<?php echo esc_js( $post->ID ); ?>';
            console.log('🪶 Binding WRCJ ID:', pid);

            // 🎧 AUDIO – detect multiple variants
            const audioSelectors = [
                '.wrap-audio-player audio',
                '.wr-audio-player audio',
                'audio[id*="wrp"]',
                'audio[id*="wrap"]'
            ];
            audioSelectors.forEach(sel => {
                document.querySelectorAll(sel).forEach(a => {
                    if (!a.dataset.trackId) {
                        a.dataset.trackId = pid;
                        console.log('🎧 Bound audio →', sel, pid);
                    }
                });
            });

            // 📖 PDF – detect iframe/modal variations
            const pdfSelectors = [
                '#wrp-modal-content',
                '[id^="wrp-modal"] iframe',
                '[id*="wrp-pdf"]',
                '.wrp-pdf-viewer iframe'
            ];
            pdfSelectors.forEach(sel => {
                document.querySelectorAll(sel).forEach(p => {
                    if (!p.dataset.wrPdfId) {
                        p.dataset.wrPdfId = pid;
                        console.log('📖 Bound PDF →', sel, pid);
                    }
                });
            });
        });
    </script>
    <?php
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
