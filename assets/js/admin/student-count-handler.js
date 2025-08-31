/**
 * Handler for student count clicks in school class manager
 */
jQuery(document).ready(function($) {
    'use strict';

    console.log('Student count handler initialized');

    // Find and attach click handlers to student count links
    function initStudentCountLinks() {
        console.log('Setting up student count links, found: ' + $('.student-count-link').length);
        
        $('.student-count-link').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Get the class ID from the data attribute
            const classId = $(this).data('class-id');
            
            console.log('Student count clicked for class ID: ' + classId);
            
            if (!classId) {
                console.warn('No class ID found in clicked element');
                return;
            }
            
            // If we're on the class management page, just select the class in the dropdown
            const $classSelector = $('#class-selector');
            if ($classSelector.length) {
                console.log('Found class selector, updating to: ' + classId);
                $classSelector.val(classId).trigger('change');
                
                // Scroll to the students section
                if ($('.students-section').length) {
                    $('html, body').animate({
                        scrollTop: $('.students-section').offset().top - 50
                    }, 500);
                }
            } else {
                // Otherwise, redirect to the class management page with the class ID
                console.log('Redirecting to class management page with class ID: ' + classId);
                window.location.href = 'admin.php?page=class-management&class_id=' + classId;
            }
        });
    }
    
    // Initialize when document is ready
    initStudentCountLinks();
    
    // Also initialize when the table is updated via AJAX (for dynamic content)
    $(document).on('school_class_table_updated', function() {
        console.log('Table updated event triggered, reinitializing student count links');
        initStudentCountLinks();
    });
    
    // Initialize after small delay to ensure all elements are loaded
    setTimeout(function() {
        console.log('Delayed initialization of student count links');
        initStudentCountLinks();
    }, 1000);
});
