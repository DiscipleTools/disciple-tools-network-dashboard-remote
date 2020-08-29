<?php
/**
 * Sets up short code option of adding tracking to a Wordpress page
 */
add_shortcode('movement_logging', 'dt_nd_movement_logging_shortcode');
function dt_nd_movement_logging_shortcode( $params = [] ){

    if ( isset( $params['value'] ) ) {
        $params['values'] = [ 'value' => $params['value'] ];
    }

    wp_parse_args( $params, [
        'action' => 'page_view',
        'category' => 'general',
        'values' => [],
        'scrolled' => 'no',
    ] );

    $values = json_encode( $params['values'] );

    // @todo sanitize submission

    if ( "yes" === $params['scrolled'] ) {
        return "<script>
            window.has_scrolled = 0
            jQuery(document).on('scroll', function(){
                if (jQuery(document).scrollTop() >= 200 && window.has_scrolled < 1 ) {
                    window.movement_logging({
                        action: \"". $params['action'] . "\", category: \"". $params['category'] ."\", values: ". $values ."
                     })
                     window.has_scrolled++
                 }
            })
            </script>";
    } else {
        return "<script>
            jQuery(document).ready(function(){
                window.movement_logging({
                    action: \"". $params['action'] . "\", category: \"". $params['category'] ."\", values: ". $values ."
                 })
            })
            </script>";
    }
}
