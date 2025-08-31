<?php
/**
 * Registration Code Class
 * Represents a single registration code with its properties and operations
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Registration_Code
 * 
 * Represents a single registration code entity
 */
class Registration_Code {
    /**
     * @var int The code ID
     */
    private $id;
    
    /**
     * @var string The actual code string
     */
    private $code;
    
    /**
     * @var string User role to assign when using this code
     */
    private $role;
    
    /**
     * @var string Group name this code belongs to
     */
    private $group_name;
    
    /**
     * @var int|null Course ID this code is associated with
     */
    private $course_id;
    
    /**
     * @var int Maximum number of times this code can be used
     */
    private $max_uses;
    
    /**
     * @var int Number of times this code has been used
     */
    private $used_count;
    
    /**
     * @var string|null Expiration date for the code
     */
    private $expiry_date;
    
    /**
     * @var bool Whether the code is used or not
     */
    private $is_used;
    
    /**
     * @var int|null User ID that last used this code
     */
    private $used_by;
    
    /**
     * @var string|null Timestamp when the code was used
     */
    private $used_at;
    
    /**
     * @var string Timestamp when the code was created
     */
    private $created_at;
    
    /**
     * @var int User ID that created this code
     */
    private $created_by;
    
    /**
     * @var Registration_DB_Manager The database manager instance
     */
    private $db_manager;
    
    /**
     * Constructor
     * 
     * @param object|array|int $code Code data, code ID, or empty for new code
     */
    public function __construct($code = null) {
        $this->db_manager = Registration_DB_Manager::get_instance();
        
        if (is_object($code)) {
            $this->load_from_object($code);
        } elseif (is_array($code)) {
            $this->load_from_array($code);
        } elseif (is_numeric($code)) {
            $this->load($code);
        } else {
            // Initialize a new code
            $this->code = $this->db_manager->generate_unique_code();
            $this->role = 'subscriber';
            $this->group_name = '';
            $this->course_id = null;
            $this->max_uses = 1;
            $this->used_count = 0;
            $this->expiry_date = null;
            $this->is_used = false;
            $this->used_by = null;
            $this->used_at = null;
            $this->created_at = current_time('mysql');
            $this->created_by = get_current_user_id();
        }
    }
    
    /**
     * Load code data by ID
     * 
     * @param int $id Code ID
     * @return bool Success status
     */
    public function load($id) {
        $code_data = $this->db_manager->get_code_by_id($id);
        
        if ($code_data) {
            $this->load_from_object($code_data);
            return true;
        }
        
        return false;
    }
    
    /**
     * Load code data from database object
     * 
     * @param object $code_data Code data from database
     */
    private function load_from_object($code_data) {
        $this->id = $code_data->id;
        $this->code = $code_data->code;
        $this->role = $code_data->role;
        $this->group_name = $code_data->group_name;
        $this->course_id = $code_data->course_id;
        $this->max_uses = $code_data->max_uses;
        $this->used_count = $code_data->used_count;
        $this->expiry_date = $code_data->expiry_date;
        $this->is_used = (bool)$code_data->is_used;
        $this->used_by = $code_data->used_by;
        $this->used_at = $code_data->used_at;
        $this->created_at = $code_data->created_at;
        $this->created_by = $code_data->created_by;
    }
    
    /**
     * Load code data from array
     * 
     * @param array $data Code data as array
     */
    private function load_from_array($data) {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->code = isset($data['code']) ? $data['code'] : $this->db_manager->generate_unique_code();
        $this->role = isset($data['role']) ? $data['role'] : 'subscriber';
        $this->group_name = isset($data['group_name']) ? $data['group_name'] : '';
        $this->course_id = isset($data['course_id']) ? $data['course_id'] : null;
        $this->max_uses = isset($data['max_uses']) ? $data['max_uses'] : 1;
        $this->used_count = isset($data['used_count']) ? $data['used_count'] : 0;
        $this->expiry_date = isset($data['expiry_date']) ? $data['expiry_date'] : null;
        $this->is_used = isset($data['is_used']) ? (bool)$data['is_used'] : false;
        $this->used_by = isset($data['used_by']) ? $data['used_by'] : null;
        $this->used_at = isset($data['used_at']) ? $data['used_at'] : null;
        $this->created_at = isset($data['created_at']) ? $data['created_at'] : current_time('mysql');
        $this->created_by = isset($data['created_by']) ? $data['created_by'] : get_current_user_id();
    }
    
    /**
     * Save the code to the database
     * 
     * @return bool Success status
     */
    public function save() {
        $data = [
            'code' => $this->code,
            'role' => $this->role,
            'group_name' => $this->group_name,
            'course_id' => $this->course_id,
            'max_uses' => $this->max_uses,
            'used_count' => $this->used_count,
            'expiry_date' => $this->expiry_date,
            'is_used' => $this->is_used ? 1 : 0,
            'used_by' => $this->used_by,
            'used_at' => $this->used_at,
            'created_by' => $this->created_by
        ];
        
        if ($this->id) {
            // Update existing code
            $result = $this->db_manager->update_code($this->id, $data);
            return $result;
        } else {
            // Create new code
            $result = $this->db_manager->create_code($data);
            if ($result) {
                $this->id = $result;
                return true;
            }
            return false;
        }
    }
    
    /**
     * Delete the code from the database
     * 
     * @return bool Success status
     */
    public function delete() {
        if (!$this->id) {
            return false;
        }
        
        return $this->db_manager->delete_code($this->id);
    }
    
    /**
     * Mark this code as used by a specific user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function mark_as_used($user_id) {
        if (!$this->id || !$this->code) {
            return false;
        }
        
        $result = $this->db_manager->mark_code_used($this->code, $user_id);
        
        if ($result) {
            // Update local properties
            $this->used_by = $user_id;
            $this->used_at = current_time('mysql');
            $this->used_count++;
            
            if ($this->max_uses <= 1 || $this->used_count >= $this->max_uses) {
                $this->is_used = true;
            }
        }
        
        return $result;
    }
    
    /**
     * Check if this code is valid for use
     * 
     * @return array Validation result
     */
    public function validate() {
        if (!$this->code) {
            return ['is_valid' => false, 'message' => 'קוד ריק'];
        }
        
        // Check if code is single-use and already used
        if ($this->max_uses <= 1 && $this->is_used) {
            return ['is_valid' => false, 'message' => 'קוד הרשמה כבר בשימוש'];
        }
        
        // Check for multi-use codes
        if ($this->max_uses > 1 && $this->used_count >= $this->max_uses) {
            return ['is_valid' => false, 'message' => 'קוד הרשמה נוצל את מספר הפעמים המקסימלי'];
        }
        
        // Check expiry date
        if (!empty($this->expiry_date) && strtotime($this->expiry_date) < time()) {
            return ['is_valid' => false, 'message' => 'קוד הרשמה פג תוקף'];
        }
        
        return [
            'is_valid' => true,
            'code' => $this,
            'message' => 'קוד תקין'
        ];
    }
    
    /**
     * Convert the code to an array
     * 
     * @return array Code data as array
     */
    public function to_array() {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'role' => $this->role,
            'group_name' => $this->group_name,
            'course_id' => $this->course_id,
            'max_uses' => $this->max_uses,
            'used_count' => $this->used_count,
            'expiry_date' => $this->expiry_date,
            'is_used' => $this->is_used,
            'used_by' => $this->used_by,
            'used_at' => $this->used_at,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by
        ];
    }
    
    // Getters and Setters
    
    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * @return string
     */
    public function get_code() {
        return $this->code;
    }
    
    /**
     * @param string $code
     * @return Registration_Code
     */
    public function set_code($code) {
        $this->code = $code;
        return $this;
    }
    
    /**
     * @return string
     */
    public function get_role() {
        return $this->role;
    }
    
    /**
     * @param string $role
     * @return Registration_Code
     */
    public function set_role($role) {
        $this->role = $role;
        return $this;
    }
    
    /**
     * @return string
     */
    public function get_group_name() {
        return $this->group_name;
    }
    
    /**
     * @param string $group_name
     * @return Registration_Code
     */
    public function set_group_name($group_name) {
        $this->group_name = $group_name;
        return $this;
    }
    
    /**
     * @return int|null
     */
    public function get_course_id() {
        return $this->course_id;
    }
    
    /**
     * @param int|null $course_id
     * @return Registration_Code
     */
    public function set_course_id($course_id) {
        $this->course_id = $course_id;
        return $this;
    }
    
    /**
     * @return int
     */
    public function get_max_uses() {
        return $this->max_uses;
    }
    
    /**
     * @param int $max_uses
     * @return Registration_Code
     */
    public function set_max_uses($max_uses) {
        $this->max_uses = $max_uses;
        return $this;
    }
    
    /**
     * @return int
     */
    public function get_used_count() {
        return $this->used_count;
    }
    
    /**
     * @param int $used_count
     * @return Registration_Code
     */
    public function set_used_count($used_count) {
        $this->used_count = $used_count;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function get_expiry_date() {
        return $this->expiry_date;
    }
    
    /**
     * @param string|null $expiry_date
     * @return Registration_Code
     */
    public function set_expiry_date($expiry_date) {
        $this->expiry_date = $expiry_date;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function is_used() {
        return $this->is_used;
    }
    
    /**
     * @param bool $is_used
     * @return Registration_Code
     */
    public function set_is_used($is_used) {
        $this->is_used = (bool)$is_used;
        return $this;
    }
    
    /**
     * @return int|null
     */
    public function get_used_by() {
        return $this->used_by;
    }
    
    /**
     * @param int|null $used_by
     * @return Registration_Code
     */
    public function set_used_by($used_by) {
        $this->used_by = $used_by;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function get_used_at() {
        return $this->used_at;
    }
    
    /**
     * @param string|null $used_at
     * @return Registration_Code
     */
    public function set_used_at($used_at) {
        $this->used_at = $used_at;
        return $this;
    }
    
    /**
     * @return string
     */
    public function get_created_at() {
        return $this->created_at;
    }
    
    /**
     * @return int
     */
    public function get_created_by() {
        return $this->created_by;
    }
    
    /**
     * @param int $created_by
     * @return Registration_Code
     */
    public function set_created_by($created_by) {
        $this->created_by = $created_by;
        return $this;
    }
}
