/**
 * Handles the Quick Edit functionality for quiz sidebar options
 */
(function($) {
    'use strict';

    // Run when DOM is ready
    $(document).ready(function() {
        // Add our custom fields to the Quick Edit box
        var $wp_inline_edit = inlineEditPost.edit;
        
        // Extend the Quick Edit functionality
        inlineEditPost.edit = function(id) {
            // Call the original WP edit function
            $wp_inline_edit.apply(this, arguments);
            
            // Get the post ID
            var post_id = 0;
            if (typeof(id) === 'object') {
                post_id = parseInt(this.getId(id));
            }
            
            // Only proceed if we have a valid post ID
            if (post_id > 0) {
                // Get the current values
                var $post_row = $('#post-' + post_id);
                var toggle_sidebar = $post_row.find('.column-toggle_sidebar').text().trim() === 'Yes';
                var enforce_hint = $post_row.find('.column-enforce_hint').text().trim() === 'Yes';
                
                // Set the values in Quick Edit
                $('#ld-quiz-toggle-sidebar').prop('checked', toggle_sidebar);
                $('#ld-quiz-enforce-hint').prop('checked', enforce_hint);
            }
        };
        
        // Add our custom fields to the Quick Edit box
        $(document).on('click', '.editinline', function() {
            var $inline_data = $('#inline_' + inlineEditPost.getId(this));
            var post_type = $inline_data.find('.post_type').text();
            
            if (post_type === 'sfwd-quiz') {
                var toggle_sidebar = $inline_data.find('.toggle_sidebar').text() === '1';
                var enforce_hint = $inline_data.find('.enforce_hint').text() === '1';
                
                // Add our fields to the Quick Edit form
                var $edit_row = $('#edit-' + inlineEditPost.getId(this));
                var $post_status = $edit_row.find('select[name="_status"]');
                
                if ($post_status.length) {
                    var quick_edit_fields = '<div class="inline-edit-group wp-clearfix">'+
                    '<label class="alignleft">'+
                    '<input type="checkbox" id="ld-quiz-toggle-sidebar" name="_ld_quiz_toggle_sidebar" value="1">'+
                    '<span class="checkbox-title">' + ld_quiz_sidebar_admin.toggle_sidebar + '</span>'+
                    '</label>'+
                    '<br>'+
                    '<label class="alignleft">'+
                    '<input type="checkbox" id="ld-quiz-enforce-hint" name="_ld_quiz_enforce_hint" value="1">'+
                    '<span class="checkbox-title">' + ld_quiz_sidebar_admin.enforce_hint + '</span>'+
                    '</label>'+
                    '</div>';
                    
                    $post_status.closest('.inline-edit-group').after(quick_edit_fields);
                    
                    // Set the values
                    $('#ld-quiz-toggle-sidebar').prop('checked', toggle_sidebar);
                    $('#ld-quiz-enforce-hint').prop('checked', enforce_hint);
                }
            }
        });
        
        // Handle saving the Quick Edit data
        $(document).on('click', '.save', function(e) {
            var post_id = $(this).closest('tr').attr('id').replace('edit-', '');
            
            if (post_id) {
                var toggle_sidebar = $('#ld-quiz-toggle-sidebar').is(':checked') ? 1 : 0;
                var enforce_hint = $('#ld-quiz-enforce-hint').is(':checked') ? 1 : 0;
                
                // Update the hidden fields that will be saved
                $('input[name="_ld_quiz_toggle_sidebar"]').val(toggle_sidebar);
                $('input[name="_ld_quiz_enforce_hint"]').val(enforce_hint);
                
                // Update the quick edit row display
                var $post_row = $('#post-' + post_id);
                $post_row.find('.column-toggle_sidebar').text(toggle_sidebar ? 'Yes' : 'No');
                $post_row.find('.column-enforce_hint').text(enforce_hint ? 'Yes' : 'No');
            }
        });
    });
    
})(jQuery);
