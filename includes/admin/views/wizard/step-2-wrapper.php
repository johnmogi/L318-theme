<?php
// Initialize variables
$new_class_name = isset($_POST['new_class_name']) ? sanitize_text_field($_POST['new_class_name']) : '';

// Include the original step-2.php file
include_once(__DIR__ . '/step-2.php');
