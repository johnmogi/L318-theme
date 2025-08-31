jQuery(document).ready(function($) {
    // Add assignment button to teacher rows
    $('.wp-list-table .name.column-name').each(function() {
        const teacherId = $(this).closest('tr').find('input[type="checkbox"]').val();
        if (teacherId) {
            $(this).append(
                '<button type="button" class="button-secondary assign-teacher-group" data-teacher-id="' + teacherId + '">' + 
                teacherAssignment.i18n.assign +
                '</button>'
            );
        }
    });

    // Handle assignment button click
    $(document).on('click', '.assign-teacher-group', function(e) {
        e.preventDefault();
        
        const teacherId = $(this).data('teacher-id');
        
        // Show loading indicator
        $(this).html(teacherAssignment.i18n.loading).prop('disabled', true);
        
        // Get available groups
        $.ajax({
            url: teacherAssignment.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_available_groups',
                nonce: teacherAssignment.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Create modal content
                    let modalContent = '<div class="teacher-group-assignment-modal">';
                    modalContent += '<h3>' + teacherAssignment.i18n.select_group + '</h3>';
                    modalContent += '<form id="teacher-group-form">';
                    modalContent += '<input type="hidden" name="teacher_id" value="' + teacherId + '">';
                    
                    // Add LearnDash groups section
                    if (response.data.learn_dash) {
                        modalContent += '<h4>' + response.data.learn_dash.label + '</h4>';
                        modalContent += '<select name="group_id" class="group-select">';
                        modalContent += '<option value="">' + teacherAssignment.i18n.select_group + '</option>';
                        response.data.learn_dash.options.forEach(function(option) {
                            modalContent += '<option value="' + option.value + '">' + option.text + '</option>';
                        });
                        modalContent += '</select>';
                    }
                    
                    // Add School Manager classes section
                    if (response.data.school_manager) {
                        modalContent += '<h4>' + response.data.school_manager.label + '</h4>';
                        modalContent += '<select name="group_id" class="group-select">';
                        modalContent += '<option value="">' + teacherAssignment.i18n.select_group + '</option>';
                        response.data.school_manager.options.forEach(function(option) {
                            modalContent += '<option value="' + option.value + '">' + option.text + '</option>';
                        });
                        modalContent += '</select>';
                    }
                    
                    modalContent += '<p class="submit">';
                    modalContent += '<button type="submit" class="button button-primary">' + 
                        teacherAssignment.i18n.assign +
                        '</button>';
                    modalContent += '<button type="button" class="button" onclick="tb_remove();">' + 
                        teacherAssignment.i18n.cancel +
                        '</button>';
                    modalContent += '</p>';
                    modalContent += '<input type="hidden" name="action" value="assign_teacher_to_group">';
                    modalContent += '<input type="hidden" name="_wpnonce" value="' + teacherAssignment.nonce + '">';
                    modalContent += '</form>';
                    modalContent += '</div>';
                    
                    // Show Thickbox modal
                    tb_show(teacherAssignment.i18n.select_group, modalContent, 600);
                    
                    // Handle form submission
                    $('#teacher-group-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = $(this).serialize();
                        
                        $.ajax({
                            url: teacherAssignment.ajaxurl,
                            type: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    alert(response.data.message);
                                    tb_remove();
                                    location.reload();
                                } else {
                                    alert(response.data.message || teacherAssignment.i18n.error);
                                }
                            },
                            error: function() {
                                alert(teacherAssignment.i18n.error);
                            }
                        });
                    });
                } else {
                    alert(teacherAssignment.i18n.no_groups);
                }
            },
            error: function() {
                alert(teacherAssignment.i18n.error);
            },
            complete: function() {
                // Restore button state
                $(e.target).html(teacherAssignment.i18n.assign).prop('disabled', false);
            }
        });
    });
});
