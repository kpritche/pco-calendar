<?php
/**
 * Admin Settings for PCO Calendar Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'pco_calendar_add_admin_menu' );

function pco_calendar_add_admin_menu() {
	add_options_page(
		'PCO Calendar Settings',
		'PCO Calendar',
		'manage_options',
		'pco-calendar',
		'pco_calendar_settings_page'
	);
}

add_action( 'admin_init', 'pco_calendar_settings_init' );

function pco_calendar_settings_init() {
	register_setting( 'pco_calendar_group', 'pco_calendar_settings' );

	add_settings_section(
		'pco_calendar_main_section',
		'PCO Connection & Configuration',
		'pco_calendar_section_callback',
		'pco-calendar'
	);

	add_settings_field(
		'pco_app_id',
		'Application ID',
		'pco_app_id_render',
		'pco-calendar',
		'pco_calendar_main_section'
	);

	add_settings_field(
		'pco_secret',
		'Secret',
		'pco_secret_render',
		'pco-calendar',
		'pco_calendar_main_section'
	);

    add_settings_field(
		'pco_enabled_calendars',
		'Enabled Calendars',
		'pco_enabled_calendars_render',
		'pco-calendar',
		'pco_calendar_main_section'
	);

    add_settings_field(
		'pco_default_calendars',
		'Active Calendars By Default',
		'pco_default_calendars_render',
		'pco-calendar',
		'pco_calendar_main_section'
	);

    add_settings_field(
		'pco_standout_tags',
		'Standout Tags',
		'pco_standout_tags_render',
		'pco-calendar',
		'pco_calendar_main_section'
	);
}

function pco_calendar_section_callback() {
	echo 'Enter your Planning Center Online API credentials below.';
}

function pco_app_id_render() {
	$options = get_option( 'pco_calendar_settings' );
	$val = isset($options['app_id']) ? $options['app_id'] : '';
	echo "<input type='text' name='pco_calendar_settings[app_id]' value='".esc_attr($val)."' size='50'>";
}

function pco_secret_render() {
	$options = get_option( 'pco_calendar_settings' );
	$val = isset($options['secret']) ? $options['secret'] : '';
	echo "<input type='password' name='pco_calendar_settings[secret]' value='".esc_attr($val)."' size='50'>";
}

function pco_enabled_calendars_render() {
	$options = get_option( 'pco_calendar_settings' );
    $api = new PCO_API_Handler();
    $calendars = $api->get_calendars(true, true);

    if ( is_wp_error( $calendars ) ) {
        echo '<p style="color:red;"><b>Connection Error:</b> ' . esc_html($calendars->get_error_message()) . '</p>';
        return;
    }

    echo '<p class="description">Select which calendars to pull from PCO.</p>';
    $enabled = isset($options['enabled_calendars']) ? (array)$options['enabled_calendars'] : array();
    foreach ( $calendars as $calendar ) {
        $id = $calendar['id'];
        $name = $calendar['attributes']['name'];
        $checked = in_array($id, $enabled) ? 'checked' : '';
        echo "<label><input type='checkbox' name='pco_calendar_settings[enabled_calendars][]' value='$id' $checked> $name</label><br>";
    }
}

function pco_default_calendars_render() {
	$options = get_option( 'pco_calendar_settings' );
    $api = new PCO_API_Handler();
    $calendars = $api->get_calendars(true);

    if ( is_wp_error( $calendars ) || empty($calendars) ) {
        echo '<p>Save your enabled calendars first.</p>';
        return;
    }

    $defaults = isset($options['default_calendars']) ? (array)$options['default_calendars'] : array();
    foreach ( $calendars as $calendar ) {
        $id = $calendar['id'];
        $name = $calendar['attributes']['name'];
        $checked = in_array($id, $defaults) ? 'checked' : '';
        echo "<label><input type='checkbox' name='pco_calendar_settings[default_calendars][]' value='$id' $checked> $name</label><br>";
    }
}

function pco_standout_tags_render() {
	$options = get_option( 'pco_calendar_settings' );
    $api = new PCO_API_Handler();
    $tags = $api->get_tags();

    if ( is_wp_error( $tags ) ) {
        echo '<p>Could not load tags: ' . esc_html($tags->get_error_message()) . '</p>';
        return;
    }

    $standout = isset($options['standout_tags']) ? (array)$options['standout_tags'] : array();
    echo '<p class="description">Select tags that should make events stand out (e.g., Worship Service).</p>';
    foreach ( $tags as $tag ) {
        $id = $tag['id'];
        $name = $tag['attributes']['name'];
        $checked = in_array($id, $standout) ? 'checked' : '';
        echo "<label><input type='checkbox' name='pco_calendar_settings[standout_tags][]' value='$id' $checked> $name</label><br>";
    }
}

function pco_calendar_settings_page() {
    // Handle manual cache clear
    if ( isset($_POST['pco_clear_cache']) && check_admin_referer('pco_clear_cache_action') ) {
        delete_transient('pco_calendars_cache');
        delete_transient('pco_events_cache');
        delete_transient('pco_tags_cache');
        echo '<div class="updated"><p>Cache cleared successfully!</p></div>';
    }
	?>
	<div class="wrap">
        <h2>PCO Agenda Calendar Settings</h2>
        <form action='options.php' method='post'>
            <?php
            settings_fields( 'pco_calendar_group' );
            do_settings_sections( 'pco-calendar' );
            submit_button();
            ?>
        </form>

        <hr>
        <h3>Maintenance</h3>
        <form method="post" action="">
            <?php wp_nonce_field('pco_clear_cache_action'); ?>
            <p>If your events aren't updating, try clearing the internal cache.</p>
            <input type="submit" name="pco_clear_cache" class="button button-secondary" value="Clear Plugin Cache">
        </form>
    </div>
	<?php
}
