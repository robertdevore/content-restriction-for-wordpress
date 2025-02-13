<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
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

