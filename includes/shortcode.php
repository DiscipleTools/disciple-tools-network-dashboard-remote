<?php
/**
 * Sets up short code option of adding tracking to a Wordpress page
 */
add_shortcode( 'movement_logging', 'dt_nd_movement_logging_shortcode' );
function dt_nd_movement_logging_shortcode( $params = [] ){

    // extra data- variables
    if ( ! isset( $params['action'] ) ){
        $params['action'] = 'page_view';
    }
    if ( ! isset( $params['action'] ) ){
        $params['category'] = 'general';
    }
    if ( ! isset( $params['scrolled'] ) ){
        $params['scrolled'] = 'no';
    }

    $params = array_map( 'sanitize_text_field', wp_unslash( $params ) );

    $json_array = json_encode( $params );

    if ( "yes" === $params['scrolled'] ) {
        return "<script>
            window.has_scrolled = 0
            jQuery(document).on('scroll', function(){
                if (jQuery(document).scrollTop() >= 200 && window.has_scrolled < 1 ) {
                    window.movement_logging(".$json_array.")
                     window.has_scrolled++
                 }
            })
            </script>";
    } else {
        return "<script>
            jQuery(document).ready(function(){
                window.movement_logging(".$json_array.")
            })
            </script>";
    }
}
