<?php

class Network_Dashboard_Remote_Log {
    public static function log( $params ) {
        $sites = Site_Link_System::get_list_of_sites_by_type(['network_dashboard_sending'], 'post_ids');
        if ( empty( $sites ) ) {
            return new WP_Error(__METHOD__, 'No sites configured for Network Dashboard', [ 'status' => 418 ]);
        }

        if ( ! isset( $params['action'] ) || empty( $params['action'] ) ) {
            return new WP_Error(__METHOD__, 'No action parameter found', [ 'status' => 400 ]);
        }
        if ( ! isset( $params['category'] ) || empty( $params['category'] ) ) {
            return new WP_Error(__METHOD__, 'No action parameter found', [ 'status' => 400 ]);
        }

        /* BUILD DATA PACKET FOR POSTING */
        $data = [
            [
                'site_id' => dt_network_site_id(),
                'action' => $params['action'],
                'category' => $params['category'],
                'location_type' => '',
                'location_value' => '',
                'payload' => [],
                'timestamp' => time()
            ]
        ];

        // extra data- variables
        $keys = array_keys($params);
        foreach( $keys as $key ){
            if ( 'data' === substr( $key, 0, 4 ) ){
                $explode = explode('-', $key );
                if ( ! isset( $explode[1] ) ) {
                    continue;
                }
                $data[0]['payload'][$explode[1]] = $params[$key];
            }
        }

        // set location info (ip, grid, complete)
        if ( isset( $params['location_type'] ) && 'complete' === $params['location_type'] ) {
            $data[0]['location_type'] = 'complete';
            $default_values = [
                'lng' => '',
                'lat' => '',
                'level' => '',
                'label' => '',
                'grid_id' => ''
            ];
            $data[0]['location_value'] = wp_parse_args($params['location_type'], $default_values );
        }
        else if ( isset( $params['location_type'] ) && 'grid' === $params['location_type'] ) {
            $data[0]['location_type'] = 'grid';
            $data[0]['location_value'] = $params['grid_id'];
        } else {
            $ip_address = self::get_real_ip_address();
            $data[0]['location_type'] = 'ip';
            $data[0]['location_value'] = $ip_address;
        }

        /* POST DATA TO ALL AVAILABLE NETWORK DASHBOARD CONNECTIONS */
        $reply = [];
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

            if ( ! is_wp_error( $response ) ) {
                dt_write_log( json_decode( $response['body'], true ) );
                $reply[] = json_decode( $response['body'], true );
            } else {
                dt_write_log($response);
                dt_write_log($site_vars);
                $reply[] = true;
            }
        }
        return $reply;
    }

    public static function get_real_ip_address() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )   //check ip from share internet
        {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } else if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )   //to check ip is pass from proxy
        {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } else if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return $ip;
    }
}
