<?php
class WRCJ_Tracker {
    public static function init() {
        add_action( 'wp_ajax_wrcj_save_progress', array( __CLASS__, 'save_progress' ) );
        add_action( 'wp_ajax_nopriv_wrcj_save_progress', array( __CLASS__, 'deny_guest' ) );
    }

    public static function deny_guest() {
        wp_send_json_error( array( 'message' => 'Login required' ), 401 );
    }

    public static function save_progress() {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Not logged in' ), 401 );
        }

        // === WRPA ACCESS VALIDATION ===
        $plan   = (string) get_user_meta( $user_id, 'wrpa_active_plan', true );
        $expiry = (int) get_user_meta( $user_id, 'wrpa_access_expiry', true );
        $valid  = in_array( strtolower( $plan ), array( 'trial', 'monthly', 'annual' ), true )
                  && $expiry && current_time( 'timestamp' ) < $expiry;

        if ( ! $valid ) {
            wp_send_json_error( array( 'message' => 'Access expired or no valid plan' ), 403 );
        }

        // === Record progress ===
        $post_id  = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $position = isset( $_POST['position'] ) ? sanitize_text_field( wp_unslash( $_POST['position'] ) ) : '';
        $type     = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Invalid post ID' ), 400 );
        }

        update_user_meta(
            $user_id,
            '_wrcj_progress_' . $post_id,
            array(
                'type'     => $type,
                'position' => $position,
                'updated'  => current_time( 'mysql' ),
            )
        );

        wp_send_json_success( array( 'message' => 'Progress saved' ) );
    }
}
