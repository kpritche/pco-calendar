# PCO Agenda Calendar - Developer Context

This project is a WordPress plugin designed to fetch and display events from the Planning Center Online (PCO) Calendar API in a filterable agenda/list view.

## Project Overview
- **Type:** WordPress Plugin
- **Main Technologies:** PHP, JavaScript (jQuery), WordPress Plugin API, PCO API v2.
- **Core Functionality:** 
    - Fetches events and calendars from Planning Center Online.
    - Provides a settings page in the WordPress admin dashboard for API configuration.
    - Displays a filterable event list via the `[pco_agenda]` shortcode.
    - Implements server-side caching (1-hour TTL) to optimize performance and respect API rate limits.

## Technical Architecture

### Backend (PHP)
- **`pco-calendar.php`**: The main plugin entry point. Handles asset enqueuing, initialization, and the AJAX endpoint (`pco_fetch_events`) used by the frontend.
- **`includes/api-handler.php`**: A dedicated class (`PCO_API_Handler`) for managing communication with the PCO API. It handles authentication (Basic Auth), request construction, and caching via WordPress transients (`pco_events_cache`, `pco_calendars_cache`).
- **`includes/admin-settings.php`**: Defines the settings page under **Settings > PCO Calendar**. It allows users to input their Application ID/Secret and select which calendars to enable.
- **`includes/shortcode.php`**: Registers the `[pco_agenda]` shortcode and provides the base HTML structure for the calendar.

### Frontend (JS/CSS)
- **`assets/js/pco-calendar.js`**: Orchestrates the frontend experience. It fetches data via AJAX, maps PCO API responses (including nested relationships for events and calendars), and handles the rendering of filter chips and event cards.
- **`assets/css/pco-calendar.css`**: Contains all styling for the agenda view. It uses CSS variables for easy customization of colors and branding.

## Development Workflow

### Installation & Setup
1. Zip the repository and upload it as a WordPress plugin.
2. Navigate to **Settings > PCO Calendar** to enter PCO API credentials.
3. Use the `[pco_agenda]` shortcode on any WordPress page.

### Testing
- **Manual Testing:** Best tested within a live or staging WordPress environment with valid PCO API credentials.
- **Caching:** You can manually clear the plugin's cache via the "Clear Plugin Cache" button on the settings page during development.

### Conventions
- **Versioning:** Always increment the version number in `pco-calendar.php` (plugin header) when implementing new features or significant changes.
- **Security:** Never expose the PCO Secret to the client. All API requests must be proxied through the backend AJAX handler.
- **Performance:** Use WordPress transients for any external API requests.
- **Styling:** Adhere to the established CSS variables in `pco-calendar.css` for brand consistency.

## Key Files
- `pco-calendar.php`: Plugin bootstrap and AJAX logic.
- `includes/api-handler.php`: Core API communication logic.
- `assets/js/pco-calendar.js`: Client-side rendering and filtering logic.
- `assets/css/pco-calendar.css`: UI styling and theme variables.
