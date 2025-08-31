/**
 * School Class Manager admin JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';

    var schoolClassManager = {
        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        cacheElements: function() {
            this.$teacherSelector = $('#teacher-selector');
            this.$classSelector = $('#class-selector');
            this.$classList = $('#class-list');
            this.$studentsTable = $('#students-table');
            this.$statistics = $('.school-class-statistics');
            this.$courseProgress = $('.course-progress-section');
        },

        bindEvents: function() {
            // Teacher selection change
            this.$teacherSelector.on('change', this.handleTeacherChange.bind(this));
            
            // Class selection change
            this.$classSelector.on('change', this.handleClassChange.bind(this));
        },

        handleTeacherChange: function() {
            const teacherId = this.$teacherSelector.val();
            
            if (!teacherId) {
                return;
            }
            
            // Show loading state
            this.$classList.addClass('is-loading');
            this.addLoadingOverlay(this.$classList);
            
            // Get classes for selected teacher via AJAX
            $.ajax({
                url: schoolClassManagerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_teacher_classes',
                    teacher_id: teacherId,
                    nonce: schoolClassManagerData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.classes) {
                        schoolClassManager.updateClassSelect(response.data.classes);
                        
                        // If classes exist, trigger class change to load students
                        if (Object.keys(response.data.classes).length > 0) {
                            schoolClassManager.$classSelector.trigger('change');
                        } else {
                            // Clear students table if no classes
                            schoolClassManager.clearStudentsTable();
                        }
                    } else {
                        schoolClassManager.clearClassSelect();
                        schoolClassManager.clearStudentsTable();
                    }
                },
                error: function() {
                    schoolClassManager.clearClassSelect();
                    schoolClassManager.clearStudentsTable();
                },
                complete: function() {
                    // Remove loading state
                    schoolClassManager.$classList.removeClass('is-loading');
                    schoolClassManager.removeLoadingOverlay(schoolClassManager.$classList);
                }
            });
        },

        handleClassChange: function() {
            const classId = this.$classSelector.val();
            
            if (!classId) {
                return;
            }
            
            // Show loading state
            this.$studentsTable.parent().addClass('is-loading');
            this.$statistics.addClass('is-loading');
            this.addLoadingOverlay(this.$studentsTable.parent());
            this.addLoadingOverlay(this.$statistics);
            
            // Get students for selected class via AJAX
            $.ajax({
                url: schoolClassManagerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_class_students',
                    class_id: classId,
                    nonce: schoolClassManagerData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update students table
                        schoolClassManager.updateStudentsTable(response.data.students);
                        
                        // Update statistics
                        schoolClassManager.updateStatistics(response.data.statistics);
                    } else {
                        schoolClassManager.clearStudentsTable();
                    }
                },
                error: function() {
                    schoolClassManager.clearStudentsTable();
                },
                complete: function() {
                    // Remove loading state
                    schoolClassManager.$studentsTable.parent().removeClass('is-loading');
                    schoolClassManager.$statistics.removeClass('is-loading');
                    schoolClassManager.removeLoadingOverlay(schoolClassManager.$studentsTable.parent());
                    schoolClassManager.removeLoadingOverlay(schoolClassManager.$statistics);
                }
            });
        },

        updateClassSelect: function(classes) {
            // Clear current options
            this.$classSelector.empty();
            
            if (Object.keys(classes).length === 0) {
                this.$classList.html('<p class="no-classes">' + schoolClassManagerData.i18n.noDataAvailable + '</p>');
                return;
            }
            
            // Build select element if it doesn't exist
            if (this.$classSelector.length === 0) {
                this.$classList.html('<select id="class-selector"></select>');
                this.$classSelector = $('#class-selector');
                this.$classSelector.on('change', this.handleClassChange.bind(this));
            }
            
            // Add new options
            $.each(classes, function(id, name) {
                schoolClassManager.$classSelector.append(
                    $('<option></option>').val(id).text(name)
                );
            });
        },

        updateStudentsTable: function(students) {
            const $tableBody = this.$studentsTable.find('tbody');
            $tableBody.empty();
            
            if (students.length === 0) {
                this.$studentsTable.parent().html(
                    '<div class="notice notice-info"><p>' + 
                    schoolClassManagerData.i18n.noDataAvailable + 
                    '</p></div>'
                );
                return;
            }
            
            // Add rows for each student
            $.each(students, function(i, student) {
                const row = $('<tr></tr>');
                
                // Check if admin links should be included
                const nameCell = $('<td></td>');
                if (typeof student.can_edit !== 'undefined' && student.can_edit) {
                    nameCell.html('<a href="' + schoolClassManagerData.ajaxurl.replace('admin-ajax.php', 'user-edit.php?user_id=' + student.id) + '">' + 
                                 student.name + '</a>');
                } else {
                    nameCell.text(student.name);
                }
                
                row.append(nameCell);
                row.append($('<td></td>').text(student.email));
                row.append($('<td></td>').text(student.username));
                
                $tableBody.append(row);
            });
        },

        updateStatistics: function(statistics) {
            // Update statistics cards
            $('#stat-total-students').text(statistics.total_students);
            $('#stat-course-completion').text(statistics.course_completion + '%');
            $('#stat-avg-score').text(statistics.average_quiz_score + '%');
            
            // Update course progress table if it exists
            if (statistics.courses && Object.keys(statistics.courses).length > 0) {
                this.updateCourseProgressTable(statistics.courses);
            } else {
                this.$courseProgress.hide();
            }
        },

        updateCourseProgressTable: function(courses) {
            const $table = this.$courseProgress.find('table');
            const $tableBody = $table.find('tbody');
            $tableBody.empty();
            
            // Show the table if it was hidden
            this.$courseProgress.show();
            
            // Add rows for each course
            $.each(courses, function(courseId, courseData) {
                const row = $('<tr></tr>');
                
                row.append($('<td></td>').text(courseData.title));
                
                // Completion progress bar
                const progressBar = $('<div class="progress-bar"></div>');
                const progressFill = $('<div class="progress-fill"></div>').css('width', courseData.completion + '%');
                const progressText = $('<div class="progress-text"></div>').text(courseData.completion + '%');
                
                progressBar.append(progressFill).append(progressText);
                row.append($('<td></td>').append(progressBar));
                
                row.append($('<td></td>').text(courseData.avg_score + '%'));
                
                $tableBody.append(row);
            });
        },

        clearClassSelect: function() {
            this.$classList.html('<p class="no-classes">' + schoolClassManagerData.i18n.noDataAvailable + '</p>');
        },

        clearStudentsTable: function() {
            this.$studentsTable.parent().html(
                '<div class="notice notice-info"><p>' + 
                schoolClassManagerData.i18n.noDataAvailable + 
                '</p></div>'
            );
            
            // Clear statistics
            $('#stat-total-students').text('0');
            $('#stat-course-completion').text('0%');
            $('#stat-avg-score').text('0%');
            
            // Hide course progress
            this.$courseProgress.hide();
        },

        addLoadingOverlay: function($element) {
            $element.append(
                '<div class="loading-overlay"><div class="loading-spinner"></div></div>'
            );
        },

        removeLoadingOverlay: function($element) {
            $element.find('.loading-overlay').remove();
        }
    };

    // Initialize the manager
    schoolClassManager.init();
});
