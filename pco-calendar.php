<?php
/**
 * Plugin Name: PCO Agenda Calendar
 * Description: A filterable list/agenda view for Planning Center Online (PCO) Calendar events.
 * Version: 1.5.0
 * Author: Kory Pritchett
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'PCO_CALENDAR_PATH', plugin_dir_path( __FILE__ ) );
define( 'PCO_CALENDAR_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once PCO_CALENDAR_PATH . 'includes/admin-settings.php';
require_once PCO_CALENDAR_PATH . 'includes/api-handler.php';
require_once PCO_CALENDAR_PATH . 'includes/shortcode.php';

// Register activation hook to set default options if needed
register_activation_hook( __FILE__, 'pco_calendar_activate' );

function pco_calendar_activate() {
	if ( ! get_option( 'pco_calendar_settings' ) ) {
		update_option( 'pco_calendar_settings', array(
			'app_id'            => '',
			'secret'            => '',
			'default_calendars' => array(),
			'enabled_calendars' => array(),
			'standout_tags'     => array(),
		) );
	}
}

// Enqueue styles and scripts
add_action( 'wp_enqueue_scripts', 'pco_calendar_enqueue_assets' );

function pco_calendar_enqueue_assets() {
    wp_enqueue_style( 'pco-calendar-style', PCO_CALENDAR_URL . 'assets/css/pco-calendar.css', array(), '1.0.0' );
    wp_enqueue_script( 'pco-calendar-script', PCO_CALENDAR_URL . 'assets/js/pco-calendar.js', array( 'jquery' ), '1.0.0', true );

    // Pass settings to JS
    $settings = get_option( 'pco_calendar_settings' );
    wp_localize_script( 'pco-calendar-script', 'pcoSettings', array(
        'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
        'defaultCalendars' => isset($settings['default_calendars']) ? $settings['default_calendars'] : array(),
        'standoutTags'     => isset($settings['standout_tags']) ? $settings['standout_tags'] : array(),
    ) );
}

// AJAX handler for fetching events (to avoid CORS and keep secrets on backend)
add_action( 'wp_ajax_pco_fetch_events', 'pco_handle_ajax_fetch_events' );
add_action( 'wp_ajax_nopriv_pco_fetch_events', 'pco_handle_ajax_fetch_events' );

function pco_handle_ajax_fetch_events() {
    error_log('PCO Calendar: Starting AJAX fetch...');
    $api = new PCO_API_Handler();
    
    $calendars = $api->get_calendars();
    if ( is_wp_error( $calendars ) ) {
        error_log('PCO Calendar Error (Calendars): ' . $calendars->get_error_message());
        wp_send_json_error( 'Calendars error: ' . $calendars->get_error_message() );
    }

    $events = $api->get_events();
    if ( is_wp_error( $events ) ) {
        error_log('PCO Calendar Error (Events): ' . $events->get_error_message());
        wp_send_json_error( 'Events error: ' . $events->get_error_message() );
    }

    error_log('PCO Calendar: Fetch successful. Found ' . count($calendars) . ' calendars.');
    wp_send_json_success( array(
        'events'    => $events,
        'calendars' => array_values($calendars), // Ensure array keys are sequential for JS
    ) );
}
