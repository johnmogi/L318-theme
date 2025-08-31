<?php
/**
 * Quiz Styles for Media Sidebar
 * 
 * Contains all CSS styles for the quiz media sidebar layout.
 * Separated into a modular file per user's folder-based preference.
 * 
 * @package LilacChildTheme
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>
<style>
    .quiz-with-sidebar-container {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .quiz-main-content {
        flex: 1;
        min-width: 65%;
        padding: 0 15px;
    }
    
    .quiz-media-sidebar {
        width: 30%;
        padding: 0 15px;
        position: sticky;
        top: 30px;
        align-self: flex-start;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .media-item {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .media-item-header {
        background: #f5f5f5;
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }
    
    .media-item-content {
        padding: 15px;
    }
    
    .media-item-image img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
    }
    
    .media-item-video {
        margin-top: 10px;
    }
    
    .media-item-hint {
        margin-top: 15px;
        border-left: 3px solid #2a7ae2;
        padding: 10px 15px;
        background: #f0f8ff;
    }
    
    .hint-indicator {
        font-weight: bold;
        margin-bottom: 8px;
    }
    
    .video-button {
        display: inline-block;
        padding: 8px 15px;
        background: #2a7ae2;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        text-align: center;
    }
    
    /* YouTube Embed Responsive Container */
    .youtube-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 */
        height: 0;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    .youtube-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 4px;
    }
    
    /* RTL Support */
    html[dir="rtl"] .media-item-hint {
        border-left: none;
        border-right: 3px solid #2a7ae2;
    }
    
    @media (max-width: 768px) {
        .quiz-with-sidebar-container {
            flex-direction: column;
        }
        
        .quiz-main-content,
        .quiz-media-sidebar {
            width: 100%;
        }
        
        .quiz-media-sidebar {
            position: static;
            margin-top: 30px;
        }
    }
</style>
