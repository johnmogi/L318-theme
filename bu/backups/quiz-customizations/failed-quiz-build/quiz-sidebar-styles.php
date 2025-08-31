<?php
/**
 * Quiz Sidebar Styles for LearnDash
 * 
 * Contains styles for the media sidebar that appears alongside LearnDash quizzes
 * when the 'enable_rich_sidebar' option is set to 'yes'.
 * 
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>
<style>
    /* Quiz with sidebar container layout */
    .quiz-with-sidebar-wrapper .quiz-with-sidebar-container {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .quiz-with-sidebar-wrapper .quiz-main-content {
        flex: 1;
        min-width: 65%;
    }
    
    /* Media sidebar styling */
    .quiz-with-sidebar-wrapper .quiz-media-sidebar {
        width: 30%;
        position: sticky;
        top: 30px;
        max-height: 90vh;
        overflow-y: auto;
        align-self: flex-start;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }
    
    .quiz-with-sidebar-wrapper .quiz-media-sidebar h3 {
        font-size: 1.5rem;
        margin-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 10px;
    }
    
    /* Media item styling */
    .quiz-with-sidebar-wrapper .media-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .quiz-with-sidebar-wrapper .media-item-header {
        background: #f5f5f5;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }
    
    .quiz-with-sidebar-wrapper .media-item-header h4 {
        margin: 0;
        font-size: 1.1rem;
    }
    
    .quiz-with-sidebar-wrapper .media-item-content {
        padding: 15px;
    }
    
    /* Image styling */
    .quiz-with-sidebar-wrapper .media-item-image {
        margin-bottom: 15px;
    }
    
    .quiz-with-sidebar-wrapper .media-item-image img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        display: block;
    }
    
    /* Video styling */
    .quiz-with-sidebar-wrapper .media-item-video {
        margin-bottom: 15px;
    }
    
    .quiz-with-sidebar-wrapper .youtube-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
        height: 0;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    .quiz-with-sidebar-wrapper .youtube-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 4px;
    }
    
    .quiz-with-sidebar-wrapper .video-button {
        display: inline-block;
        padding: 8px 15px;
        background: #2a7ae2;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        text-align: center;
        transition: background 0.3s ease;
    }
    
    .quiz-with-sidebar-wrapper .video-button:hover {
        background: #1e5fa9;
    }
    
    /* Hint styling */
    .quiz-with-sidebar-wrapper .media-item-hint {
        margin-top: 15px;
        border-right: 3px solid #2a7ae2;
        padding: 10px 15px;
        background: #f0f8ff;
        border-radius: 4px;
    }
    
    /* RTL Support */
    html[dir="rtl"] .quiz-with-sidebar-wrapper .media-item-hint {
        border-right: 3px solid #2a7ae2;
        border-left: none;
    }
    
    /* Responsive styling */
    @media (max-width: 990px) {
        .quiz-with-sidebar-wrapper .quiz-with-sidebar-container {
            flex-direction: column;
        }
        
        .quiz-with-sidebar-wrapper .quiz-main-content,
        .quiz-with-sidebar-wrapper .quiz-media-sidebar {
            width: 100%;
        }
        
        .quiz-with-sidebar-wrapper .quiz-media-sidebar {
            position: static;
            margin-top: 30px;
        }
    }
</style>
