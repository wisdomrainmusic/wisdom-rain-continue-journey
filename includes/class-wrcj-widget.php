<?php
class WRCJ_Widget {
    public static function init() {
        add_shortcode( 'wr_continue_journey', array( __CLASS__, 'render' ) );
    }

    public static function render() {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $user_id = get_current_user_id();
        $meta    = get_user_meta( $user_id );

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

        usort(
            $items,
            function ( $a, $b ) {
                return $b['updated'] <=> $a['updated'];
            }
        );

        $items = array_slice( $items, 0, 5 );

        ob_start();
        ?>
        <section class="wr-continue-section">
            <h2><?php echo esc_html__( 'Continue Your Journey', 'wrcj' ); ?></h2>
            <?php if ( empty( $items ) ) : ?>
                <p style="text-align:center;color:#aaa;">
                    <?php echo esc_html__( 'No recent activity yet — start exploring your premium library ✨', 'wrcj' ); ?>
                </p>
            <?php else : ?>
                <div class="wr-continue-grid">
                    <?php
                    foreach ( $items as $item ) {
                        $post = get_post( $item['post_id'] );

                        if ( ! $post instanceof WP_Post || 'trash' === $post->post_status ) {
                            continue;
                        }

                        $permalink = get_permalink( $post );

                        if ( ! $permalink ) {
                            continue;
                        }

                        $thumbnail = get_the_post_thumbnail( $post, 'medium' );
                        ?>
                        <a class="wr-continue-card" href="<?php echo esc_url( $permalink ); ?>">
                            <?php if ( $thumbnail ) : ?>
                                <?php echo wp_kses_post( $thumbnail ); ?>
                            <?php endif; ?>
                            <div class="wr-info">
                                <h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
                                <?php if ( $item['type'] || $item['position'] ) : ?>
                                    <p><?php echo esc_html( self::format_progress_summary( $item['type'], $item['position'] ) ); ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                    ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return trim( ob_get_clean() );
    }

    private static function format_progress_summary( $type, $position ) {
        $label = $type ? ucfirst( $type ) : '';

        if ( $label && $position ) {
            return sprintf( '%1$s — %2$s', $label, $position );
        }

        return $label ? $label : $position;
    }
}
