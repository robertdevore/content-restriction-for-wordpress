<?php

/**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             1.0.0
  * @package           Content_Restriction_For_WordPress
  *
  * @wordpress-plugin
  *
  * Plugin Name: Content Restriction for WordPress®
  * Description: Enables role-based content restriction by post type, taxonomy term, or individual post/page, redirecting unauthorized users to login when needed.
  * Plugin URI:  https://github.com/robertdevore/content-restriction-for-wordpress/
  * Version:     1.0.0
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: crwp
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/content-restriction-for-wordpress/
  */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/content-restriction-for-wordpress/',
	__FILE__,
	'content-restriction-for-wordpress'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

/**
 * Current plugin version.
 */
define( 'CRWP_VERSION', '1.0.0' );

/**
 * Register the settings page.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_register_settings_page() {
    add_options_page(
        esc_html__( 'Content Restriction Settings', 'crwp' ),
        esc_html__( 'Content Restriction', 'crwp' ),
        'manage_options',
        'crwp_settings',
        'crwp_render_settings_page'
    );
}
add_action( 'admin_menu', 'crwp_register_settings_page' );

/**
 * Render the settings page.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Content Restriction Settings', 'crwp' ); ?>
            <a id="crwp-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> <?php esc_html_e( 'Support', 'crwp' ); ?>
            </a>
            <a id="crwp-docs-btn" href="https://robertdevore.com/articles/content-restriction-for-wordpress/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> <?php esc_html_e( 'Documentation', 'crwp' ); ?>
            </a>
        </h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'crwp_settings_group' );

                echo '<div class="crwp-section crwp-restriction-options">';
                do_settings_sections( 'crwp_settings_restriction_options' );
                echo '</div>';

                do_settings_sections( 'crwp_settings_visibility_options' );

                submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings, sections, and fields with a repeater.
 *
 * @since 1.0.0
 * @return void
 */
function crwp_register_settings() {
    // Register the main settings.
    register_setting( 'crwp_settings_group', 'crwp_restrictions', [ 'crwp_sanitize_restrictions' ] );

    // Register new settings for REST API and RSS feed visibility.
    register_setting( 'crwp_settings_group', 'crwp_hide_rest_content', [ 'absint' ] );
    register_setting( 'crwp_settings_group', 'crwp_hide_feed_content', [ 'absint' ] );

    /**
     * Section: Content Restriction Options
     */
    add_settings_section(
        'crwp_restriction_options_section',
        esc_html__( 'Content Restriction Options', 'crwp' ),
        'crwp_restriction_options_section_callback',
        'crwp_settings_restriction_options'
    );

    add_settings_field(
        'crwp_restrictions',
        esc_html__( 'Restrict content by:', 'crwp' ),
        'crwp_restrictions_field',
        'crwp_settings_restriction_options',
        'crwp_restriction_options_section'
    );

    /**
     * Section: Content Visibility Options
     */
    add_settings_section(
        'crwp_visibility_options_section',
        esc_html__( 'Content Visibility Options', 'crwp' ),
        'crwp_visibility_options_section_callback',
        'crwp_settings_visibility_options'
    );

    add_settings_field(
        'crwp_hide_rest_content',
        esc_html__( 'Hide Restricted Content from REST API:', 'crwp' ),
        'crwp_hide_rest_content_field',
        'crwp_settings_visibility_options',
        'crwp_visibility_options_section'
    );

    add_settings_field(
        'crwp_hide_feed_content',
        esc_html__( 'Hide Restricted Content from RSS Feed:', 'crwp' ),
        'crwp_hide_feed_content_field',
        'crwp_settings_visibility_options',
        'crwp_visibility_options_section'
    );
}
add_action( 'admin_init', 'crwp_register_settings' );

/**
 * Callback for the content restriction options section.
 *
 * @since 1.1.0
 * @return void
 */
function crwp_restriction_options_section_callback() {
    echo '<p>' . esc_html__( 'Set access restrictions per post type or taxonomy term and user role.', 'crwp' ) . '</p>';
}

/**
 * Callback for the content visibility options section.
 *
 * @since 1.1.0
 * @return void
 */
function crwp_visibility_options_section_callback() {
    echo '<hr />';
    echo '<p>' . esc_html__( 'Configure how restricted content is handled in the REST API and RSS feeds.', 'crwp' ) . '</p>';
}

/**
 * Sanitize the restrictions option.
 *
 * @param array $input The input value.
 * @return array The sanitized input.
 */
function crwp_sanitize_restrictions( $input ) {
    // Perform sanitization on the restrictions array.
    $sanitized = [];

    if ( is_array( $input ) ) {
        foreach ( $input as $index => $restriction ) {
            $sanitized[ $index ]['content_type'] = sanitize_text_field( $restriction['content_type'] );
            $sanitized[ $index ]['role']         = sanitize_text_field( $restriction['role'] );
        }
    }

    return $sanitized;
}

/**
 * Render the checkbox for hiding content from REST API.
 *
 * @since  1.0.0
 * @return void
 */
function crwp_hide_rest_content_field() {
    $option = get_option( 'crwp_hide_rest_content', '' );
    ?>
    <label for="crwp_hide_rest_content">
        <input type="checkbox" name="crwp_hide_rest_content" id="crwp_hide_rest_content" value="1" <?php checked( $option, '1' ); ?> />
        <?php esc_html_e( 'Completely hide restricted content from REST API responses.', 'crwp' ); ?>
    </label>
    <?php
}

/**
 * Render the checkbox for hiding content from RSS feed.
 *
 * @since  1.0.0
 * @return void
 */
function crwp_hide_feed_content_field() {
    $option = get_option( 'crwp_hide_feed_content', '' );
    ?>
    <label for="crwp_hide_feed_content">
        <input type="checkbox" name="crwp_hide_feed_content" id="crwp_hide_feed_content" value="1" <?php checked( $option, '1' ); ?> />
        <?php esc_html_e( 'Completely hide restricted content from RSS feeds.', 'crwp' ); ?>
    </label>
    <?php
}

/**
 * Render the repeater field for restrictions, combining post types and taxonomies in one select box.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_restrictions_field() {
    global $wp_roles;
    $post_types   = get_post_types( [ 'public' => true ], 'objects' );
    $taxonomies   = get_taxonomies( [ 'public' => true ], 'objects' );
    $restrictions = get_option( 'crwp_restrictions', [] );

    echo '<div id="crwp_repeater_container">';
    foreach ( $restrictions as $index => $restriction ) {
        ?>
        <div class="crwp_repeater_item">
            <select name="crwp_restrictions[<?php echo esc_attr( $index ); ?>][content_type]" class="crwp-content-type-select">
                <option value=""><?php esc_html_e( 'Select Post Type or Taxonomy Term', 'crwp' ); ?></option>

                <optgroup label="<?php esc_attr_e( 'Post Types', 'crwp' ); ?>">
                    <?php foreach ( $post_types as $post_type ) : ?>
                        <option value="post_type:<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $restriction['content_type'], 'post_type:' . $post_type->name ); ?>>
                            <?php echo esc_html( $post_type->label ); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>

                <optgroup label="<?php esc_attr_e( 'Taxonomies', 'crwp' ); ?>">
                    <?php foreach ( $taxonomies as $taxonomy ) : ?>
                        <?php
                        $terms = get_terms( [
                            'taxonomy'   => $taxonomy->name,
                            'hide_empty' => false
                        ] );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
                        ?>
                            <optgroup label="&nbsp;&nbsp;<?php echo esc_attr( $taxonomy->label ); ?>">
                                <?php foreach ( $terms as $term ) : ?>
                                    <option value="taxonomy:<?php echo esc_attr( $taxonomy->name . ':' . $term->term_id ); ?>" <?php selected( $restriction['content_type'], 'taxonomy:' . $taxonomy->name . ':' . $term->term_id ); ?>>
                                        &nbsp;&nbsp;<?php echo esc_html( $term->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </optgroup>
            </select>

            <select name="crwp_restrictions[<?php echo esc_attr( $index ); ?>][role]" class="crwp-role-select">
                <?php foreach ( $wp_roles->roles as $role => $details ) : ?>
                    <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $restriction['role'], $role ); ?>>
                        <?php echo esc_html( $details['name'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button remove-item">Remove</button>
        </div>
        <?php
    }
    echo '</div>';

    // Add hidden template for new restrictions.
    echo '<div id="crwp_template" style="display: none;">';
    echo '<select class="crwp-content-type-select">';
    echo '<option value="">' . esc_html__( 'Select Post Type or Taxonomy Term', 'crwp' ) . '</option>';

    // Post Types Section.
    echo '<optgroup label="' . esc_attr__( 'Post Types', 'crwp' ) . '">';
    foreach ( $post_types as $post_type ) {
        echo '<option value="post_type:' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->label ) . '</option>';
    }
    echo '</optgroup>';

    // Taxonomies Section.
    echo '<optgroup label="' . esc_attr__( 'Taxonomies', 'crwp' ) . '">';
    foreach ( $taxonomies as $taxonomy ) {
        $terms = get_terms( [ 'taxonomy' => $taxonomy->name, 'hide_empty' => false ] );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            echo '<optgroup label="&nbsp;&nbsp;' . esc_attr( $taxonomy->label ) . '">';
            foreach ( $terms as $term ) {
                echo '<option value="taxonomy:' . esc_attr( $taxonomy->name . ':' . $term->term_id ) . '">&nbsp;&nbsp;' . esc_html( $term->name ) . '</option>';
            }
            echo '</optgroup>';
        }
    }
    echo '</optgroup>';
    echo '</select>';

    echo '<select class="crwp-role-select">';
    foreach ( $wp_roles->roles as $role => $details ) {
        echo '<option value="' . esc_attr( $role ) . '">' . esc_html( $details['name'] ) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    echo '<button type="button" class="button add-item">' . esc_html__( 'Add Restriction', 'crwp' ) . '</button>';
}

/**
 * Enqueue scripts and styles for the settings page.
 * 
 * @param string $hook The current admin page.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_enqueue_admin_scripts( $hook ) {
    if ( 'settings_page_crwp_settings' === $hook ) {
        // Enqueue Select2 library.
        wp_enqueue_script( 'select2-js', plugin_dir_url( __FILE__ ) . 'assets/js/select2.min.js', [ 'jquery' ], null, true );
        wp_enqueue_style( 'select2-css', plugin_dir_url( __FILE__ ) . 'assets/css/select2.min.css' );

        // Enqueue custom admin JS.
        wp_enqueue_script( 'crwp-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/crwp-admin.js', [ 'jquery', 'select2-js' ], CRWP_VERSION, true );
        wp_localize_script( 'crwp-admin-js', 'crwp_admin', [
            'nonce' => wp_create_nonce( 'crwp_ajax_nonce' ),
        ] );

        // Enqueue custom admin CSS.
        wp_enqueue_style( 'crwp-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/crwp-admin.css', [], CRWP_VERSION );
    }
}
add_action( 'admin_enqueue_scripts', 'crwp_enqueue_admin_scripts' );

/**
 * Modify content restriction logic to consider individual post/page restrictions with role.
 *
 * @param string $content The original content.
 * 
 * @since  1.0.0
 * @return string Restricted content message or the original content.
 */
function crwp_restrict_content_based_on_settings( $content ) {
    global $post;

    if ( is_singular() && $post ) {
        $restrictions = get_option( 'crwp_restrictions', [] );

        // Check for individual post-level restriction and role.
        $individual_restriction = get_post_meta( $post->ID, '_crwp_restrict_content', true );
        $individual_role = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';
        
        if ( $individual_restriction && ( ! is_user_logged_in() || ! crwp_user_has_minimum_role( $individual_role ) ) ) {
            return '<p>' . esc_html__( 'This content is restricted. Please log in to view it.', 'crwp' ) . '</p>';
        }

        // Process global restrictions if no individual restriction.
        foreach ( $restrictions as $restriction ) {
            $restricted_role = $restriction['role'];
            $content_type = explode( ':', $restriction['content_type'] );

            // Check if user meets role requirement for the post type or taxonomy restrictions.
            if ( ! is_user_logged_in() || ! crwp_user_has_minimum_role( $restricted_role ) ) {
                // Handle post type restriction
                if ( $content_type[0] === 'post_type' && $post->post_type === $content_type[1] ) {
                    return '<p>' . esc_html__( 'This content is restricted. Please log in to view it.', 'crwp' ) . '</p>';
                }

                // Handle taxonomy term restriction.
                if ( $content_type[0] === 'taxonomy' && has_term( (int) $content_type[2], $content_type[1], $post ) ) {
                    return '<p>' . esc_html__( 'This content is restricted. Please log in to view it.', 'crwp' ) . '</p>';
                }
            }
        }
    }

    return $content;
}
add_filter( 'the_content', 'crwp_restrict_content_based_on_settings' );

/**
 * Modify redirection logic to consider individual post/page restrictions with role.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_redirect_unauthorized_users() {
    if ( is_singular() ) {
        global $post;
        $restrictions = get_option( 'crwp_restrictions', [] );

        // Check individual post restriction and role.
        $individual_restriction = get_post_meta( $post->ID, '_crwp_restrict_content', true );
        $individual_role        = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';

        if ( $individual_restriction && ( ! is_user_logged_in() || ! crwp_user_has_minimum_role( $individual_role ) ) ) {
            wp_redirect( wp_login_url( get_permalink() ) );
            exit;
        }

        // Process global restrictions if no individual restriction.
        foreach ( $restrictions as $restriction ) {
            $content_type    = explode( ':', $restriction['content_type'] );
            $restricted_role = $restriction['role'];

            // Check if user meets role requirement for post type or taxonomy restrictions.
            if ( ! is_user_logged_in() || ! crwp_user_has_minimum_role( $restricted_role ) ) {
                // Handle post type restriction.
                if ( $content_type[0] === 'post_type' && $post->post_type === $content_type[1] ) {
                    wp_redirect( wp_login_url( get_permalink() ) );
                    exit;
                }

                // Handle taxonomy term restriction.
                if ( $content_type[0] === 'taxonomy' && has_term( (int) $content_type[2], $content_type[1], $post ) ) {
                    wp_redirect( wp_login_url( get_permalink() ) );
                    exit;
                }
            }
        }
    }
}
add_action( 'template_redirect', 'crwp_redirect_unauthorized_users' );

/**
 * Check if the current user has the specified minimum role or higher.
 *
 * @param string $minimum_role The minimum required role.
 * 
 * @since  1.0.0
 * @return bool True if user meets or exceeds the role, false otherwise.
 */
function crwp_user_has_minimum_role( $minimum_role ) {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    // Get the current user and their roles.
    $user = wp_get_current_user();

    // Retrieve all roles in a hierarchical order, from lowest to highest, and flip for indexing.
    $roles_hierarchy = array_keys( wp_roles()->get_names() );
    $roles_hierarchy = array_flip( array_reverse( $roles_hierarchy ) );

    // Ensure the minimum role is valid in the hierarchy.
    if ( ! isset( $roles_hierarchy[ $minimum_role ] ) ) {
        error_log( '[Content Restriction for WordPress®] Minimum role is not in the defined roles hierarchy.' );
        return false;
    }

    // Get the required level for the minimum role.
    $required_level = $roles_hierarchy[ $minimum_role ];

    // Check each of the user’s roles to see if any meet or exceed the required level.
    foreach ( $user->roles as $role ) {
        if ( isset( $roles_hierarchy[ $role ] ) ) {
            $user_role_level = $roles_hierarchy[ $role ];

            if ( $user_role_level >= $required_level ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Adds a restriction metabox to individual posts and pages for specifying access control.
 *
 * This function loops through all public post types and attaches a metabox for each one,
 * allowing content restriction based on a selected user role. The metabox provides a checkbox 
 * to enable restriction and a dropdown to select the minimum user role required to view the content.
 *
 * @since  1.0.0
 * @return void
 */
function crwp_add_restriction_metabox() {
    $post_types = get_post_types( ['public' => true] );
    foreach ( $post_types as $post_type ) {
        add_meta_box(
            'crwp_restriction_metabox',
            esc_html__( 'Restrict Content', 'crwp' ),
            'crwp_render_restriction_metabox',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action( 'add_meta_boxes', 'crwp_add_restriction_metabox' );

/**
 * Render the restriction metabox with a checkbox and role select.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_render_restriction_metabox( $post ) {
    global $wp_roles;

    // Retrieve the current values for restriction and role.
    $is_restricted = get_post_meta( $post->ID, '_crwp_restrict_content', true );
    $selected_role = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';

    wp_nonce_field( 'crwp_restriction_metabox_nonce', 'crwp_restriction_nonce' );
    ?>
    <label for="crwp_restrict_content">
        <input type="checkbox" name="crwp_restrict_content" id="crwp_restrict_content" value="1" <?php checked( $is_restricted, '1' ); ?> />
        <?php esc_html_e( 'Restrict this content', 'crwp' ); ?>
    </label>
    <br /><br />
    <label for="crwp_restricted_role">
        <?php esc_html_e( 'Minimum Role Required:', 'crwp' ); ?>
    </label>
    <select name="crwp_restricted_role" id="crwp_restricted_role">
        <?php foreach ( $wp_roles->roles as $role => $details ) : ?>
            <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $selected_role, $role ); ?>>
                <?php echo esc_html( $details['name'] ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Save the restriction metabox data when saving the post.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_save_restriction_metabox( $post_id ) {
    // Verify nonce and autosave state.
    if ( ! isset( $_POST['crwp_restriction_nonce'] ) || ! wp_verify_nonce( $_POST['crwp_restriction_nonce'], 'crwp_restriction_metabox_nonce' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save the checkbox.
    $is_restricted = isset( $_POST['crwp_restrict_content'] ) ? '1' : '';
    update_post_meta( $post_id, '_crwp_restrict_content', $is_restricted );

    // Save the selected role.
    $selected_role = $_POST['crwp_restricted_role'] ?? 'subscriber';
    update_post_meta( $post_id, '_crwp_restricted_role', sanitize_text_field( $selected_role ) );
}
add_action( 'save_post', 'crwp_save_restriction_metabox' );

/**
 * Filter REST API response to handle restricted content based on settings.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post          $post     The post object.
 * @param WP_REST_Request  $request  The request object.
 *
 * @since  1.0.0
 * @return WP_REST_Response The modified response object.
 */
function crwp_modify_rest_content( $response, $post, $request ) {
    $restrictions    = get_option( 'crwp_restrictions', [] );
    $is_restricted   = get_post_meta( $post->ID, '_crwp_restrict_content', true );
    $individual_role = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';

    // Check if hiding restricted content from REST API is enabled
    $hide_rest_content = get_option( 'crwp_hide_rest_content', '' );

    // Check individual restriction for the post.
    if ( $is_restricted && ! crwp_user_has_minimum_role( $individual_role ) ) {
        if ( $hide_rest_content ) {
            // Remove the post from the REST API response entirely.
            return new WP_Error( 'rest_post_invalid', __( 'Content not found.', 'crwp' ), array( 'status' => 404 ) );
        } else {
            // Replace content with a restriction message.
            $message = apply_filters( 'crwp_restricted_rest_message', '<p>' . esc_html__( 'This content is restricted. Please log in to view.', 'crwp' ) . '</p>' );
            $response->data['content']['rendered'] = $message;
            return $response;
        }
    }

    // If no individual restriction, check global settings.
    foreach ( $restrictions as $restriction ) {
        $restricted_role = $restriction['role'];
        $content_type    = explode( ':', $restriction['content_type'] );

        // Apply global restriction if the user doesn't meet the required role.
        if ( ! crwp_user_has_minimum_role( $restricted_role ) ) {
            $is_match = false;

            // Restrict by post type.
            if ( $content_type[0] === 'post_type' && $post->post_type === $content_type[1] ) {
                $is_match = true;
            }

            // Restrict by taxonomy term.
            if ( $content_type[0] === 'taxonomy' && has_term( (int) $content_type[2], $content_type[1], $post ) ) {
                $is_match = true;
            }

            if ( $is_match ) {
                if ( $hide_rest_content ) {
                    // Remove the post from the REST API response entirely.
                    return new WP_Error( 'rest_post_invalid', __( 'Content not found.', 'crwp' ), array( 'status' => 404 ) );
                } else {
                    // Replace content with a restriction message.
                    $message = apply_filters( 'crwp_restricted_rest_message', '<p>' . esc_html__( 'This content is restricted. Please log in to view.', 'crwp' ) . '</p>' );
                    $response->data['content']['rendered'] = $message;
                    return $response;
                }
            }
        }
    }

    return $response;
}
add_filter( 'rest_prepare_post', 'crwp_modify_rest_content', 10, 3 );

/**
 * Filter the content for restricted posts in the RSS feed based on settings.
 *
 * @param string $content The original content.
 *
 * @since  1.0.0
 * @return string The restricted message or the original content.
 */
function crwp_modify_feed_content( $content ) {
    global $post;

    // Only modify content in feeds.
    if ( is_feed() ) {
        $restrictions      = get_option( 'crwp_restrictions', [] );
        $hide_feed_content = get_option( 'crwp_hide_feed_content', '' );

        // Check individual restriction for the post.
        $is_restricted   = get_post_meta( $post->ID, '_crwp_restrict_content', true );
        $individual_role = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';

        if ( $is_restricted && ! crwp_user_has_minimum_role( $individual_role ) ) {
            if ( $hide_feed_content ) {
                // Remove the post content from the feed.
                return '';
            } else {
                $message = apply_filters( 'crwp_restricted_feed_message', '<p>' . esc_html__( 'This content is restricted. Please visit the site and log in to view.', 'crwp' ) . '</p>' );
                return $message;
            }
        }

        // If no individual restriction, check global settings.
        foreach ( $restrictions as $restriction ) {
            $restricted_role = $restriction['role'];
            $content_type    = explode( ':', $restriction['content_type'] );

            // Apply global restriction if the user doesn't meet the required role.
            if ( ! crwp_user_has_minimum_role( $restricted_role ) ) {
                $is_match = false;

                // Restrict by post type.
                if ( $content_type[0] === 'post_type' && $post->post_type === $content_type[1] ) {
                    $is_match = true;
                }

                // Restrict by taxonomy term.
                if ( $content_type[0] === 'taxonomy' && has_term( (int) $content_type[2], $content_type[1], $post ) ) {
                    $is_match = true;
                }

                if ( $is_match ) {
                    if ( $hide_feed_content ) {
                        // Remove the post content from the feed.
                        return '';
                    } else {
                        $message = apply_filters( 'crwp_restricted_feed_message', '<p>' . esc_html__( 'This content is restricted. Please visit the site and log in to view.', 'crwp' ) . '</p>' );
                        return $message;
                    }
                }
            }
        }
    }

    return $content;
}
add_filter( 'the_content', 'crwp_modify_feed_content' );

/**
 * AJAX handler to fetch post types and taxonomy terms for Select2.
 *
 * @since 1.0.0
 */
function crwp_fetch_content_options() {
    // Check for nonce security.
    check_ajax_referer( 'crwp_ajax_nonce', 'nonce' );

    $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

    $results = [];

    // Fetch post types
    $post_types = get_post_types([ 'public' => true ], 'objects');
    foreach ($post_types as $post_type) {
        if (stripos($post_type->label, $search) !== false) {
            $results[] = [
                'id' => 'post_type:' . $post_type->name,
                'text' => $post_type->label,
            ];
        }
    }

    // Fetch taxonomy terms
    $taxonomies = get_taxonomies([ 'public' => true ], 'objects');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms([
            'taxonomy' => $taxonomy->name,
            'hide_empty' => false,
            'search' => $search,
        ]);
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $results[] = [
                    'id' => 'taxonomy:' . $taxonomy->name . ':' . $term->term_id,
                    'text' => $taxonomy->label . ' - ' . $term->name,
                ];
            }
        }
    }

    // Return the result as JSON
    wp_send_json($results);
}
add_action('wp_ajax_crwp_fetch_content_options', 'crwp_fetch_content_options');

/**
 * Helper function to handle WordPress.com environment checks.
 *
 * @param string $plugin_slug     The plugin slug.
 * @param string $learn_more_link The link to more information.
 * 
 * @since  1.1.0
 * @return bool
 */
function wp_com_plugin_check( $plugin_slug, $learn_more_link ) {
    // Check if the site is hosted on WordPress.com.
    if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
        // Ensure the deactivate_plugins function is available.
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Deactivate the plugin if in the admin area.
        if ( is_admin() ) {
            deactivate_plugins( $plugin_slug );

            // Add a deactivation notice for later display.
            add_option( 'wpcom_deactivation_notice', $learn_more_link );

            // Prevent further execution.
            return true;
        }
    }

    return false;
}

/**
 * Auto-deactivate the plugin if running in an unsupported environment.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_auto_deactivation() {
    if ( wp_com_plugin_check( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ) ) {
        return; // Stop execution if deactivated.
    }
}
add_action( 'plugins_loaded', 'wpcom_auto_deactivation' );

/**
 * Display an admin notice if the plugin was deactivated due to hosting restrictions.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_admin_notice() {
    $notice_link = get_option( 'wpcom_deactivation_notice' );
    if ( $notice_link ) {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                        __( 'My Plugin has been deactivated because it cannot be used on WordPress.com-hosted websites. %s', 'crwp' ),
                        '<a href="' . esc_url( $notice_link ) . '" target="_blank" rel="noopener">' . __( 'Learn more', 'crwp' ) . '</a>'
                    )
                );
                ?>
            </p>
        </div>
        <?php
        delete_option( 'wpcom_deactivation_notice' );
    }
}
add_action( 'admin_notices', 'wpcom_admin_notice' );

/**
 * Prevent plugin activation on WordPress.com-hosted sites.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_activation_check() {
    if ( wp_com_plugin_check( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ) ) {
        // Display an error message and stop activation.
        wp_die(
            wp_kses_post(
                sprintf(
                    '<h1>%s</h1><p>%s</p><p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
                    __( 'Plugin Activation Blocked', 'crwp' ),
                    __( 'This plugin cannot be activated on WordPress.com-hosted websites. It is restricted due to concerns about WordPress.com policies impacting the community.', 'crwp' ),
                    esc_url( 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ),
                    __( 'Learn more', 'crwp' )
                )
            ),
            esc_html__( 'Plugin Activation Blocked', 'crwp' ),
            [ 'back_link' => true ]
        );
    }
}
register_activation_hook( __FILE__, 'wpcom_activation_check' );

/**
 * Add a deactivation flag when the plugin is deactivated.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_deactivation_flag() {
    add_option( 'wpcom_deactivation_notice', 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );
}
register_deactivation_hook( __FILE__, 'wpcom_deactivation_flag' );
