<?php
/**
 * Test script to verify AJAX handler registration
 * 
 * Access this file directly in your browser:
 * https://207lilac.local/wp-content/themes/hello-theme-child-master/test-ajax-handler.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set WordPress environment mode
define('WP_USE_THEMES', false);

// Path to wp-load.php - adjust this path if needed
$wp_load = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Debug: Output the path we're trying to load
error_log("Trying to load WordPress from: " . $wp_load);

if (file_exists($wp_load)) {
    require_once($wp_load);
    
    // Make sure WordPress is loaded
    if (!function_exists('add_action')) {
        die('WordPress is not loaded properly');
    }
    
    // Make sure we're logged in as admin
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        auth_redirect();
        exit;
    }
} else {
    die('Error: Could not find wp-load.php');
}

// Include the AJAX handler file if it exists
$handler_file = get_stylesheet_directory() . '/inc/ajax/teacher-students-export.php';
if (file_exists($handler_file)) {
    include_once $handler_file;
}

// Check if the action is registered
function test_ajax_handler_registration() {
    global $wp_actions, $wp_filter;
    
    echo "<style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545; }
        .info { color: #004085; background-color: #cce5ff; padding: 10px; margin: 5px 0; border-left: 4px solid #007bff; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
    </style>";
    
    echo "<h1>🎯 Teacher Students Export - AJAX Handler Test</h1>";
    
    // Check if our action is registered
    $action = 'wp_ajax_export_teacher_students';
    $registered = has_action($action, 'handle_teacher_students_export');
    
    echo "<h2>🔍 AJAX Handler Registration</h2>";
    
    if ($registered !== false) {
        echo "<div class='success'>✅ AJAX handler is registered for action: <code>$action</code> (Priority: $registered)</div>";
    } else {
        echo "<div class='error'>❌ AJAX handler is NOT registered for action: <code>$action</code></div>";
    }
    
    // Check if the file exists
    $handler_file = get_stylesheet_directory() . '/inc/ajax/teacher-students-export.php';
    if (file_exists($handler_file)) {
        echo "<div class='success'>✅ Handler file exists: <code>" . basename($handler_file) . "</code></div>";
        
        // Check file contents
        $file_contents = file_get_contents($handler_file);
        $has_function = strpos($file_contents, 'function handle_teacher_students_export') !== false;
        $has_add_action = strpos($file_contents, 'add_action') !== false && 
                         (strpos($file_contents, 'wp_ajax_export_teacher_students') !== false ||
                          strpos($file_contents, 'wp_ajax_nopriv_export_teacher_students') !== false);
        
        echo "<div class='info'>📄 File Analysis:</div>";
        echo "<ul>";
        echo $has_function ? 
             "<li>✅ Contains handler function: <code>handle_teacher_students_export()</code></li>" : 
             "<li class='error'>❌ Missing handler function: <code>handle_teacher_students_export()</code></li>";
        echo $has_add_action ? 
             "<li>✅ Contains add_action for AJAX handler</li>" : 
             "<li class='error'>❌ Missing add_action for AJAX handler</li>";
        echo "</ul>";
    } else {
        echo "<div class='error'>❌ Handler file does NOT exist: <code>$handler_file</code></div>";
    }
    
    // Check if the loader file exists
    $loader_file = get_stylesheet_directory() . '/inc/ajax/load-ajax-handlers.php';
    if (file_exists($loader_file)) {
        echo "<div class='success'>✅ Loader file exists: <code>" . basename($loader_file) . "</code></div>";
        
        // Check loader contents
        $loader_contents = file_get_contents($loader_file);
        $includes_handler = strpos($loader_contents, 'teacher-students-export.php') !== false;
        
        echo "<div class='info'>📄 Loader Analysis:</div>";
        echo $includes_handler ? 
             "<div class='success'>✅ Loader includes the teacher-students-export.php file</div>" : 
             "<div class='error'>❌ Loader does NOT include the teacher-students-export.php file</div>";
    } else {
        echo "<div class='error'>❌ Loader file does NOT exist: <code>$loader_file</code></div>";
    }
    
    // Check if the handler function exists
    $function_exists = function_exists('handle_teacher_students_export');
    if ($function_exists) {
        echo "<div class='success'>✅ Handler function exists: <code>handle_teacher_students_export()</code></div>";
        
        // Test function reflection
        try {
            $reflection = new ReflectionFunction('handle_teacher_students_export');
            $params = $reflection->getParameters();
            $param_list = [];
            
            foreach ($params as $param) {
                $param_str = '';
                if ($param->hasType()) {
                    $param_str .= $param->getType() . ' ';
                }
                $param_str .= '$' . $param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $param_str .= ' = ' . json_encode($param->getDefaultValue());
                }
                $param_list[] = $param_str;
            }
            
            echo "<div class='info'>📋 Function Signature: <code>handle_teacher_students_export(" . implode(', ', $param_list) . ")</code></div>";
            
        } catch (ReflectionException $e) {
            echo "<div class='error'>❌ Could not analyze function: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Handler function does NOT exist: <code>handle_teacher_students_export()</code></div>";
    }
    
    // Check if the action is hooked
    echo "<h2>🔗 Hooked Actions</h2>";
    
    if (isset($wp_actions[$action])) {
        echo "<div class='success'>✅ Action <code>$action</code> has been triggered " . $wp_actions[$action] . " time(s)</div>";
    } else {
        echo "<div class='error'>❌ Action <code>$action</code> has NOT been triggered</div>";
    }
    
    // Check all registered AJAX actions
    echo "<h3>📋 All Registered AJAX Actions:</h3>";
    
    $ajax_actions = [];
    foreach ($wp_filter as $hook => $callbacks) {
        if (strpos($hook, 'wp_ajax') === 0 || strpos($hook, 'admin_post') === 0) {
            $ajax_actions[$hook] = [];
            
            if (isset($wp_filter[$hook]->callbacks)) {
                foreach ($wp_filter[$hook]->callbacks as $priority => $functions) {
                    foreach ($functions as $function) {
                        $function_name = '';
                        if (is_string($function['function'])) {
                            $function_name = $function['function'];
                        } elseif (is_array($function['function'])) {
                            $class = is_object($function['function'][0]) ? 
                                    get_class($function['function'][0]) : $function['function'][0];
                            $function_name = $class . '->' . $function['function'][1];
                        } else {
                            $function_name = 'Closure';
                        }
                        
                        $ajax_actions[$hook][] = [
                            'priority' => $priority,
                            'function' => $function_name,
                            'accepted_args' => $function['accepted_args']
                        ];
                    }
                }
            }
        }
    }
    
    if (!empty($ajax_actions)) {
        echo "<table>";
        echo "<tr><th>Hook</th><th>Priority</th><th>Function</th><th>Args</th></tr>";
        
        foreach ($ajax_actions as $hook => $callbacks) {
            $first = true;
            $row_count = count($callbacks);
            
            foreach ($callbacks as $callback) {
                $highlight = $hook === $action ? ' style="background-color: #fff3cd;"' : '';
                echo "<tr$highlight>";
                
                if ($first) {
                    echo "<td rowspan='$row_count'><code>$hook</code></td>";
                    $first = false;
                }
                
                echo "<td>{$callback['priority']}</td>";
                echo "<td><code>{$callback['function']}</code></td>";
                echo "<td>{$callback['accepted_args']}</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
    } else {
        echo "<div class='info'>No AJAX actions found.</div>";
    }
    
    // Nonce test
    $nonce_action = 'export_teacher_students_' . get_current_user_id();
    $nonce = wp_create_nonce($nonce_action);
    
    echo "<h2>🔑 Nonce Verification</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Action:</strong> <code>$nonce_action</code></p>";
    echo "<p><strong>Nonce:</strong> <code>$nonce</code></p>";
    echo "<p><strong>Verification:</strong> " . 
         (wp_verify_nonce($nonce, $nonce_action) ? 
          '✅ <span style="color: #155724;">Valid</span>' : 
          '❌ <span style="color: #721c24;">Invalid</span>') . "</p>";
    echo "</div>";
    
    // Test AJAX URL
    $ajax_url = admin_url('admin-ajax.php');
    echo "<h2>🔗 Test AJAX Endpoint</h2>";
    echo "<div class='info'>";
    echo "<p>You can test the AJAX endpoint with this URL:</p>";
    echo "<p><code>$ajax_url?action=export_teacher_students&nonce=$nonce&teacher_id=" . get_current_user_id() . "</code></p>";
    echo "<p>Or use this test button (opens in new tab):</p>";
    echo "<a href='$ajax_url?action=export_teacher_students&nonce=$nonce&teacher_id=" . get_current_user_id() . "' ";
    echo "target='_blank' class='button button-primary'>Test AJAX Endpoint</a>";
    echo "</div>";
}

// Run the test
if (is_user_logged_in() && current_user_can('manage_options')) {
    test_ajax_handler_registration();
} else {
    wp_die('You must be logged in as an administrator to run this test.');
}
