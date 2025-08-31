<?php
/**
 * Registration Helpers
 *
 * Shared functions for creating users, assigning roles, and enrolling in LearnDash.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Create a new WP user with the given role and subscriber role.
 *
 * @param array  $data Form data
 * @param string $role Role slug to assign
 * @return int|WP_Error New user ID or WP_Error on failure
 */
function reg_helpers_create_user_and_assign_roles( array $data, string $role ) {
    $userdata = [
        'first_name' => sanitize_text_field( $data['first_name'] ?? '' ),
        'last_name'  => sanitize_text_field( $data['last_name'] ?? '' ),
        // Capture email provided in form
        'user_email' => sanitize_email( $data['email'] ?? '' ),
        'user_login' => sanitize_user( $data['phone'] ?? '' ),
        'user_pass'  => wp_generate_password(),
        'role'       => $role,
    ];
    $user_id = wp_insert_user( $userdata );
    if ( is_wp_error( $user_id ) ) {
        return $user_id;
    }
    $user = new WP_User( $user_id );
    $user->add_role( 'subscriber' );
    return $user_id;
}

/**
 * Enroll a user into LearnDash groups and courses based on roles.
 *
 * @param int   $user_id WP user ID
 * @param array $roles   Array of role slugs
 */
function reg_helpers_enroll_in_learndash( int $user_id, array $roles ) {
    $map = [
        'student_private' => [ 'group' => 1294, 'courses' => [ 898, 1292, 1367 ] ],
        'student_school'  => [ 'group' => 1294, 'courses' => [ 898, 1292, 1367 ] ],
        'school_teacher'  => [ 'group' => 1294, 'courses' => [] ],
        'book_holder'     => [ 'group' => 1320, 'courses' => [] ],
        'practice_only'   => [ 'group' => 0,    'courses' => [] ],
    ];
    foreach ( $roles as $role ) {
        if ( ! empty( $map[ $role ]['group'] ) ) {
            ld_update_group_access( $user_id, $map[ $role ]['group'] );
        }
        foreach ( $map[ $role ]['courses'] as $course_id ) {
            ld_update_course_access( $user_id, $course_id, false );
        }
    }
}
