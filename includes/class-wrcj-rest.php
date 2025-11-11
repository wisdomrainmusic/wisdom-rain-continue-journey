<?php
/**
 * REST endpoints for exposing member journey progress.
 */
class WRCJ_REST {
    /**
     * Boot the REST route registration.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register plugin-specific routes.
     */
    public static function register_routes() {
        register_rest_route(
            'wrcj/v1',
            '/recent',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_recent' ),
                'permission_callback' => array( __CLASS__, 'ensure_authenticated_user' ),
            )
        );
    }

    /**
     * Ensure the current request is made by an authenticated user.
     *
     * @return bool
     */
    public static function ensure_authenticated_user() {
        return is_user_logged_in();
    }

    /**
     * Return the five most recent progress entries for the active member.
     *
     * @param WP_REST_Request $request Incoming request instance.
     *
     * @return WP_REST_Response
     */
    public static function get_recent( WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return rest_ensure_response( array() );
        }

        $items = self::get_recent_progress_items( $user_id );
        $data  = array();

        foreach ( $items as $item ) {
            $post = get_post( $item['post_id'] );

            if ( ! $post instanceof WP_Post || 'trash' === $post->post_status ) {
                continue;
            }

            $permalink = get_permalink( $post );

            if ( ! $permalink ) {
                continue;
            }

            $data[] = array(
                'id'        => (int) $post->ID,
                'title'     => get_the_title( $post ),
                'link'      => esc_url_raw( $permalink ),
                'type'      => $item['type'],
                'position'  => $item['position'],
                'thumbnail' => esc_url_raw( get_the_post_thumbnail_url( $post, 'medium' ) ),
            );
        }

        return rest_ensure_response( $data );
    }

    /**
     * Collect and normalize saved progress metadata for the provided user.
     *
     * @param int $user_id User identifier.
     *
     * @return array
     */
    private static function get_recent_progress_items( $user_id ) {
        $meta = get_user_meta( $user_id );

        if ( empty( $meta ) ) {
            return array();
        }

        $items = array();

        foreach ( $meta as $key => $values ) {
            if ( 0 !== strpos( $key, '_wrcj_progress_' ) ) {
                continue;
            }

            $post_id = intval( substr( $key, strlen( '_wrcj_progress_' ) ) );

            if ( $post_id <= 0 ) {
                continue;
            }

            $stored = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : array();

            if ( ! is_array( $stored ) ) {
                continue;
            }

            $items[] = array(
                'post_id'  => $post_id,
                'type'     => isset( $stored['type'] ) ? sanitize_text_field( $stored['type'] ) : '',
                'position' => isset( $stored['position'] ) ? sanitize_text_field( $stored['position'] ) : '',
                'updated'  => isset( $stored['updated'] ) ? strtotime( $stored['updated'] ) : 0,
            );
        }

        if ( empty( $items ) ) {
            return array();
        }

        usort(
            $items,
            function ( $a, $b ) {
                return $b['updated'] <=> $a['updated'];
            }
        );

        return array_slice( $items, 0, 5 );
    }
}
