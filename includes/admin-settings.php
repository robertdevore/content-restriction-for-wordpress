<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

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
 * @since  1.0.0
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
 * @since  1.0.0
 * @return void
 */
function crwp_restriction_options_section_callback() {
    echo '<p>' . esc_html__( 'Set access restrictions per post type or taxonomy term and user role.', 'crwp' ) . '</p>';
}

/**
 * Callback for the content visibility options section.
 *
 * @since  1.0.0
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
 * 
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
