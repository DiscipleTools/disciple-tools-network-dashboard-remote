<?php

if ( ! is_admin() ){
    add_action( 'wp_head', 'dt_nd_logging_script' );
}
function dt_nd_logging_script(){
    ?>
    <script>
        window.movement_logging = ( args ) => {
            jQuery.ajax({
                type: "POST",
                data: JSON.stringify(args),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                url: '<?php echo esc_url_raw( rest_url() ) ?>movement_logging/v1/log',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' ) ?>' );
                },
            })
                .done(function(response){
                    console.log(response)
                })
        }
    </script>
    <?php
}
