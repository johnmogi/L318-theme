<?php
/**
 * ACF Quiz Debug Tool
 * 
 * Add this URL parameter to any page to run the debug: ?acf_quiz_debug=1
 * Only administrators can see the output.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add the debug functionality hook
add_action('wp_footer', 'lilac_acf_quiz_debug_output', 9999);

function lilac_acf_quiz_debug_output() {
    // Only run for admins with the debug parameter
    if (!current_user_can('administrator') || empty($_GET['acf_quiz_debug'])) {
        return;
    }

    global $wpdb;
    ?>
    <style>
        .acf-debug-wrapper {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            font-family: monospace;
            direction: ltr;
            text-align: left;
            max-height: 500px;
            overflow: auto;
        }
        .acf-debug-wrapper h2 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
        }
        .acf-debug-wrapper pre {
            background: #fff;
            padding: 10px;
            border: 1px solid #eee;
            overflow: auto;
        }
        .acf-debug-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .acf-field-item {
            margin: 10px 0;
            padding: 5px;
            background: #eef6ff;
        }
    </style>
    
    <div class="acf-debug-wrapper">
        <h2>ACF Quiz Debug Tool</h2>
        
        <div class="acf-debug-section">
            <h3>1. LearnDash Post Types</h3>
            <?php
            $post_types = array(
                'sfwd-quiz' => 'Quizzes',
                'sfwd-question' => 'Questions',
                'sfwd-answer' => 'Answers'
            );
            
            foreach ($post_types as $post_type => $label) {
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", $post_type));
                echo "<p><strong>{$label}:</strong> {$count} found</p>";
            }
            ?>
        </div>
        
        <div class="acf-debug-section">
            <h3>2. List of Quiz Questions</h3>
            <?php
            $questions = $wpdb->get_results("
                SELECT ID, post_title, post_content 
                FROM {$wpdb->posts} 
                WHERE post_type = 'sfwd-question' 
                AND post_status = 'publish'
                LIMIT 20
            ");
            
            if (empty($questions)) {
                echo "<p>No questions found.</p>";
            } else {
                echo "<p>Found " . count($questions) . " questions (showing max 20):</p>";
                
                foreach ($questions as $question) {
                    echo "<div class='acf-field-item'>";
                    echo "<p><strong>Question ID:</strong> {$question->ID}</p>";
                    echo "<p><strong>Title:</strong> " . esc_html($question->post_title) . "</p>";
                    
                    // Check for ACF fields
                    if (function_exists('get_fields')) {
                        echo "<p><strong>ACF Fields:</strong></p>";
                        $fields = get_fields($question->ID);
                        
                        if ($fields) {
                            echo "<pre>" . print_r($fields, true) . "</pre>";
                        } else {
                            echo "<p>No ACF fields found for this question.</p>";
                        }
                    }
                    
                    // Check for quiz meta data
                    $question_pro_id = get_post_meta($question->ID, 'question_pro_id', true);
                    $quiz_id = get_post_meta($question->ID, 'quiz_id', true);
                    
                    echo "<p><strong>Question Pro ID:</strong> " . ($question_pro_id ? $question_pro_id : 'N/A') . "</p>";
                    echo "<p><strong>Quiz ID:</strong> " . ($quiz_id ? $quiz_id : 'N/A') . "</p>";
                    
                    // Check for related answers
                    $answers = $wpdb->get_results($wpdb->prepare("
                        SELECT answer_text, answer_type, points_awarded 
                        FROM {$wpdb->prefix}learndash_pro_quiz_question
                        LEFT JOIN {$wpdb->prefix}learndash_pro_quiz_question_answers ON {$wpdb->prefix}learndash_pro_quiz_question.id = {$wpdb->prefix}learndash_pro_quiz_question_answers.question_id
                        WHERE question_post_id = %d
                    ", $question->ID));
                    
                    if (!empty($answers)) {
                        echo "<p><strong>Related Answers (" . count($answers) . "):</strong></p>";
                        echo "<pre>" . print_r($answers, true) . "</pre>";
                    } else {
                        echo "<p>No related answers found in the database.</p>";
                    }
                    
                    echo "</div>";
                }
            }
            ?>
        </div>
        
        <div class="acf-debug-section">
            <h3>3. ACF Field Groups for Questions</h3>
            <?php
            if (function_exists('acf_get_field_groups')) {
                $field_groups = acf_get_field_groups();
                $question_field_groups = array();
                
                foreach ($field_groups as $field_group) {
                    if (isset($field_group['location']) && is_array($field_group['location'])) {
                        foreach ($field_group['location'] as $group) {
                            foreach ($group as $rule) {
                                if ($rule['param'] == 'post_type' && $rule['operator'] == '==' && $rule['value'] == 'sfwd-question') {
                                    $question_field_groups[] = $field_group;
                                }
                            }
                        }
                    }
                }
                
                if (empty($question_field_groups)) {
                    echo "<p>No ACF field groups found for Question post type.</p>";
                } else {
                    foreach ($question_field_groups as $group) {
                        echo "<div class='acf-field-item'>";
                        echo "<p><strong>Field Group:</strong> " . esc_html($group['title']) . "</p>";
                        echo "<p><strong>Key:</strong> " . esc_html($group['key']) . "</p>";
                        
                        if (function_exists('acf_get_fields')) {
                            $fields = acf_get_fields($group['key']);
                            
                            if (!empty($fields)) {
                                echo "<p><strong>Fields:</strong></p>";
                                echo "<ul>";
                                foreach ($fields as $field) {
                                    echo "<li>" . esc_html($field['label']) . " (" . $field['name'] . ") - Type: " . $field['type'] . "</li>";
                                }
                                echo "</ul>";
                            }
                        }
                        
                        echo "</div>";
                    }
                }
            } else {
                echo "<p>ACF functions not available. Is ACF plugin active?</p>";
            }
            ?>
        </div>
        
        <div class="acf-debug-section">
            <h3>4. Direct Database Query for ACF Fields</h3>
            <?php
            // Find ACF fields in the postmeta table
            $acf_meta = $wpdb->get_results("
                SELECT p.ID, p.post_title, pm.meta_key, pm.meta_value
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'sfwd-question'
                AND pm.meta_key LIKE 'add_hint'
                OR pm.meta_key LIKE 'custom_answer_hint'
                OR pm.meta_key LIKE 'rich_media'
                OR pm.meta_key LIKE 'extended_hint'
                OR pm.meta_key LIKE '_add_hint'
                OR pm.meta_key LIKE '_custom_answer_hint'
                OR pm.meta_key LIKE '_rich_media'
                OR pm.meta_key LIKE '_extended_hint'
                LIMIT 20
            ");
            
            if (empty($acf_meta)) {
                echo "<p>No ACF fields found in the database for the specified field names.</p>";
                echo "<p>Try checking if ACF fields are using different meta_key names.</p>";
                
                // Show all meta keys for questions to help identify the right ones
                $all_question_meta_keys = $wpdb->get_col("
                    SELECT DISTINCT pm.meta_key
                    FROM {$wpdb->posts} p
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'sfwd-question'
                    AND pm.meta_key NOT LIKE '\_%'
                    ORDER BY pm.meta_key
                    LIMIT 50
                ");
                
                if (!empty($all_question_meta_keys)) {
                    echo "<p>Available meta keys for questions (showing max 50):</p>";
                    echo "<pre>" . print_r($all_question_meta_keys, true) . "</pre>";
                }
            } else {
                echo "<p>Found ACF fields in the database:</p>";
                echo "<pre>" . print_r($acf_meta, true) . "</pre>";
            }
            ?>
        </div>
        
        <div class="acf-debug-section">
            <h3>5. WordPress Debug Information</h3>
            <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
            <p><strong>ACF Version:</strong> <?php echo class_exists('ACF') ? ACF()->version : 'Not Active'; ?></p>
            <p><strong>LearnDash Version:</strong> <?php echo defined('LEARNDASH_VERSION') ? LEARNDASH_VERSION : 'Not Active'; ?></p>
            <p><strong>Active Theme:</strong> <?php echo wp_get_theme()->get('Name'); ?></p>
        </div>
    </div>
    <?php
}
