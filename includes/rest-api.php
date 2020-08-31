<?php
/**
 * Rest API example class
 */


class DT_ND_Remote_Endpoints
{
    public $permissions = [ 'view_any_contacts', 'view_project_metrics' ];

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }

    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'movement_logging/v1';

        register_rest_route(
            $namespace, '/log', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'rest_log' ],
                ],
            ]
        );
    }

    public function rest_log( WP_REST_Request $request ) {
        /* VERIFY REQUIRED PARAMS AND SANITIZE */
        $params = $request->get_params();
        return Network_Dashboard_Remote_Log::log( $params );
    }
}
DT_ND_Remote_Endpoints::instance();
