/**
 * Teacher Group Assignment
 * 
 * Handles the teacher group assignment functionality in School Manager Lite
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Handle click on the "Assign Groups" button
    $(document).on('click', '.assign-teacher-group', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var teacherId = $button.data('teacher-id');
        
        if (!teacherId) {
            alert(teacherGroupAssignment.i18n.error);
            return;
        }
        
        // Show thickbox with loading indicator
        var tbTitle = teacherGroupAssignment.i18n.selectGroups;
        var tbOptions = 'width=650&height=500&inlineId=teacher-group-assignment-container';
        
        // Create or update the container
        var $container = $('#teacher-group-assignment-container');
        if ($container.length === 0) {
            $container = $('<div id="teacher-group-assignment-container" style="display: none;"></div>');
            $('body').append($container);
        }
        
        // Show loading
        $container.html('<div style="padding: 20px; text-align: center;"><span class="spinner is-active" style="float: none;"></span> ' + teacherGroupAssignment.i18n.loading + '</div>');
        
        // Open thickbox
        tb_show(tbTitle, '#TB_inline?' + tbOptions);
        
        // Load the form via AJAX
        $.ajax({
            url: teacherGroupAssignment.ajax_url,
            type: 'GET',
            data: {
                action: 'get_teacher_group_assignment_form',
                teacher_id: teacherId,
                nonce: teacherGroupAssignment.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data && response.data.html) {
                    $container.html(response.data.html);
                    
                    // Adjust thickbox size after content loads
                    $('#TB_window').css({
                        'margin-left': '-325px',
                        'width': '650px',
                        'margin-top': '-250px'
                    });
                    
                    // Adjust content height
                    var windowHeight = $(window).height();
                    var tbHeight = windowHeight - 100;
                    $('#TB_ajaxContent').css('height', tbHeight + 'px');
                    
                    // Focus on first input for better UX
                    $container.find('input[type="checkbox"]').first().focus();
                } else {
                    var errorMessage = response.data && response.data.message ? 
                        response.data.message : teacherGroupAssignment.i18n.error;
                    $container.html('<div class="error"><p>' + errorMessage + '</p></div>');
                }
            },
            error: function() {
                $container.html('<div class="error"><p>' + teacherGroupAssignment.i18n.error + '</p></div>');
            }
        });
    });
    
    // Handle save button click
    $(document).on('click', '.save-teacher-groups', function() {
        var $button = $(this);
        var $form = $button.closest('.teacher-group-assignment');
        var teacherId = $button.data('teacher-id');
        var $spinner = $form.find('.spinner');
        var $response = $form.find('.response-message');
        
        // Get selected group IDs
        var groupIds = [];
        $form.find('input[name="teacher_groups[]"]:checked').each(function() {
            groupIds.push($(this).val());
        });
        
        // Show loading
        $button.prop('disabled', true);
        $spinner.addClass('is-active').show();
        $response.hide().removeClass('notice-success notice-error');
        
        // Send AJAX request
        $.ajax({
            url: teacherGroupAssignment.ajax_url,
            type: 'POST',
            data: {
                action: 'assign_teacher_to_groups',
                teacher_id: teacherId,
                group_ids: groupIds,
                nonce: teacherGroupAssignment.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $response.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Close the thickbox after 1.5 seconds
                    setTimeout(function() {
                        tb_remove();
                        // Optionally refresh the page or update the UI
                        window.location.reload();
                    }, 1500);
                } else {
                    var errorMessage = response.data && response.data.message ? 
                        response.data.message : teacherGroupAssignment.i18n.error;
                    $response.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                }
            },
            error: function() {
                $response.html('<div class="notice notice-error"><p>' + teacherGroupAssignment.i18n.error + '</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active').hide();
                $response.slideDown();
            }
        });
    });
    
    // Handle cancel button click
    $(document).on('click', '.cancel-teacher-groups', function() {
        tb_remove();
    });
    
    // Handle window resize to adjust thickbox
    $(window).on('thickbox:iframe:loaded', function() {
        if ($('#TB_window').is(':visible')) {
            var windowHeight = $(window).height();
            var tbHeight = windowHeight - 100;
            $('#TB_ajaxContent').css('height', tbHeight + 'px');
        }
    });
    
    // Fix for thickbox close button in RTL
    $(document).on('click', '#TB_closeWindowButton', function(e) {
        e.preventDefault();
        tb_remove();
    });
});
