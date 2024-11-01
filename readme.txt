=== WPRaiz Content API Tool ===
Contributors: zeicaro
Donate link: https://ai.wpraiz.com.br
Tags: REST API, Content, Post Creation, SEO, Image Upload, Automation
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 1.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create WordPress posts via REST API with custom SEO fields, image uploads, primary category assignment, and integration with popular SEO plugins (SEOPress, Yoast SEO, and Rank Math).

== Description ==

The WPRaiz Content API Tool is a powerful plugin that enables the creation of WordPress posts programmatically through a REST API. Ideal for developers and websites that need seamless content integration from external systems, this plugin supports:

- **Integration with Major SEO Plugins**: Set custom SEO fields for titles and descriptions, compatible with SEOPress, Yoast SEO, and Rank Math.
- **Flexible Image Uploads**: Accept images via URL or Base64 encoding, with automatic attachment as the featured image.
- **Automatic Category Management**: Assign an existing category or create a new one based on supplied category names.
- **Installation Check Endpoint**: Verify plugin installation, authentication, and detect the installed SEO plugin.

== Installation ==

1. Download the plugin zip file.
2. In your WordPress dashboard, go to **Plugins > Add New**.
3. Click on **Upload Plugin** and choose the zip file you downloaded.
4. Click **Install Now** and activate the plugin.
5. Use the REST API endpoint `/wp-json/api-post-creator/v1/create-post` to start creating posts programmatically.

== Authentication ==

To authenticate API requests, you must use an application password, which can be generated from your user profile in the WordPress admin dashboard.

1. **Generate Application Password**: Go to **Users > Profile** in the WordPress dashboard and create an application password.
2. **Authorization Header**: Pass the application password in a Base64-encoded `Authorization` header in the format `Basic {base64_encode(username:application_password)}`.

== REST API Endpoints ==

1. **Create Post Endpoint**  
   - **URL**: `/wp-json/api-post-creator/v1/create-post`
   - **Method**: POST
   - **Parameters**:
     - `title` (required): Title of the post.
     - `content` (required): Content of the post.
     - `status` (optional): Status of the post (default is draft).
     - `primary_category` (optional): Name of the primary category.
     - `seopress_title` (optional): Custom SEO title (SEOPress).
     - `seopress_desc` (optional): Custom SEO description (SEOPress).
     - `image_url` (optional): URL for the featured image.

2. **Check Installation Endpoint**  
   - **URL**: `/wp-json/api-post-creator/v1/check`
   - **Method**: GET
   - **Purpose**: Validates plugin installation, authentication, and returns the installed SEO plugin (SEOPress, Yoast SEO, or Rank Math).

== Frequently Asked Questions ==

= What is the REST API endpoint to create posts? =
The main endpoint to create posts is `/wp-json/api-post-creator/v1/create-post`, which requires a POST request with the necessary parameters.

= How do I check if the plugin is installed and authenticated? =
Use the `/wp-json/api-post-creator/v1/check` endpoint with a GET request to validate installation, check authentication, and detect the active SEO plugin.

= What parameters are required to create a post? =
The `title` and `content` fields are required. Optional fields include `status`, `primary_category`, SEO metadata (such as `seopress_title`), and `image_url`.

= What formats are supported for image uploads? =
You can upload images by providing a direct URL or Base64-encoded data. Supported formats are JPG, PNG, GIF, and JPEG.

= How do I handle authentication for API requests? =
Authentication requires an application password from the WordPress user profile, passed as a Basic Auth header.

= Can I add categories programmatically? =
Yes, the plugin automatically checks if the provided category exists. If it doesn’t, it will create it.

== Screenshots ==

1. **Settings Page** - Shows the WPRaiz Content API settings in the WordPress dashboard.
2. **API Request Example** - Example of a JSON payload used to create a post.
3. **Installation Check Response** - Shows the response for the check endpoint indicating the active SEO plugin and authentication status.

== Changelog ==

= 1.4 =
* Added SEO metadata integration for SEOPress, Yoast SEO, and Rank Math.
* Introduced `/check` endpoint to verify installation and authentication.
* Enhanced error handling and response messages.

= 1.3 =
* Improved compatibility with WordPress 6.6.
* Proper enqueuing of admin JavaScript and CSS files.
* Added support for automatic category creation.
* Enhanced image upload functionality.

= 1.2 =
* Added support for SEO metadata (SEOPress compatibility).
* Fixed image upload issues.

= 1.1 =
* Initial public release with basic post creation via REST API.

== Upgrade Notice ==

= 1.4 =
Critical update for SEO integration across multiple plugins and added verification endpoint. Please update to ensure full functionality.

== License ==
This plugin is licensed under GPLv3. See [GNU's official site](https://www.gnu.org/licenses/gpl-3.0.html) for details.

== Support ==
For questions or support, please visit [WPRaiz Support](https://ai.wpraiz.com.br).