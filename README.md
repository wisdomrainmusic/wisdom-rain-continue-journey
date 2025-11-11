# Wisdom Rain Continue Journey

Netflix-style Continue Reading & Listening tracker for Wisdom Rain Premium Access users.

## Features

- Tracks in-progress reading and listening experiences per user.
- Stores progress in user meta for quick retrieval without custom tables.
- Provides an AJAX endpoint (`wrcj_save_progress`) to persist a member's current position while they browse.
- Exposes a REST endpoint (`/wp-json/wrcj/v1/recent`) so native and headless clients can sync the member's recent items.

## Usage

1. Upload the plugin directory to `wp-content/plugins` and activate it in the WordPress admin.
2. Call the `wrcj_save_progress` AJAX action from the front-end when a user finishes a session:

   ```http
   POST /wp-admin/admin-ajax.php?action=wrcj_save_progress
   post_id=42
   type=course
   position=65
   ```

3. The tracker saves the progress to user meta under keys prefixed with `_wrcj_progress_` for easy retrieval in templates.

4. Drop the `[wr_continue_journey]` shortcode into any page or template part to render the "Continue Your Journey" panel for the current member. The widget surfaces the five most recent experiences with saved progress.

5. Fetch `GET /wp-json/wrcj/v1/recent` from authenticated clients to retrieve a JSON payload of the member's five latest progress entries, including titles, links, and featured image URLs.

Only authenticated users can record progress; unauthenticated requests receive an error response.
