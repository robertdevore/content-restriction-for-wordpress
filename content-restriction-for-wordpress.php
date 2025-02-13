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

require 'includes/admin-settings.php';
require 'includes/enqueue.php';
require 'includes/metabox.php';

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/content-restriction-for-wordpress/',
	__FILE__,
	'content-restriction-for-wordpress'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

// Current plugin version.
define( 'CRWP_VERSION', '1.0.0' );

/**
 * Load plugin text domain for translations
 * 
 * @since  1.1.0
 * @return void
 */
function crwp_load_textdomain() {
    load_plugin_textdomain( 
        'crwp',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'crwp_load_textdomain' );

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
        $individual_role        = get_post_meta( $post->ID, '_crwp_restricted_role', true ) ?: 'subscriber';
        
        if ( $individual_restriction && ( ! is_user_logged_in() || ! crwp_user_has_minimum_role( $individual_role ) ) ) {
            return '<p>' . esc_html__( 'This content is restricted. Please log in to view it.', 'crwp' ) . '</p>';
        }

        // Process global restrictions if no individual restriction.
        foreach ( $restrictions as $restriction ) {
            $restricted_role = $restriction['role'];
            $content_type    = explode( ':', $restriction['content_type'] );

            // Check if user meets role requirement for the post type or taxonomy restrictions.
            if ( ! is_user_logged_in() || ! crwp_user_has_minimum_role( $restricted_role ) ) {
                // Handle post type restriction.
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
 * @since  1.0.0
 * @return void
 */
function crwp_fetch_content_options() {
    // Check for nonce security.
    check_ajax_referer( 'crwp_ajax_nonce', 'nonce' );

    $search = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';

    $results = [];

    // Fetch post types
    $post_types = get_post_types( [ 'public' => true ], 'objects' );
    foreach ( $post_types as $post_type ) {
        if ( stripos( $post_type->label, $search ) !== false ) {
            $results[] = [
                'id'   => 'post_type:' . $post_type->name,
                'text' => $post_type->label,
            ];
        }
    }

    // Fetch taxonomy terms
    $taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
    foreach ( $taxonomies as $taxonomy ) {
        $terms = get_terms( [
            'taxonomy'   => $taxonomy->name,
            'hide_empty' => false,
            'search'     => $search,
        ] );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $results[] = [
                    'id'   => 'taxonomy:' . $taxonomy->name . ':' . $term->term_id,
                    'text' => $taxonomy->label . ' - ' . $term->name,
                ];
            }
        }
    }

    // Return the result as JSON.
    wp_send_json( $results );
}
add_action( 'wp_ajax_crwp_fetch_content_options', 'crwp_fetch_content_options' );
