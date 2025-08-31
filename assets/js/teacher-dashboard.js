/**
 * Teacher Dashboard JavaScript
 */
(function($) {
    'use strict';

    // Dashboard object
    const TeacherDashboard = {
        // Initialize the dashboard
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.initDataTables();
            this.calculateClassStats();
        },

        // Cache DOM elements
        cacheElements: function() {
            this.$body = $('body');
            this.$dashboard = $('.teacher-dashboard');
            this.$tabs = $('.tab-button');
            this.$tabContents = $('.tab-content');
            this.$studentSearch = $('#student-search');
            this.$groupFilter = $('#group-filter');
            this.$selectAll = $('#select-all-students');
            this.$studentCheckboxes = $('.student-checkbox');
            this.$modal = $('#student-details-modal');
            this.$modalBody = this.$modal.find('.modal-body');
            this.$refreshStats = $('#refresh-stats');
            this.$exportData = $('#export-data');
            this.$messageStudents = $('#message-students');
        },

        // Bind event handlers
        bindEvents: function() {
            // Tab switching
            this.$tabs.on('click', this.switchTab.bind(this));
            
            // Student search
            this.$studentSearch.on('keyup', this.filterStudents.bind(this));
            
            // Group filter
            this.$groupFilter.on('change', this.filterStudents.bind(this));
            
            // Select all students
            this.$selectAll.on('change', this.toggleSelectAll.bind(this));
            
            // Student selection
            this.$studentCheckboxes.on('change', this.updateSelectAllCheckbox.bind(this));
            
            // View student details
            this.$dashboard.on('click', '.view-details', this.showStudentDetails.bind(this));
            
            // Message student
            this.$dashboard.on('click', '.message-student', this.messageStudent.bind(this));
            
            // Close modal
            this.$modal.on('click', '.close-modal', this.closeModal.bind(this));
            
            // Click outside modal to close
            this.$modal.on('click', function(e) {
                if (e.target === this) {
                    TeacherDashboard.closeModal();
                }
            });
            
            // Refresh stats
            this.$refreshStats.on('click', this.refreshStats.bind(this));
            
            // Export data
            this.$exportData.on('click', this.exportData.bind(this));
            
            // Message students
            this.$messageStudents.on('click', this.messageStudents.bind(this));
            
            // Handle window resize
            $(window).on('resize', this.handleResize.bind(this));
        },

        // Initialize DataTables if available
        initDataTables: function() {
            if ($.fn.DataTable) {
                this.dataTable = $('.students-list').DataTable({
                    paging: true,
                    pageLength: 10,
                    lengthChange: false,
                    searching: false,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    responsive: true,
                    language: {
                        paginate: {
                            previous: '‹',
                            next: '›',
                            first: '«',
                            last: '»'
                        },
                        info: 'Showing _START_ to _END_ of _TOTAL_ students',
                        infoEmpty: 'No students found',
                        infoFiltered: '(filtered from _MAX_ total students)'
                    },
                    dom: '<"top"i>rt<"bottom"lp><"clear">',
                    drawCallback: function() {
                        // Update the "Select All" checkbox when table is redrawn
                        TeacherDashboard.updateSelectAllCheckbox();
                    }
                });
                
                // Update the search to work with DataTables
                this.$studentSearch.on('keyup', function() {
                    TeacherDashboard.dataTable.search(this.value).draw();
                });
                
                // Update the group filter to work with DataTables
                this.$groupFilter.on('change', function() {
                    const group = $(this).val();
                    if (group) {
                        TeacherDashboard.dataTable.column(3).search('^' + group + '$', true, false).draw();
                    } else {
                        TeacherDashboard.dataTable.column(3).search('').draw();
                    }
                });
            }
        },

        // Switch between tabs
        switchTab: function(e) {
            e.preventDefault();
            
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');
            
            // Update active tab
            this.$tabs.removeClass('active');
            $tab.addClass('active');
            
            // Show active tab content
            this.$tabContents.removeClass('active');
            $(`#${tabId}-tab`).addClass('active');
            
            // Trigger resize event for any charts or responsive elements
            $(window).trigger('resize');
        },

        // Filter students based on search and group
        filterStudents: function() {
            if (!this.dataTable) {
                const searchTerm = this.$studentSearch.val().toLowerCase();
                const groupFilter = this.$groupFilter.val().toLowerCase();
                
                $('.students-list tbody tr').each(function() {
                    const $row = $(this);
                    const name = $row.find('.student-name').text().toLowerCase();
                    const email = $row.find('td:nth-child(3)').text().toLowerCase();
                    const groups = $row.find('td:nth-child(4)').text().toLowerCase();
                    
                    const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                    const matchesGroup = !groupFilter || groups.includes(groupFilter);
                    
                    $row.toggle(matchesSearch && matchesGroup);
                });
                
                this.updatePagination();
            }
        },

        // Toggle select all students
        toggleSelectAll: function() {
            const isChecked = this.$selectAll.prop('checked');
            this.$studentCheckboxes.prop('checked', isChecked);
        },

        // Update select all checkbox when individual checkboxes change
        updateSelectAllCheckbox: function() {
            const allChecked = this.$studentCheckboxes.length === this.$studentCheckboxes.filter(':checked').length;
            this.$selectAll.prop('checked', allChecked);
        },

        // Show student details in modal
        showStudentDetails: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const studentId = $button.data('student-id');
            
            if (!studentId) return;
            
            // Show loading state
            this.$modalBody.html('<div class="loading">' + teacherDashboardData.i18n.loading + '</div>');
            this.$modal.show();
            
            // Get student data via AJAX
            $.ajax({
                url: teacherDashboardData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_student_data',
                    student_id: studentId,
                    nonce: teacherDashboardData.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        TeacherDashboard.renderStudentDetails(response.data);
                    } else {
                        TeacherDashboard.showError(response.data.message || teacherDashboardData.i18n.error);
                    }
                },
                error: function() {
                    TeacherDashboard.showError(teacherDashboardData.i18n.error);
                }
            });
        },

        // Render student details in modal
        renderStudentDetails: function(student) {
            if (!student) {
                this.showError(teacherDashboardData.i18n.error);
                return;
            }
            
            let html = `
                <div class="student-details">
                    <div class="student-header">
                        <div class="student-avatar">${student.avatar || ''}</div>
                        <div class="student-info">
                            <h3>${student.name}</h3>
                            <p class="student-email">${student.email}</p>
                            <p class="student-meta">
                                <span>${teacherDashboardData.i18n.registered}: ${student.registered}</span>
                                <span>${teacherDashboardData.i18n.lastLogin}: ${student.last_login || teacherDashboardData.i18n.never}</span>
                            </p>
                        </div>
                    </div>
                    <div class="student-progress">
                        <h4>${teacherDashboardData.i18n.courseProgress}</h4>
            `;
            
            if (student.courses && student.courses.length > 0) {
                html += '<ul class="course-list">';
                
                student.courses.forEach(function(course) {
                    const progressClass = course.status === 'completed' ? 'completed' : 
                                        course.status === 'in_progress' ? 'in-progress' : 'not-started';
                    const progressText = course.status === 'in_progress' ? 
                                        `${course.progress}% ${teacherDashboardData.i18n.complete}` : 
                                        teacherDashboardData.i18n[course.status];
                    
                    html += `
                        <li class="course-item ${progressClass}">
                            <div class="course-header">
                                <h5><a href="${course.url}" target="_blank">${course.title}</a></h5>
                                <span class="course-status">${progressText}</span>
                            </div>`;
                    
                    if (course.score > 0) {
                        html += `
                            <div class="course-score">
                                <span>${teacherDashboardData.i18n.score}: ${course.score}%</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${course.score}%;"></div>
                                </div>
                            </div>`;
                    }
                    
                    if (course.last_activity) {
                        html += `
                            <div class="course-activity">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                ${teacherDashboardData.i18n.lastActivity}: ${course.last_activity}
                            </div>`;
                    }
                    
                    html += '</li>';
                });
                
                html += '</ul>';
            } else {
                html += `<p>${teacherDashboardData.i18n.noCourses}</p>`;
            }
            
            html += `
                    </div>
                </div>`;
            
            this.$modalBody.html(html);
        },

        // Message student
        messageStudent: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const studentId = $button.data('student-id');
            
            // Implement message functionality here
            alert(teacherDashboardData.i18n.messageSent.replace('%s', studentId));
        },

        // Close modal
        closeModal: function() {
            this.$modal.hide();
            this.$modalBody.empty();
        },

        // Show error message
        showError: function(message) {
            this.$modalBody.html(`<div class="error">${message}</div>`);
        },

        // Calculate and display class statistics
        calculateClassStats: function() {
            let totalScore = 0;
            let totalStudents = 0;
            let totalProgress = 0;
            
            $('.students-list tbody tr').each(function() {
                const $row = $(this);
                const scoreText = $row.find('.score-value').text();
                const progressText = $row.find('.progress-text').text();
                
                const score = parseFloat(scoreText) || 0;
                const progress = parseFloat(progressText) || 0;
                
                if (score > 0) {
                    totalScore += score;
                    totalStudents++;
                }
                
                totalProgress += progress;
            });
            
            // Update average score
            const avgScore = totalStudents > 0 ? Math.round(totalScore / totalStudents) : 0;
            $('#avg-score').text(avgScore + '%');
            
            // Update completion rate
            const totalRows = $('.students-list tbody tr').length;
            const avgProgress = totalRows > 0 ? Math.round(totalProgress / totalRows) : 0;
            $('#completion-rate').text(avgProgress + '%');
        },

        // Refresh statistics
        refreshStats: function(e) {
            if (e) e.preventDefault();
            
            // Show loading state
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            $button.prop('disabled', true).text(teacherDashboardData.i18n.refreshing);
            
            // Simulate API call
            setTimeout(() => {
                this.calculateClassStats();
                $button.prop('disabled', false).text(originalText);
                
                // Show success message
                const $notice = $('<div class="notice notice-success is-dismissible"><p>' + 
                                teacherDashboardData.i18n.statsUpdated + '</p></div>');
                $('.dashboard-header').after($notice);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    $notice.fadeOut(400, function() {
                        $(this).remove();
                    });
                }, 3000);
            }, 1000);
        },

        // Export data
        exportData: function(e) {
            if (e) e.preventDefault();
            
            // Get selected students or all if none selected
            let selectedStudents = [];
            const $selectedRows = $('.student-checkbox:checked').closest('tr');
            
            if ($selectedRows.length > 0) {
                $selectedRows.each(function() {
                    const studentId = $(this).data('student-id');
                    if (studentId) {
                        selectedStudents.push(studentId);
                    }
                });
            } else {
                // If no students selected, export all
                $('.student-checkbox').each(function() {
                    const studentId = $(this).closest('tr').data('student-id');
                    if (studentId) {
                        selectedStudents.push(studentId);
                    }
                });
            }
            
            if (selectedStudents.length === 0) {
                alert(teacherDashboardData.i18n.noStudents);
                return;
            }
            
            // Show loading state
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            $button.prop('disabled', true).text(teacherDashboardData.i18n.exporting);
            
            // In a real implementation, this would be an AJAX call to generate and download the export
            setTimeout(() => {
                // Simulate export completion
                $button.prop('disabled', false).text(originalText);
                
                // Show success message
                const $notice = $('<div class="notice notice-success is-dismissible"><p>' + 
                                teacherDashboardData.i18n.exportComplete + ' ' + 
                                selectedStudents.length + ' ' + 
                                (selectedStudents.length === 1 ? teacherDashboardData.i18n.student : teacherDashboardData.i18n.students) + 
                                '</p></div>');
                $('.dashboard-header').after($notice);
                
                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    $notice.fadeOut(400, function() {
                        $(this).remove();
                    });
                }, 3000);
                
                // In a real implementation, trigger file download here
                // window.location.href = teacherDashboardData.ajaxurl + '?action=export_student_data&students=' + selectedStudents.join(',');
            }, 1000);
        },

        // Message students
        messageStudents: function(e) {
            if (e) e.preventDefault();
            
            // Get selected students or all if none selected
            let selectedStudents = [];
            const $selectedRows = $('.student-checkbox:checked').closest('tr');
            
            if ($selectedRows.length > 0) {
                $selectedRows.each(function() {
                    const studentId = $(this).data('student-id');
                    if (studentId) {
                        selectedStudents.push(studentId);
                    }
                });
            } else {
                // If no students selected, message all
                $('.student-checkbox').each(function() {
                    const studentId = $(this).closest('tr').data('student-id');
                    if (studentId) {
                        selectedStudents.push(studentId);
                    }
                });
            }
            
            if (selectedStudents.length === 0) {
                alert(teacherDashboardData.i18n.noStudents);
                return;
            }
            
            // Show message composition UI
            this.showMessageComposer(selectedStudents);
        },

        // Show message composer
        showMessageComposer: function(recipientIds) {
            const modalContent = `
                <div class="message-composer">
                    <h3>${teacherDashboardData.i18n.composeMessage}</h3>
                    <div class="form-group">
                        <label for="message-subject">${teacherDashboardData.i18n.subject}:</label>
                        <input type="text" id="message-subject" class="widefat">
                    </div>
                    <div class="form-group">
                        <label for="message-content">${teacherDashboardData.i18n.message}:</label>
                        <textarea id="message-content" rows="8" class="widefat"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="button button-primary" id="send-message">
                            ${teacherDashboardData.i18n.sendMessage}
                        </button>
                        <button type="button" class="button button-link cancel-message">
                            ${teacherDashboardData.i18n.cancel}
                        </button>
                        <span class="spinner"></span>
                    </div>
                </div>`;
            
            this.$modalBody.html(modalContent);
            this.$modal.show();
            
            // Bind send message handler
            $('#send-message').on('click', () => this.sendMessage(recipientIds));
            
            // Bind cancel handler
            $('.cancel-message').on('click', () => this.closeModal());
        },

        // Send message to students
        sendMessage: function(recipientIds) {
            const subject = $('#message-subject').val().trim();
            const content = $('#message-content').val().trim();
            
            if (!subject) {
                alert(teacherDashboardData.i18n.subjectRequired);
                return;
            }
            
            if (!content) {
                alert(teacherDashboardData.i18n.messageRequired);
                return;
            }
            
            // Show loading state
            const $button = $('#send-message');
            const $spinner = $button.siblings('.spinner');
            const originalText = $button.text();
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            // In a real implementation, this would be an AJAX call to send the message
            setTimeout(() => {
                // Simulate sending
                $button.prop('disabled', false).text(originalText);
                $spinner.removeClass('is-active');
                
                // Show success message
                this.$modalBody.html(`
                    <div class="message-sent">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h3>${teacherDashboardData.i18n.messageSentSuccess}</h3>
                        <p>${teacherDashboardData.i18n.messageSentTo} ${recipientIds.length} 
                        ${recipientIds.length === 1 ? teacherDashboardData.i18n.recipient : teacherDashboardData.i18n.recipients}</p>
                        <button type="button" class="button button-primary close-modal">
                            ${teacherDashboardData.i18n.close}
                        </button>
                    </div>`);
                
                // Re-bind close handler
                $('.close-modal').on('click', () => this.closeModal());
            }, 1500);
        },

        // Handle window resize
        handleResize: function() {
            // Update any responsive elements here
        },

        // Update pagination display (for non-DataTables implementation)
        updatePagination: function() {
            // Implement custom pagination if not using DataTables
            if (!this.dataTable) {
                // Custom pagination logic here
            }
        }
    };

    // Initialize the dashboard when the DOM is ready
    $(document).ready(function() {
        if ($('.teacher-dashboard').length) {
            TeacherDashboard.init();
        }
    });

})(jQuery);
