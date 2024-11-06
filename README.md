# Content Restriction for WordPress®

Enables role-based content restriction by post type, taxonomy term, or individual post/page, redirecting unauthorized users to login when needed.

## Description

**Content Restriction for WordPress®** allows administrators to restrict access to content based on user roles. 

You can set restrictions globally for post types and taxonomy terms or individually for posts and pages. Unauthorized users will be redirected to the login page or see a customizable message if they try to access restricted content.

## Features

- **Restrict by Post Type or Taxonomy Term**: Globally restrict content based on post types and taxonomy terms.
- **Individual Post/Page Restrictions**: Restrict specific posts or pages with custom role requirements.
- **Role-Based Access Control**: Set minimum user roles required to view content.
- **Automatic Redirection**: Redirect unauthorized users to the login page.
- **Customizable Messages**: Display custom messages for restricted content.
- **REST API and RSS Feed Integration**: Content restrictions are respected in REST API responses and RSS feeds.

## Installation

1. **Download the Plugin**: Clone or download the plugin from the [GitHub repository](https://github.com/robertdevore/content-restriction-for-wordpress/).

2. **Upload to WordPress®**:

    - Navigate to **Plugins** > **Add New** in your WordPress® admin dashboard.
    - Click on **Upload Plugin** and select the downloaded ZIP file.
    - Click **Install Now** and then **Activate** the plugin.
3. **Via FTP** (Alternative):

    - Upload the plugin folder to the `/wp-content/plugins/` directory.
    - Activate the plugin through the **Plugins** menu in WordPress®.

## Usage

### Global Content Restrictions

1. **Access Settings**:

    - Go to **Settings** > **Content Restriction** in the WordPress® admin dashboard.
2. **Add a Restriction**:

    - Click on the **Add Restriction** button.
3. **Configure Restriction**:

    - **Restrict content by**: Select a **Post Type** or **Taxonomy Term** to restrict.
    - **Role**: Choose the minimum user role required to access the content.
4. **Save Changes**:

    - Click the **Save Changes** button to apply the restrictions.

### Individual Content Restrictions

1. **Edit Post/Page**:

    - Open the post or page you want to restrict in the editor.
2. **Restrict Content Metabox**:

    - In the **Restrict Content** metabox (usually located in the sidebar), check **Restrict this content**.
3. **Select Minimum Role**:

    - Choose the minimum user role required from the dropdown menu.
4. **Update Post/Page**:

    - Click **Update** or **Publish** to save your changes.

### Behavior for Unauthorized Users

- **Logged-Out Users**:
    - Redirected to the login page when accessing restricted content.
- **Logged-In Users Without Required Role**:
    - See a message: "This content is restricted. Please log in to view it."

## Filters and Actions

### Customizing Restriction Messages

You can customize the restriction messages using filters in your theme's `functions.php` file or a custom plugin.

- **Content Message**:
    
    ```
    add_filter( 'crwp_restricted_content_message', function( $message ) {
        return '<p>You must be a premium member to view this content.</p>';
    } );
    ```

- **REST API Message**:
    ```
    add_filter( 'crwp_restricted_rest_message', function( $message ) {
        return '<p>Restricted content. Please upgrade your membership.</p>';
    } );
    ```

- **RSS Feed Message**:
    ```
    add_filter( 'crwp_restricted_feed_message', function( $message ) {
        return '<p>Content is restricted in the feed. Visit the site to view.</p>';
    } );
    ```

### Available Filters

The plugin provides several filters that allow developers to customize its behavior. Below are details for each filter, including usage examples.

#### `crwp_restricted_content_message`

**Description**:  
Filters the message displayed to unauthorized users when they attempt to access restricted content on the front end.

**Parameters**:

- `$message` _(string)_: The default restriction message.

**Usage**:

Add the following code to your theme's `functions.php` file or a custom plugin to modify the restricted content message:
```
/**
 * Customize the restricted content message displayed to unauthorized users.
 *
 * @param  string $message The default restriction message.
 * @return string The customized restriction message.
 */
function my_custom_restricted_content_message( $message ) {
    return '<p>You must be a premium member to view this content.</p>';
}
add_filter( 'crwp_restricted_content_message', 'my_custom_restricted_content_message' );
```

**Returns**:

- _(string)_: The customized message to display to unauthorized users.

#### `crwp_restricted_rest_message`

**Description**:  
Filters the message returned in REST API responses for restricted content. This ensures that any applications or services consuming your REST API will receive a consistent message for restricted content.

**Parameters**:

- `$message` _(string)_: The default REST API restriction message.

**Usage**:

To modify the message returned in REST API responses for restricted content, add the following code:
```
/**
 * Customize the REST API restricted content message.
 *
 * @param  string $message The default REST API restriction message.
 * @return string The customized restriction message.
 */
function my_custom_restricted_rest_message( $message ) {
    return '<p>Content is restricted via the REST API. Please log in.</p>';
}
add_filter( 'crwp_restricted_rest_message', 'my_custom_restricted_rest_message' );
```

**Returns**:

- _(string)_: The customized message to return in REST API responses for restricted content.

#### `crwp_restricted_feed_message`

**Description**:  
Filters the message displayed in RSS feeds for restricted content. This is useful if you want to prevent unauthorized users from accessing restricted content through RSS readers.

**Parameters**:

- `$message` _(string)_: The default feed restriction message.

**Usage**:

Customize the RSS feed message by adding the following code:
```
/**
 * Customize the RSS feed restricted content message.
 *
 * @param  string $message The default feed restriction message.
 * @return string The customized restriction message.
 */
function my_custom_restricted_feed_message( $message ) {
    return '<p>This content is restricted in the feed. Visit the website to access it.</p>';
}
add_filter( 'crwp_restricted_feed_message', 'my_custom_restricted_feed_message' );
```

**Returns**:

- _(string)_: The customized message to display in RSS feeds for restricted content.

### Notes

- These filters should be added to your theme's `functions.php` file or within a custom plugin.
- Remember to replace the messages in the examples with your desired custom messages.
- Double check that any HTML used in the messages is properly sanitized and valid.

## Frequently Asked Questions

### Does this plugin support custom post types and taxonomies?

Yes, it supports all public custom post types and taxonomies registered in WordPress®.

### Can I restrict content to multiple user roles?

You can set the minimum required role for content access. Users with that role or higher will have access.

### What happens to restricted content in the REST API and RSS feeds?

Restricted content will display a restriction message instead of the actual content.

## Screenshots

1. **Content Restriction Settings Page**

2. **Restrict Content Metabox**

## Contributing

Contributions are welcome! Please open issues and submit pull requests on the [GitHub repository](https://github.com/robertdevore/content-restriction-for-wordpress/).

## License

This plugin is licensed under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).