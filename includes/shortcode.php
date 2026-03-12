<?php
/**
 * Shortcode for PCO Agenda View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'pco_agenda', 'pco_calendar_agenda_shortcode' );

function pco_calendar_agenda_shortcode( $atts ) {
    ob_start();
    ?>
    <div id="pco-agenda-container" class="pco-agenda">
        <div id="pco-filters" class="pco-filters">
            <!-- Calendars will be loaded here -->
            <p>Loading calendars...</p>
        </div>
        <div id="pco-event-list" class="pco-event-list">
            <!-- Events will be loaded here -->
            <p>Loading events...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
