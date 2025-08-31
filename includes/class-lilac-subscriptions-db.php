<?php
/**
 * Lilac Subscriptions Database
 * 
 * Handles the database operations for user subscriptions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Lilac_Subscriptions_DB {
    private static $instance = null;
    private $table_name;
    private $db_version = '1.0';
    private $charset_collate;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'lilac_user_subscriptions';
        $this->charset_collate = $wpdb->get_charset_collate();
        
        add_action('plugins_loaded', array($this, 'maybe_create_table'));
    }

    public function maybe_create_table() {
        if (get_option('lilac_subscriptions_db_version') != $this->db_version) {
            $this->create_table();
        }
    }

    private function create_table() {
        global $wpdb;
        
        $sql = "CREATE TABLE {$this->table_name} (
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
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('lilac_subscriptions_db_version', $this->db_version);
    }

    public function add_subscription($data) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => 0,
            'product_id' => 0,
            'course_id' => 0,
            'access_started' => current_time('mysql'),
            'duration_days' => 30,
            'status' => 'active',
            'order_id' => 0,
            'metadata' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Calculate expiry date
        $access_started = strtotime($data['access_started']);
        $access_expires = strtotime('+' . intval($data['duration_days']) . ' days', $access_started);
        
        $insert_data = array(
            'user_id' => intval($data['user_id']),
            'product_id' => intval($data['product_id']),
            'course_id' => intval($data['course_id']),
            'access_started' => $data['access_started'],
            'access_expires' => date('Y-m-d H:i:s', $access_expires),
            'duration_days' => intval($data['duration_days']),
            'status' => sanitize_text_field($data['status']),
            'order_id' => intval($data['order_id']),
            'metadata' => is_array($data['metadata']) ? json_encode($data['metadata']) : $data['metadata']
        );
        
        $format = array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s');
        
        $result = $wpdb->insert($this->table_name, $insert_data, $format);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public function update_subscription($subscription_id, $data) {
        global $wpdb;
        
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            return false;
        }
        
        $update_data = array();
        $format = array();
        
        $fields = array(
            'status' => '%s',
            'access_started' => '%s',
            'access_expires' => '%s',
            'duration_days' => '%d',
            'metadata' => '%s'
        );
        
        foreach ($fields as $field => $field_format) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = $field_format;
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $subscription_id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    public function get_subscription($subscription_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $subscription_id),
            ARRAY_A
        );
    }
    
    public function get_user_subscriptions($user_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'course_id' => 0,
            'product_id' => 0,
            'orderby' => 'access_expires',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('user_id = %d');
        $values = array($user_id);
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        if (!empty($args['course_id'])) {
            $where[] = 'course_id = %d';
            $values[] = $args['course_id'];
        }
        
        if (!empty($args['product_id'])) {
            $where[] = 'product_id = %d';
            $values[] = $args['product_id'];
        }
        
        $sql = "SELECT * FROM {$this->table_name} WHERE " . implode(' AND ', $where);
        
        // Add sorting
        $orderby = in_array($args['orderby'], array('id', 'user_id', 'product_id', 'course_id', 'access_started', 'access_expires', 'created_at')) ? 
                 $args['orderby'] : 'access_expires';
        $order = in_array(strtoupper($args['order']), array('ASC', 'DESC')) ? $args['order'] : 'DESC';
        $sql .= " ORDER BY {$orderby} {$order}";
        
        // Add limit/offset
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d, %d", absint($args['offset']), absint($args['limit']));
        }
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    public function get_user_active_subscription($user_id, $course_id) {
        global $wpdb;
        
        $now = current_time('mysql');
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE user_id = %d 
                AND course_id = %d 
                AND status = 'active' 
                AND access_expires > %s 
                ORDER BY access_expires DESC 
                LIMIT 1",
                $user_id,
                $course_id,
                $now
            ),
            ARRAY_A
        );
    }
    
    public function has_active_subscription($user_id, $course_id) {
        $subscription = $this->get_user_active_subscription($user_id, $course_id);
        return !empty($subscription);
    }
    
    public function get_expiring_soon_subscriptions($days = 7) {
        global $wpdb;
        
        $now = current_time('mysql');
        $expiry_date = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status = 'active' 
                AND access_expires BETWEEN %s AND %s 
                ORDER BY access_expires ASC",
                $now,
                $expiry_date
            ),
            ARRAY_A
        );
    }
}

// Initialize the database table
function lilac_init_subscriptions_db() {
    return Lilac_Subscriptions_DB::get_instance();
}
add_action('plugins_loaded', 'lilac_init_subscriptions_db');
