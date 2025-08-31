<?php
/**
 * User Subscriptions Shortcode
 * 
 * Displays a table of the user's course subscriptions with access details
 * 
 * @package Hello_Child_Theme
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure the database table is created
add_action('init', 'lilac_maybe_create_subscriptions_table');
function lilac_maybe_create_subscriptions_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lilac_user_subscriptions';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            access_started datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            access_expires datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            duration_days int(11) NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            order_id bigint(20) DEFAULT 0,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY product_id (product_id),
            KEY course_id (course_id),
            KEY status (status),
            KEY access_expires (access_expires)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add version to help with future updates
        add_option('lilac_subscriptions_db_version', '1.0');
    }
}

/**
 * User Subscriptions Shortcode
 * 
 * Usage: [llm_user_subscriptions debug="no"]
 * 
 * @param array $atts Shortcode attributes
 * @return string Output HTML
 */
function llm_user_subscriptions_shortcode($atts) {
    global $wpdb;
    
    // Only show to logged in users
    if (!is_user_logged_in()) {
        return '<p class="lilac-login-required">' . __('Please log in to view your subscriptions.', 'lilac') . '</p>';
    }
    
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'lilac_user_subscriptions';
    
    // Get shortcode attributes
    $atts = shortcode_atts(array(
        'debug' => 'no',
    ), $atts, 'llm_user_subscriptions');
    
    $debug_mode = $atts['debug'] === 'yes' && current_user_can('manage_options');
    
    // Get user's subscriptions from the database
    $subscriptions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE user_id = %d 
        ORDER BY 
            CASE 
                WHEN access_expires > %s THEN 0 
                ELSE 1 
            END,
            access_expires DESC",
        $user_id,
        current_time('mysql', true)
    ));
    
    // Start output buffering
    ob_start();
    
    // Debug info
    if ($debug_mode) {
        echo '<div class="lilac-debug" style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">';
        echo '<h4>Debug: User Subscriptions</h4>';
        echo '<pre>Query: ' . $wpdb->last_query . '</pre>';
        echo '<pre>Results: ' . print_r($subscriptions, true) . '</pre>';
        echo '</div>';
    }
    
    // If no subscriptions found
    if (empty($subscriptions)) {
        return '<div class="lilac-no-subscriptions">' . 
               '<p>' . __('You do not have any active subscriptions.', 'lilac') . '</p>' .
               '</div>';
    }
    
    // Group subscriptions by course
    $grouped_subscriptions = array();
    foreach ($subscriptions as $sub) {
        if (!isset($grouped_subscriptions[$sub->course_id])) {
            $grouped_subscriptions[$sub->course_id] = array();
        }
        $grouped_subscriptions[$sub->course_id][] = $sub;
    }
    ?>
    <div class="lilac-user-subscriptions">
        <h3><?php _e('Your Course Access', 'lilac'); ?></h3>
        
        <?php foreach ($grouped_subscriptions as $course_id => $course_subs) : 
            $course = get_post($course_id);
            if (!$course) continue;
            
            $course_url = get_permalink($course_id);
            $has_active = false;
            
            // Check if any subscription is active for this course
            foreach ($course_subs as $sub) {
                if (strtotime($sub->access_expires) > current_time('timestamp')) {
                    $has_active = true;
                    break;
                }
            }
            ?>
            <div class="lilac-course-subscription">
                <div class="course-header">
                    <h4>
                        <a href="<?php echo esc_url($course_url); ?>">
                            <?php echo esc_html($course->post_title); ?>
                        </a>
                        <?php if ($has_active) : ?>
                            <span class="status-badge status-active">
                                <?php _e('Active', 'lilac'); ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                </div>
                
                <div class="subscription-details">
                    <table class="lilac-subscriptions-table">
                        <thead>
                            <tr>
                                <th><?php _e('Access Type', 'lilac'); ?></th>
                                <th><?php _e('Duration', 'lilac'); ?></th>
                                <th><?php _e('Start Date', 'lilac'); ?></th>
                                <th><?php _e('Expiry Date', 'lilac'); ?></th>
                                <th><?php _e('Status', 'lilac'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_subs as $sub) : 
                                $product = wc_get_product($sub->product_id);
                                $is_active = strtotime($sub->access_expires) > current_time('timestamp');
                                $status_class = $is_active ? 'status-active' : 'status-expired';
                                $status_text = $is_active ? __('Active', 'lilac') : __('Expired', 'lilac');
                                $start_date = date_i18n(get_option('date_format'), strtotime($sub->access_started));
                                $expiry_date = date_i18n(get_option('date_format'), strtotime($sub->access_expires));
                                $days_remaining = $is_active ? max(0, ceil((strtotime($sub->access_expires) - current_time('timestamp')) / DAY_IN_SECONDS)) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php echo $product ? esc_html($product->get_name()) : __('N/A', 'lilac'); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(sprintf(_n('%d day', '%d days', $sub->duration_days, 'lilac'), $sub->duration_days)); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($start_date); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($expiry_date); ?>
                                        <?php if ($is_active && $days_remaining <= 7) : ?>
                                            <div class="expiry-notice">
                                                <?php printf(_n('Expires in %d day', 'Expires in %d days', $days_remaining, 'lilac'), $days_remaining); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html($status_text); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="course-actions">
                        <?php if ($has_active) : ?>
                            <a href="<?php echo esc_url($course_url); ?>" class="button">
                                <?php _e('Continue Learning', 'lilac'); ?>
                            </a>
                        <?php else : ?>
                            <?php 
                            // Get the product with the longest duration for renewal
                            $renewal_product_id = 0;
                            $max_duration = 0;
                            foreach ($course_subs as $sub) {
                                if ($sub->product_id && $sub->duration_days > $max_duration) {
                                    $max_duration = $sub->duration_days;
                                    $renewal_product_id = $sub->product_id;
                                }
                            }
                            if ($renewal_product_id && ($product = wc_get_product($renewal_product_id))) : ?>
                                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="button alt">
                                    <?php _e('Renew Access', 'lilac'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
            
        <style>
        .lilac-user-subscriptions {
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .lilac-course-subscription {
            margin-bottom: 30px;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
        }
        .course-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .course-header h4 {
            margin: 0;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .subscription-details {
            padding: 15px 20px;
        }
        .lilac-subscriptions-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .lilac-subscriptions-table th,
        .lilac-subscriptions-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .lilac-subscriptions-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .status-expired {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            opacity: 0.7;
        }
        .expiry-notice {
            font-size: 0.8em;
            color: #d32f2f;
            margin-top: 3px;
            font-weight: 500;
        }
        .course-actions {
            margin-top: 15px;
            text-align: right;
        }
        .button {
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 2.15384615;
            min-height: 30px;
            margin: 0 5px 5px 0;
            padding: 0 10px;
            cursor: pointer;
            border-width: 1px;
            border-style: solid;
            border-radius: 3px;
            white-space: nowrap;
            box-sizing: border-box;
            background: #f7f7f7;
            border-color: #ccc;
            color: #555;
            vertical-align: top;
        }
        .button.alt {
            background: #0073aa;
            border-color: #006291;
            color: #fff;
            text-shadow: 0 -1px 1px #006291, 1px 0 1px #006291, 0 1px 1px #006291, -1px 0 1px #006291;
        }
        .button.alt:hover {
            background: #008ec2;
            border-color: #006291;
            color: #fff;
        }
        @media (max-width: 768px) {
            .lilac-subscriptions-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .lilac-course-subscription {
                margin-left: -15px;
                margin-right: -15px;
                border-left: none;
                border-right: none;
                border-radius: 0;
            }
            .course-header {
                padding: 12px 15px;
            }
            .subscription-details {
                padding: 12px 15px;
            }
            .course-actions {
                text-align: center;
            }
            .button {
                width: 100%;
                margin-bottom: 8px;
            }
        }
        </style>
    </div>
    <?php
    
    // Get the buffered content and clean the buffer
    $output = ob_get_clean();
    
    return $output;
}
add_shortcode('llm_user_subscriptions', 'llm_user_subscriptions_shortcode');
