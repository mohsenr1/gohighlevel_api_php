# GoHighLevel PHP Integration

This project provides two key functionalities that extend the GoHighLevel platform:

1. **Funnel Management**: Retrieves a list of all funnels and their steps, displays them in a table, and pushes the data to a Google Sheet with alternating row colors.
2. **Calendar Slot Management**: Retrieves free and booked slots for each calendar and calculates how many free slots are available for each agent in the coming days.

## Prerequisites

1. A GoHighLevel account.
2. Access to the [GoHighLevel API Marketplace](https://marketplace.gohighlevel.com/).
3. PHP 7.4 or later.
4. Composer installed on your system.
5. Google Sheets API credentials.

## Getting Started

### Step 1: Set Up GoHighLevel API

1. Log in to the [GoHighLevel API Marketplace](https://marketplace.gohighlevel.com/).
2. Create a new application.
3. Define the required scopes.
4. Generate the `Client ID` and `Client Secret`.
5. Add a `Redirect URI`. Use a placeholder like:
   - Redirect URI: `https://your_redirect_uri.com`

### Step 2: Configure the Application

1. Open the `ghl_api_conf.php` file.
2. Replace the placeholders with your actual credentials:
   ```php
   $client_id = 'your_client_id';
   $client_secret = 'your_client_secret';
   $redirect_uri = 'your_redirect_uri';
   $scope = 'XXXXXXXX';
   $g_client->setAccessToken('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
   $slots = get_slots_for_calendar('XXXXXXXXXXXXXXXXXXXX', $access_token, $locationID);
   $sheet_ID = 'XXXXXXXXXXXXXXXX-XXXXXXXXXXXXXXXXXXXXXXXXXXX';
   $sheet_name = "the_sheet_name";
   ```

3. Place the `refresh_token.php` script in a cron job to refresh tokens automatically. Access token expires ins 24 hours.

### Step 3: Set Up Google Sheets Integration

1. Open `credentials_placeholder.json`.
2. Replace the placeholders with your Google Sheets API credentials.
3. Share your Google Sheet with the email address provided in the Google Sheets API console.
4. Update the script with the following details:
   - Sheet URL
   - Sheet name

### Step 4: Install Dependencies

1. Navigate to the project directory.
2. Run the following commands:
   ```bash
   composer install
   composer update
   ```

### Step 5: Run the Scripts

1. **Funnel Management**:
   Run `funnels_complete.php` to retrieve funnel data and push it to Google Sheets.

2. **Calendar Slot Management**:
   Run `calendars_slots.php` to retrieve free and booked slots and push them to Google Sheets.

## Files Overview

- `ghl_api_conf.php`: Configuration file for GoHighLevel API.
- `refresh_token.php`: Script to refresh API tokens.
- `credentials_placeholder.json`: Placeholder for Google Sheets API credentials.
- `tokens_placeholder.json`: Placeholder for storing tokens.
- `funnels_complete.php`: Script for funnel management.
- `calendars_slots.php`: Script for calendar slot management.
- `composer.json` and `composer.lock`: Dependency management files.

## Notes

- Ensure your server supports cron jobs for token refresh.
- Make sure Google Sheets API is enabled and credentials are correctly set.

For more details on GoHighLevel API, refer to their [official documentation](https://highlevel.stoplight.io/docs/integrations/0443d7d1a4bd0-overview).

For more details on how to generate Google Sheet API credentials, follow this guide: [https://materialplus.srijan.net/resources/blog/integrating-google-sheets-with-php-is-this-easy-know-how
](https://materialplus.srijan.net/resources/blog/integrating-google-sheets-with-php-is-this-easy-know-how)


