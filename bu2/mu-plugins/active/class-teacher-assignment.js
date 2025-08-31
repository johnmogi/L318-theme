jQuery(document).ready(function($) {
    // Add assignment button to class rows
    $('.wp-list-table .name.column-name').each(function() {
        const classId = $(this).closest('tr').find('input[type="checkbox"]').val();
        if (classId) {
            $(this).append(
                '<button type="button" class="button-secondary assign-teacher-button" data-class-id="' + classId + '">' + 
                classTeacherAssignment.i18n.assign +
                '</button>'
            );
        }
    });

    // Handle assignment button click
    $(document).on('click', '.assign-teacher-button', function(e) {
        e.preventDefault();
        
        const classId = $(this).data('class-id');
        
        // Show loading indicator
        $(this).html(classTeacherAssignment.i18n.loading).prop('disabled', true);
        
        // Get available teachers
        $.ajax({
            url: classTeacherAssignment.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_available_teachers',
                nonce: classTeacherAssignment.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Create modal content
                    let modalContent = '<div class="class-teacher-assignment-modal">';
                    modalContent += '<h3>' + classTeacherAssignment.i18n.select_teacher + '</h3>';
                    modalContent += '<form id="class-teacher-form">';
                    modalContent += '<input type="hidden" name="class_id" value="' + classId + '">';
                    
                    // Teacher selector
                    modalContent += '<label>' + classTeacherAssignment.i18n.select_teacher + '</label>';
                    modalContent += '<select name="teacher_id" class="teacher-select" required>';
                    modalContent += '<option value="">' + classTeacherAssignment.i18n.select_teacher + '</option>';
                    
                    // Add teachers with their roles
                    response.data.forEach(function(teacher) {
                        modalContent += '<option value="' + teacher.value + '">' + 
                            teacher.text + ' (' + teacher.email + ')' +
                            '</option>';
                    });
                    
                    modalContent += '</select>';
                    
                    modalContent += '<p class="submit">';
                    modalContent += '<button type="submit" class="button button-primary">' + 
                        classTeacherAssignment.i18n.assign +
                        '</button>';
                    modalContent += '<button type="button" class="button" onclick="tb_remove();">' + 
                        classTeacherAssignment.i18n.cancel +
                        '</button>';
                    modalContent += '</p>';
                    modalContent += '<input type="hidden" name="action" value="assign_teacher_to_class">';
                    modalContent += '<input type="hidden" name="_wpnonce" value="' + classTeacherAssignment.nonce + '">';
                    modalContent += '</form>';
                    modalContent += '</div>';
                    
                    // Show Thickbox modal
                    tb_show(classTeacherAssignment.i18n.select_teacher, modalContent, 600);
                    
                    // Handle form submission
                    $('#class-teacher-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = $(this).serialize();
                        
                        $.ajax({
                            url: classTeacherAssignment.ajaxurl,
                            type: 'POST',
                            data: formData,
                            success: function(response) {
                                if (response.success) {
                                    alert(response.data.message);
                                    tb_remove();
                                    location.reload();
                                } else {
                                    alert(response.data.message || classTeacherAssignment.i18n.error);
                                }
                            },
                            error: function() {
                                alert(classTeacherAssignment.i18n.error);
                            }
                        });
                    });
                } else {
                    alert(classTeacherAssignment.i18n.no_teachers);
                }
            },
            error: function() {
                alert(classTeacherAssignment.i18n.error);
            },
            complete: function() {
                // Restore button state
                $(e.target).html(classTeacherAssignment.i18n.assign).prop('disabled', false);
            }
        });
    });
});
