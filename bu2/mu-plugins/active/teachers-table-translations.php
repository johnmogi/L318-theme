<?php
/**
 * Plugin Name: Teachers Table Translations
 * Description: Translates teachers table headers and actions to Hebrew
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teachers_Table_Translations {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_head', array($this, 'add_table_translations'));
    }
    
    public function add_table_translations() {
        if (isset($_GET['page']) && $_GET['page'] === 'school-manager-teachers') {
            ?>
            <style>
                /* RTL alignment for table headers */
                .wp-list-table th {
                    text-align: right !important;
                }
                
                /* RTL alignment for table cells */
                .wp-list-table td {
                    text-align: right !important;
                }
                
                /* RTL alignment for row actions */
                .row-actions span {
                    float: right !important;
                }
                
                /* RTL alignment for bulk actions */
                #bulk-action-selector-top,
                #bulk-action-selector-bottom {
                    float: right !important;
                }
                
                /* RTL alignment for search box */
                .search-box {
                    float: right !important;
                }
                
                /* Hide class management menu item */
                .wp-menu-name:contains('Class Management') {
                    display: none !important;
                }
                
                /* Hide class filter dropdown */
                #class_filter {
                    display: none !important;
                }
                
                /* Hide filter button */
                #post-query-submit {
                    display: none !important;
                }
                
                /* Hide search submit button */
                #search-submit {
                    display: none !important;
                }
            </style>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Translate table headers
                document.querySelector('th.column-name a span').textContent = 'שם';
                document.querySelector('th.column-email a span').textContent = 'אימייל';
                document.querySelector('th.column-classes').textContent = 'כיתות';
                document.querySelector('th.column-students').textContent = 'תלמידים';
                document.querySelector('th.column-date a span').textContent = 'הרשמה';
                
                // Translate row actions
                document.querySelectorAll('.row-actions span').forEach(function(span) {
                    if (span.textContent.includes('Edit')) {
                        span.querySelector('a').textContent = 'ערוך';
                    } else if (span.textContent.includes('Delete')) {
                        span.querySelector('a').textContent = 'מחק';
                    }
                });
                
                // Translate bulk actions
                const bulkSelectors = document.querySelectorAll('#bulk-action-selector-top, #bulk-action-selector-bottom');
                bulkSelectors.forEach(function(select) {
                    const options = select.querySelectorAll('option');
                    options.forEach(function(option) {
                        if (option.value === '-1') {
                            option.textContent = 'בחר פעולה';
                        } else if (option.value === 'delete') {
                            option.textContent = 'מחק';
                        }
                    });
                });
                
                // Translate search box
                const searchBox = document.querySelector('.search-box input');
                if (searchBox) {
                    searchBox.placeholder = 'חפש מורים...';
                }
                
                // Hide the class filter dropdown
                const classFilter = document.getElementById('class_filter');
                if (classFilter) {
                    classFilter.style.display = 'none';
                }
                
                // Hide the filter button
                const filterButton = document.getElementById('post-query-submit');
                if (filterButton) {
                    filterButton.style.display = 'none';
                }
                
                // Hide the search submit button
                const searchSubmit = document.getElementById('search-submit');
                if (searchSubmit) {
                    searchSubmit.style.display = 'none';
                }
            });
            </script>
            <?php
        }
    }
}

// Initialize
Teachers_Table_Translations::instance();
