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
        <h1><?php esc_html_e( 'Content Restriction Settings', 'crwp' ); ?></h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'crwp_settings_group' );
                do_settings_sections( 'crwp_settings' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings, sections, and fields with a repeater.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_register_settings() {
    register_setting( 'crwp_settings_group', 'crwp_restrictions', 'sanitize_callback' );

    add_settings_section(
        'crwp_settings_section',
        esc_html__( 'Content Restriction Options', 'crwp' ),
        'crwp_settings_section_callback',
        'crwp_settings'
    );

    add_settings_field(
        'crwp_restrictions',
        esc_html__( 'Restrict content by:', 'crwp' ),
        'crwp_restrictions_field',
        'crwp_settings',
        'crwp_settings_section'
    );
}
add_action( 'admin_init', 'crwp_register_settings' );

/**
 * Callback for the settings section.
 * 
 * @since  1.0.0
 * @return void
 */
function crwp_settings_section_callback() {
    echo '<p>' . esc_html__( 'Set access restrictions per post type or taxonomy term and user role.', 'crwp' ) . '</p>';
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
        
        // Enqueue custom admin script.
        wp_enqueue_script( 'crwp-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/crwp-admin.js', [ 'jquery', 'select2-js' ], CRWP_VERSION, true );
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

    // Get the user role data.
    $user            = wp_get_current_user();
    $roles_hierarchy = array_keys( wp_roles()->get_names() );

    // Compare user's highest role with the minimum required role.
    foreach ( $user->roles as $role ) {
        if ( array_search( $role, $roles_hierarchy ) >= array_search( $minimum_role, $roles_hierarchy ) ) {
            return true;
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
 * Filter REST API response to replace restricted post content with a message.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post $post The post object.
 * @param WP_REST_Request $request The request object.
 * 
 * @since  1.0.0
 * @return WP_REST_Response The modified response object.
 */
function crwp_modify_rest_content( $response, $post, $request ) {
    $restrictions = get_option( 'crwp_restrictions', [] );
    $is_restricted = get_post_meta( $post->ID, '_crwp_restrict_content', true );
    $individual_role = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';

    // Check individual restriction for the post.
    if ( $is_restricted && ! crwp_user_has_minimum_role( $individual_role ) ) {
        // Replace content with a restriction message.
        $response->data['content']['rendered'] = '<p>' . esc_html__( 'This content is restricted. Please log in to view.', 'crwp' ) . '</p>';
        return $response;
    }

    // If no individual restriction, check global settings.
    foreach ( $restrictions as $restriction ) {
        $restricted_role = $restriction['role'];
        $content_type = explode( ':', $restriction['content_type'] );

        // Apply global restriction if the user doesn't meet the required role.
        if ( ! crwp_user_has_minimum_role( $restricted_role ) ) {
            // Restrict by post type.
            if ( $content_type[0] === 'post_type' && $post->post_type === $content_type[1] ) {
                $response->data['content']['rendered'] = '<p>' . esc_html__( 'This content is restricted. Please log in to view.', 'crwp' ) . '</p>';
                return $response;
            }

            // Restrict by taxonomy term.
            if ( $content_type[0] === 'taxonomy' && has_term( (int) $content_type[2], $content_type[1], $post ) ) {
                $response->data['content']['rendered'] = '<p>' . esc_html__( 'This content is restricted. Please log in to view.', 'crwp' ) . '</p>';
                return $response;
            }
        }
    }

    return $response;
}
add_filter( 'rest_prepare_post', 'crwp_modify_rest_content', 10, 3 );

/**
 * Filter the content for restricted posts in the RSS feed to display a restriction message.
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
        $restrictions = get_option( 'crwp_restrictions', [] );

        // Check individual restriction for the post.
        $is_restricted   = get_post_meta( $post->ID, '_crwp_restrict_content', true );
        $individual_role = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';

        if ( $is_restricted && ! crwp_user_has_minimum_role( $individual_role ) ) {
            return '<p>' . esc_html__( 'This content is restricted. Please visit the site and log in to view.', 'crwp' ) . '</p>';
        }

        // If no individual restriction, check global settings.
        foreach ( $restrictions as $restriction ) {
            $restricted_role = $restriction['role'];
            $content_type = explode( ':', $restriction['content_type'] );

            // Apply global restriction if the user doesn't meet the required role.
            if ( ! crwp_user_has_minimum_role( $restricted_role ) ) {
                // Restrict by post type.
                if ( $content_type[0] === 'post_type' && $post->post_type === $content_type[1] ) {
                    return '<p>' . esc_html__( 'This content is restricted. Please visit the site and log in to view.', 'crwp' ) . '</p>';
                }

                // Restrict by taxonomy term.
                if ( $content_type[0] === 'taxonomy' && has_term( (int) $content_type[2], $content_type[1], $post ) ) {
                    return '<p>' . esc_html__( 'This content is restricted. Please visit the site and log in to view.', 'crwp' ) . '</p>';
                }
            }
        }
    }

    return $content;
}
add_filter( 'the_content', 'crwp_modify_feed_content' );