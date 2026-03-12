<?php
/**
 * API Handler for Planning Center Online
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PCO_API_Handler {

    private $app_id;
    private $secret;
    private $enabled_calendars;
    private $base_url = 'https://api.planningcenteronline.com/calendar/v2/';

    public function __construct() {
        $options = get_option( 'pco_calendar_settings' );
        $this->app_id = isset( $options['app_id'] ) ? $options['app_id'] : '';
        $this->secret = isset( $options['secret'] ) ? $options['secret'] : '';
        $this->enabled_calendars = isset( $options['enabled_calendars'] ) ? (array)$options['enabled_calendars'] : array();
    }

    private function get_auth_header() {
        return array(
            'Authorization' => 'Basic ' . base64_encode( $this->app_id . ':' . $this->secret ),
            'User-Agent'    => 'FUMCWL-PCO-Calendar-Plugin'
        );
    }

    public function get_calendars( $no_cache = false, $all = false ) {
        $cache_key = 'pco_calendars_cache';
        $cached_data = $no_cache ? false : get_transient( $cache_key );

        if ( $cached_data === false ) {
            $response = wp_remote_get( $this->base_url . 'calendars', array(
                'headers' => $this->get_auth_header()
            ) );

            if ( is_wp_error( $response ) ) return $response;

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                return new WP_Error('pco_api_error', 'Calendars: API error ' . $code);
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            if ( !isset( $data['data'] ) ) return new WP_Error( 'pco_api_error', 'Invalid calendars response' );
            
            $cached_data = $data['data'];
            set_transient( $cache_key, $cached_data, HOUR_IN_SECONDS );
        }

        if ( !$all && !empty($this->enabled_calendars) ) {
            $filtered = array_filter($cached_data, function($cal) {
                return in_array($cal['id'], $this->enabled_calendars);
            });
            return array_values($filtered);
        }

        return $cached_data;
    }

    public function get_events() {
        $cache_key = 'pco_events_cache';
        $cached_data = get_transient( $cache_key );

        if ( $cached_data !== false ) {
            return $cached_data;
        }

        $args = array(
            'filter'  => 'future,published',
            'include' => 'event,calendar,event.calendar,event.tags,resource_bookings.resource',
            'order'   => 'starts_at',
            'per_page' => 100
        );

        if ( !empty($this->enabled_calendars) ) {
            $args['where[calendar_id]'] = implode(',', $this->enabled_calendars);
        }

        $url = add_query_arg( $args, $this->base_url . 'event_instances' );

        $response = wp_remote_get( $url, array(
            'headers' => $this->get_auth_header()
        ) );

        if ( is_wp_error( $response ) ) return $response;

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new WP_Error('pco_api_error', 'Events: API error ' . $code);
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['data'] ) ) {
            set_transient( $cache_key, $data, HOUR_IN_SECONDS );
            return $data;
        }

        return new WP_Error( 'pco_api_error', 'Invalid events response from PCO API' );
    }

    public function get_tags() {
        $cache_key = 'pco_tags_cache';
        $cached_data = get_transient( $cache_key );

        if ( $cached_data === false ) {
            $response = wp_remote_get( $this->base_url . 'tags', array(
                'headers' => $this->get_auth_header()
            ) );

            if ( is_wp_error( $response ) ) return $response;

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                return new WP_Error('pco_api_error', 'Tags: API error ' . $code);
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            if ( !isset( $data['data'] ) ) return new WP_Error( 'pco_api_error', 'Invalid tags response' );
            
            $cached_data = $data['data'];
            set_transient( $cache_key, $cached_data, DAY_IN_SECONDS );
        }

        return $cached_data;
    }
}
