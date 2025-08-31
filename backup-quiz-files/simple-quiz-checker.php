<?php
/**
 * Simple Quiz Checker - Shows the correct answers for a specific question
 */

// Bootstrap WordPress
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// Ensure only admins can access this script
if (!current_user_can('manage_options')) {
    die('Access denied.');
}

// Basic CSS
echo '<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
    .correct { background-color: #d4edda; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { background-color: #f2f2f2; }
</style>';

echo '<h1>LearnDash Quiz Question Checker</h1>';

// Get question ID from URL or default to the one in your HTML
$question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 42;

echo "<h2>Checking Question ID: $question_id</h2>";

// Get the question post
$question_post = get_post($question_id);
if (!$question_post) {
    die("Question not found with ID: $question_id");
}

echo "<p><strong>Question Title:</strong> " . esc_html($question_post->post_title) . "</p>";

// Get the Pro Quiz question ID
$pro_question_id = get_post_meta($question_id, 'question_pro_id', true);
if (!$pro_question_id) {
    die("Could not find Pro Quiz question ID for this question");
}

echo "<p><strong>Pro Quiz Question ID:</strong> $pro_question_id</p>";

// Direct SQL query to get answer data
global $wpdb;
$answer_data = $wpdb->get_var($wpdb->prepare(
    "SELECT answer_data FROM {$wpdb->prefix}learndash_pro_quiz_question WHERE id = %d",
    $pro_question_id
));

if (!$answer_data) {
    die("No answer data found for this question");
}

echo "<h3>Answer Data</h3>";

// Check if we're dealing with serialized data
if (is_serialized($answer_data)) {
    $answers = unserialize($answer_data);
    
    echo "<table>";
    echo "<tr><th>Answer Value</th><th>Answer Text</th><th>Correct?</th></tr>";
    
    foreach ($answers as $idx => $answer) {
        $is_correct = (isset($answer['correct']) && $answer['correct']) ? 'Yes ✓' : 'No';
        $row_class = (isset($answer['correct']) && $answer['correct']) ? ' class="correct"' : '';
        
        echo "<tr$row_class>";
        echo "<td>" . ($idx + 1) . "</td>";
        echo "<td>" . esc_html($answer['answer']) . "</td>";
        echo "<td>" . $is_correct . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    // If it's not serialized data, try to extract info another way
    echo "<p>Raw answer data (not serialized):</p>";
    echo "<pre>" . esc_html(print_r($answer_data, true)) . "</pre>";
}

// Get all questions for the quiz
$quiz_id = $wpdb->get_var($wpdb->prepare(
    "SELECT quiz_id FROM {$wpdb->prefix}learndash_pro_quiz_question WHERE id = %d",
    $pro_question_id
));

if ($quiz_id) {
    echo "<h3>All Questions in this Quiz</h3>";
    
    $questions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}learndash_pro_quiz_question WHERE quiz_id = %d ORDER BY sort",
        $quiz_id
    ));
    
    if (!empty($questions)) {
        echo "<ul>";
        foreach ($questions as $q) {
            $wp_question_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = 'question_pro_id' AND meta_value = %d 
                LIMIT 1",
                $q->id
            ));
            
            $current = ($q->id == $pro_question_id) ? ' style="font-weight:bold;color:blue;"' : '';
            
            echo "<li$current>";
            echo esc_html($q->title);
            if ($wp_question_id) {
                echo " - <a href='?question_id=$wp_question_id'>View details (ID: $wp_question_id)</a>";
            }
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No questions found for this quiz</p>";
    }
}
?>
