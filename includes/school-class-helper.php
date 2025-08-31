<?php
/**
 * Helper functions for school classes and promo codes
 *
 * @package Hello_Theme_Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get promo codes by teacher ID
 *
 * @param int $teacher_id Teacher user ID
 * @return array Array of promo code data
 */
function lilac_get_promo_codes_by_teacher($teacher_id) {
    if (empty($teacher_id)) {
        return array();
    }
    
    // Query for promo codes assigned to this teacher
    $args = array(
        'post_type' => 'ld_promo_code',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_ld_promo_code_teacher_id',
                'value' => $teacher_id,
                'compare' => '=',
            ),
        ),
    );
    
    $query = new WP_Query($args);
    $codes = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $code = get_the_title();
            $group_id = get_post_meta($post_id, '_ld_promo_code_group_id', true);
            $student_id = get_post_meta($post_id, '_ld_promo_code_student_id', true);
            $expiry_date = get_post_meta($post_id, '_ld_promo_code_expiry_date', true);
            
            $codes[] = array(
                'id' => $post_id,
                'code' => $code,
                'group_id' => $group_id,
                'student_id' => $student_id,
                'expiry_date' => $expiry_date,
                'group_name' => $group_id ? get_the_title($group_id) : '',
                'student_name' => $student_id ? get_user_by('id', $student_id)->display_name : '',
                'used' => !empty($student_id),
            );
        }
        wp_reset_postdata();
    }
    
    return $codes;
}

/**
 * Get promo codes by group/class ID
 *
 * @param int $group_id LearnDash group/class ID
 * @return array Array of promo code data
 */
function lilac_get_promo_codes_by_group($group_id) {
    if (empty($group_id)) {
        return array();
    }
    
    // Query for promo codes assigned to this group
    $args = array(
        'post_type' => 'ld_promo_code',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_ld_promo_code_group_id',
                'value' => $group_id,
                'compare' => '=',
            ),
        ),
    );
    
    $query = new WP_Query($args);
    $codes = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $code = get_the_title();
            $teacher_id = get_post_meta($post_id, '_ld_promo_code_teacher_id', true);
            $student_id = get_post_meta($post_id, '_ld_promo_code_student_id', true);
            $expiry_date = get_post_meta($post_id, '_ld_promo_code_expiry_date', true);
            
            $codes[] = array(
                'id' => $post_id,
                'code' => $code,
                'teacher_id' => $teacher_id,
                'student_id' => $student_id,
                'expiry_date' => $expiry_date,
                'teacher_name' => $teacher_id ? get_user_by('id', $teacher_id)->display_name : '',
                'student_name' => $student_id ? get_user_by('id', $student_id)->display_name : '',
                'used' => !empty($student_id),
            );
        }
        wp_reset_postdata();
    }
    
    return $codes;
}

/**
 * Get classes/groups by teacher ID
 * 
 * @param int $teacher_id Teacher user ID
 * @return array Array of group data
 */
function lilac_get_groups_by_teacher($teacher_id) {
    if (empty($teacher_id)) {
        return array();
    }
    
    // Get all groups where the user is a leader
    $query_args = array(
        'post_type'      => 'groups',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_ld_group_leaders',
                'value'   => $teacher_id,
                'compare' => 'LIKE',
            ),
        ),
    );
    
    $groups_query = new WP_Query($query_args);
    $groups = array();
    
    if ($groups_query->have_posts()) {
        while ($groups_query->have_posts()) {
            $groups_query->the_post();
            $group_id = get_the_ID();
            $group_leaders = get_post_meta($group_id, '_ld_group_leaders', true);
            $group_members = get_post_meta($group_id, '_groups_members', true);
            
            if (!is_array($group_leaders)) {
                $group_leaders = (array) $group_leaders;
            }
            
            if (!is_array($group_members)) {
                $group_members = (array) $group_members;
            }
            
            // Get promo codes for this group
            $promo_codes = lilac_get_promo_codes_by_group($group_id);
            
            $groups[] = array(
                'id'           => $group_id,
                'title'        => get_the_title(),
                'leaders'      => $group_leaders,
                'members'      => $group_members,
                'member_count' => count($group_members),
                'promo_codes'  => $promo_codes,
                'code_count'   => count($promo_codes),
            );
        }
        wp_reset_postdata();
    }
    
    return $groups;
}
