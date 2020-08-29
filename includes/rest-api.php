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
        $params = $request->get_params();
        $sites = Site_Link_System::get_list_of_sites_by_type(['network_dashboard_sending'], 'post_ids');
        if ( empty( $sites ) ) {
            return new WP_Error(__METHOD__, 'No sites configured for Network Dashboard', [ 'status' => 418 ]);
        }

        // @todo test if all required fields are present
//        if ( ! ( isset( $params['action'] ) && ! empty( $params['action' ] ) )  ) {
//            dt_write_log( new WP_Error(__METHOD__, 'Required parameter missing.' ) );
//            return false;
//        }
        /**
         * Expects:
         * $params['action'] (required)
         * $params['group_size'] (optional)
         */

        // @todo sanitize submitted fields.


        // @todo post to network dashboard
        /**
         * Post Activity Log
         *
         * @param $data
         *
         * @example
         * $data = [
            [
                'site_id' => dt_network_site_id(),
                'action' => 'action',
                'category' => 'complete',
                'location_type' => 'complete', // ip, grid, lnglat
                'location_value' => [
                    'lng' => '-104.968',
                    'lat' => '39.7075',
                    'level' => 'admin2',
                    'label' => 'Denver, Colorado, US',
                    'grid_id' => '100364508'
                ], // ip, grid, lnglat
                'payload' => [
                    'initials' => 'CC',
                    'group_size' => '3',
                    'country' => 'United States',
                    'language' => 'en',
                    'note' => 'This is the full note'.time()
                ],
                'timestamp' => time()
            ]
        ];
         */
        $data = [
            [
                'site_id' => dt_network_site_id(),
                'action' => $params['action'],
                'category' => $params['category'],
                'location_type' => 'complete', // ip, grid, lnglat
                'location_value' => [
                    'lng' => '-104.968',
                    'lat' => '39.7075',
                    'level' => 'admin2',
                    'label' => 'Denver, Colorado, US',
                    'grid_id' => '100364508'
                ], // ip, grid, lnglat
                'payload' => $params['values'],
                'timestamp' => time()
            ]
        ];


        foreach( $sites as $site ) {
            $site_vars = Site_Link_System::get_site_connection_vars( $site );

            $args = [
                'method' => 'POST',
                'body' => [
                    'transfer_token' => $site_vars['transfer_token'],
                    'data' => $data
                ]
            ];
            $response = wp_remote_post( 'https://' . $site_vars['url'] . '/wp-content/plugins/disciple-tools-network-dashboard/activity/log.php', $args );

            dt_write_log('remote post');
            if ( ! is_wp_error( $response ) ) {
                dt_write_log( json_decode( $response['body'], true ) );
            } else {
                dt_write_log($response);
                dt_write_log($site_vars);
            }
        }


        return $params;
    }

}
DT_ND_Remote_Endpoints::instance();
