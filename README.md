# PCO Agenda Calendar Implementation Guide

This guide will walk you through installing and configuring your custom Planning Center Online (PCO) Calendar plugin on your WordPress site.

## Step 1: Upload and Activate
1.  Compress the `pco-calendar` folder into a `.zip` file.
2.  In your WordPress Dashboard, go to **Plugins > Add New**.
3.  Click **Upload Plugin** and select the `.zip` file you created.
4.  Click **Install Now** and then **Activate**.

## Step 2: Configure API Credentials
1.  In your WordPress Dashboard, go to **Settings > PCO Calendar**.
2.  Enter your **Application ID** and **Secret**. 
    *   *If you don't have these, log in to Planning Center, go to your Profile, then Developers, and create a New Personal Access Token.*
3.  Click **Save Changes**.

## Step 3: Set Default Calendars
1.  Once you have saved your credentials, the settings page will refresh and display a list of all your Planning Center Calendars.
2.  Check the boxes for the calendars you want to be **active by default** when a visitor first lands on your page.
3.  Click **Save Changes** again.

## Step 4: Add the Calendar to Your Site
1.  Navigate to the page where you want the calendar to appear.
2.  Insert a **Shortcode Block** (if using Gutenberg) or simply paste the following shortcode into the editor:
    `[pco_agenda]`
3.  Update/Publish the page.

## Customizing the Theme
The plugin uses CSS variables to manage branding. If you ever want to change the colors, you can do so in `assets/css/pco-calendar.css`.

Currently, it is set to your specified palette:
*   **Primary:** #16463e
*   **Accent:** #51bf9b
*   **Orange:** #ff7f30
*   **Blue:** #6fcfeb
*   **Tan:** #cda787

## Technical Notes
*   **Caching:** The plugin fetches new data from Planning Center every **1 hour** to keep your site fast and avoid hitting PCO's API rate limits.
*   **Security:** Your PCO Secret is stored securely in the WordPress database and is **never** sent to the visitor's browser. The browser only talks to your WordPress server, which then talks to Planning Center.
