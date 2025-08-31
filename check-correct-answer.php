<?php
// A simple script to check the correct answer for a specific question

// Load WordPress
require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

// Question ID to check (from your HTML: question_post_id=42)
$question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 42;

echo "<h1>Checking correct answer for question ID: {$question_id}</h1>";

// Get the question from WordPress
$question = get_post($question_id);
if (!$question) {
    echo "<p>Error: Question not found with ID {$question_id}</p>";
    exit;
}

echo "<h2>Question: " . esc_html($question->post_title) . "</h2>";

// Get question data from WPProQuiz
global $wpdb;
$pro_quiz_id = get_post_meta($question_id, 'question_pro_id', true);

if (!$pro_quiz_id) {
    echo "<p>Error: Could not find pro_quiz_id for this question</p>";
    exit;
}

echo "<p>Question Pro ID: " . esc_html($pro_quiz_id) . "</p>";

// Query the wpProQuiz tables to get answer data
$answers_data = $wpdb->get_var($wpdb->prepare(
    "SELECT answer_data FROM {$wpdb->prefix}wp_pro_quiz_question WHERE id = %d",
    $pro_quiz_id
));

if ($answers_data) {
    echo "<h3>Answer Data:</h3>";
    echo "<pre>";
    $answers = maybe_unserialize($answers_data);
    print_r($answers);
    echo "</pre>";
    
    // For a cleaner display
    echo "<h3>Correct Answers:</h3>";
    echo "<ul>";
    foreach ($answers as $index => $answer) {
        if (isset($answer['correct']) && $answer['correct']) {
            echo "<li><strong>Answer " . ($index + 1) . ":</strong> " . esc_html($answer['answer']) . " (is correct)</li>";
        } else {
            echo "<li>Answer " . ($index + 1) . ": " . esc_html($answer['answer']) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>Error: Could not find answer data for this question</p>";
}

?>
