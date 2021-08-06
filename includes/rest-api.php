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
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            'dt-public/v1', '/network_dashboard/profile', [
                'methods'  => 'POST',
                'callback' => [ $this, 'profile' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public function rest_log( WP_REST_Request $request ) {
        /* VERIFY REQUIRED PARAMS AND SANITIZE */
        $params = $request->get_params();
        return Network_Dashboard_Remote_Log::log( $params );
    }

    public function profile( WP_REST_Request $request ) {
        $params = $this->process_token( $request );
        if ( is_wp_error( $params ) ) {
            return $params;
        }
        return dt_remote_network_site_profile();
    }
    /**
     * Process the standard security checks on an api request to network endpoints.
     * @param \WP_REST_Request $request
     *
     * @return array|\WP_Error
     */
    public function process_token( WP_REST_Request $request ) {

        $params = $request->get_params();

        // required token parameter challenge
        if ( ! isset( $params['transfer_token'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters.' );
        }

        $valid_token = Site_Link_System::verify_transfer_token( $params['transfer_token'] );

        // required valid token challenge
        if ( ! $valid_token ) {
            dt_write_log( $valid_token );
            return new WP_Error( __METHOD__, 'Invalid transfer token' );
        }

        // Add post id for site to site link
        $decrypted_key = Site_Link_System::decrypt_transfer_token( $params['transfer_token'] );
        $keys = Site_Link_System::get_site_keys();
        $params['site_post_id'] = $keys[$decrypted_key]['post_id'];

        return $params;
    }
}
DT_ND_Remote_Endpoints::instance();
