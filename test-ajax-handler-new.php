<?php
/**
 * Test script to verify AJAX handler registration
 * 
 * Access this file directly in your browser:
 * https://207lilac.local/wp-content/themes/hello-theme-child-master/test-ajax-handler-new.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Find WordPress
$wp_load = __DIR__ . '/../../../../wp-load.php';

if (!file_exists($wp_load)) {
    die('Error: Could not find wp-load.php. Tried: ' . $wp_load);
}

// Load WordPress
require_once($wp_load);

// Make sure we're loaded
if (!function_exists('add_action')) {
    die('WordPress did not load correctly');
}

// Only allow admins
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Include the AJAX handler file if it exists
$handler_file = __DIR__ . '/inc/ajax/teacher-students-export.php';
if (file_exists($handler_file)) {
    include_once $handler_file;
}

// Start output
?>
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Handler Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; max-width: 1200px; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545; }
        .info { color: #004085; background-color: #cce5ff; padding: 10px; margin: 5px 0; border-left: 4px solid #007bff; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .button { 
            display: inline-block; 
            padding: 8px 16px; 
            background: #0073aa; 
            color: white; 
            text-decoration: none; 
            border-radius: 3px; 
            margin: 5px 0;
        }
        .button:hover { background: #005177; }
    </style>
</head>
<body>
    <h1>🎯 Teacher Students Export - AJAX Handler Test</h1>
    
    <?php
    // Check if our action is registered
    $action = 'wp_ajax_export_teacher_students';
    $registered = has_action($action, 'handle_teacher_students_export');
    
    echo "<h2>🔍 AJAX Handler Registration</h2>";
    
    if ($registered !== false) {
        echo "<div class='success'>✅ AJAX handler is registered for action: <code>$action</code> (Priority: $registered)</div>";
    } else {
        echo "<div class='error'>❌ AJAX handler is NOT registered for action: <code>$action</code></div>";
    }
    
    // Check if the handler function exists
    if (function_exists('handle_teacher_students_export')) {
        echo "<div class='success'>✅ Handler function exists: <code>handle_teacher_students_export()</code></div>";
    } else {
        echo "<div class='error'>❌ Handler function does NOT exist: <code>handle_teacher_students_export()</code></div>";
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
    $test_url = add_query_arg([
        'action' => 'export_teacher_students',
        'nonce' => $nonce,
        'teacher_id' => get_current_user_id()
    ], $ajax_url);
    
    echo "<h2>🔗 Test AJAX Endpoint</h2>";
    echo "<div class='info'>";
    echo "<p>You can test the AJAX endpoint with this URL:</p>";
    echo "<p><code>" . esc_url($test_url) . "</code></p>";
    echo "<p>Or use this test button (opens in new tab):</p>";
    echo "<a href='" . esc_url($test_url) . "' target='_blank' class='button'>Test AJAX Endpoint</a>";
    echo "</div>";
    
    // List all AJAX actions
    global $wp_actions, $wp_filter;
    echo "<h2>📋 All Registered AJAX Actions</h2>";
    
    $ajax_actions = [];
    foreach ($wp_filter as $hook => $callbacks) {
        if (strpos($hook, 'wp_ajax') === 0) {
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
    ?>
    
    <h2>📄 File Verification</h2>
    <?php
    // Check if the handler file exists
    $handler_file = __DIR__ . '/inc/ajax/teacher-students-export.php';
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
        echo "</ul>
        <div class='info'><strong>File Path:</strong> <code>" . $handler_file . "</code></div>";
    } else {
        echo "<div class='error'>❌ Handler file does NOT exist: <code>$handler_file</code></div>";
    }
    ?>
    
    <h2>🔍 Debug Information</h2>
    <div class='info'>
        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
        <p><strong>Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?> v<?php echo wp_get_theme()->get('Version'); ?></p>
        <p><strong>Current User:</strong> <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)</p>
        <p><strong>User Capabilities:</strong> <?php 
            $caps = [];
            $user = wp_get_current_user();
            foreach ($user->allcaps as $cap => $has) {
                if ($has) $caps[] = $cap;
            }
            echo implode(', ', $caps);
        ?></p>
    </div>
</body>
</html>
