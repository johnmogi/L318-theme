<div class="wizard-step-content">
    <h2><?php _e('Step 1: Teacher Information', 'hello-theme-child'); ?></h2>
    
    <div class="form-section teacher-type-selector">
        <div class="form-field">
            <label>
                <input type="radio" name="teacher_type" value="existing" checked>
                <?php _e('Select existing teacher', 'hello-theme-child'); ?>
            </label>
            <label>
                <input type="radio" name="teacher_type" value="new">
                <?php _e('Create new teacher', 'hello-theme-child'); ?>
            </label>
        </div>
    </div>
    
    <div class="form-section existing-teacher-section">
        <h3><?php _e('Select Teacher', 'hello-theme-child'); ?></h3>
        
        <?php
        // Get all teachers
        $teachers = get_users([
            'role' => 'school_teacher',
            'orderby' => 'display_name',
            'order' => 'ASC',
            'number' => 100 // Limit to 100 teachers
        ]);
        
        $selected_teacher_id = !empty($form_data['teacher_id']) ? (int)$form_data['teacher_id'] : 0;
        ?>
        
        <!-- Table Navigation -->
        <div class="tablenav top">
            <div class="tablenav-pages">
                <span class="displaying-num">0 items</span>
                <span class="pagination-links">
                    <a class="first-page button" href="#">
                        <span class="screen-reader-text"><?php _e('First page', 'hello-theme-child'); ?></span>
                        <span aria-hidden="true">«</span>
                    </a>
                    <a class="prev-page button" href="#">
                        <span class="screen-reader-text"><?php _e('Previous page', 'hello-theme-child'); ?></span>
                        <span aria-hidden="true">‹</span>
                    </a>
                    <span class="paging-input">
                        <label for="current-page-selector" class="screen-reader-text">
                            <?php _e('Current Page', 'hello-theme-child'); ?>
                        </label>
                        <input class="current-page" id="current-page-selector" type="text" 
                               name="paged" value="1" size="2" aria-describedby="table-paging">
                        <span class="tablenav-paging-text">
                            <?php _e('of', 'hello-theme-child'); ?> <span class="total-pages">1</span>
                        </span>
                    </span>
                    <a class="next-page button" href="#">
                        <span class="screen-reader-text"><?php _e('Next page', 'hello-theme-child'); ?></span>
                        <span aria-hidden="true">›</span>
                    </a>
                    <a class="last-page button" href="#">
                        <span class="screen-reader-text"><?php _e('Last page', 'hello-theme-child'); ?></span>
                        <span aria-hidden="true">»</span>
                    </a>
                </span>
            </div>
            <div class="alignleft actions">
                <input type="search" id="teacher-search" class="search" 
                       placeholder="<?php esc_attr_e('Search teachers...', 'hello-theme-child'); ?>" 
                       style="width: 250px;">
            </div>
            <br class="clear">
        </div>
        
        <!-- Teachers Table -->
        <div class="teacher-table-container" style="max-height: 400px; overflow-y: auto; margin-bottom: 20px;">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('Select', 'hello-theme-child'); ?></th>
                        <th class="sortable" data-sort="name">
                            <?php _e('Name', 'hello-theme-child'); ?>
                            <span class="sorting-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="email">
                            <?php _e('Email', 'hello-theme-child'); ?>
                            <span class="sorting-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="phone">
                            <?php _e('Phone', 'hello-theme-child'); ?>
                            <span class="sorting-indicator"></span>
                        </th>
                    </tr>
                </thead>
                <tbody id="teacher-list">
                    <?php if (!empty($teachers)) : ?>
                        <?php foreach ($teachers as $teacher) : 
                            $phone = get_user_meta($teacher->ID, 'phone_number', true) ?: '';
                            $is_selected = ($teacher->ID === $selected_teacher_id);
                        ?>
                            <tr class="teacher-row" 
                                data-name="<?php echo esc_attr(strtolower($teacher->display_name)); ?>" 
                                data-email="<?php echo esc_attr(strtolower($teacher->user_email)); ?>"
                                data-phone="<?php echo esc_attr($phone); ?>"
                                <?php echo $is_selected ? 'data-selected="true"' : ''; ?>>
                                <td>
                                    <input type="radio" 
                                           name="teacher_id" 
                                           value="<?php echo esc_attr($teacher->ID); ?>" 
                                           id="teacher_<?php echo esc_attr($teacher->ID); ?>" 
                                           class="teacher-radio"
                                           <?php echo $is_selected ? 'checked' : ''; ?>>
                                    <input type="hidden" name="teacher_selected" value="<?php echo $is_selected ? '1' : '0'; ?>">
                                </td>
                                <td>
                                    <label for="teacher_<?php echo esc_attr($teacher->ID); ?>">
                                        <?php echo esc_html($teacher->display_name); ?>
                                    </label>
                                </td>
                                <td><?php echo esc_html($teacher->user_email); ?></td>
                                <td><?php echo esc_html($phone); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                <?php _e('No teachers found.', 'hello-theme-child'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <p class="description"><?php _e('Select a teacher from the list above', 'hello-theme-child'); ?></p>
        
        <style>
        /* Form section styling */
        .form-section {
            margin-bottom: 2em;
            background: #fff;
            padding: 1.5em;
            border: 1px solid #dcdcde;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        /* Table container */
        .teacher-table-container {
            border: 1px solid #dcdcde;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            background: #fff;
            margin: 1em 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .teacher-table-container .tablenav {
            padding: 8px 10px;
            border-bottom: 1px solid #dcdcde;
            background: #f6f7f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .teacher-table-container .tablenav-pages {
            margin: 0;
            float: none;
            display: flex;
            align-items: center;
        }
        
        .teacher-table-container .tablenav-pages .pagination-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .teacher-table-container .tablenav-pages .paging-input {
            display: flex;
            align-items: center;
            margin: 0 8px;
        }
        
        .teacher-table-container .tablenav-pages .current-page {
            width: 40px;
            text-align: center;
            margin: 0 2px;
            padding: 2px 4px;
        }
        
        .teacher-table-container table {
            margin: 0;
            border: none;
            border-collapse: collapse;
            width: 100%;
            table-layout: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .teacher-table-container th {
            position: sticky;
            top: 0;
            background: #f6f7f7;
            z-index: 2;
            font-weight: 600;
            color: #1d2327;
            padding: 10px;
            border-bottom: 1px solid #c3c4c7;
            text-align: left;
            vertical-align: middle;
        }
        
        .teacher-table-container th.sortable {
            cursor: pointer;
            position: relative;
            padding-right: 30px;
            white-space: nowrap;
        }
        
        .teacher-table-container th.sortable .sorting-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #646970;
        }
        
        .teacher-table-container td {
            padding: 10px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f1;
            line-height: 1.4;
            word-wrap: break-word;
            background: #fff;
        }
        
        .teacher-table-container tr:hover {
            background-color: #f6f7f7;
        }
        
        .teacher-table-container tr[data-selected="true"] {
            background-color: #f0f6fc;
        }
        
        .teacher-radio {
            margin: 0 8px 0 0;
            width: 16px;
            height: 16px;
            vertical-align: middle;
        }
        
        /* RTL support */
        .rtl .teacher-table-container th {
            text-align: right;
        }
        
        .rtl .teacher-table-container th.sortable {
            padding-left: 30px;
            padding-right: 10px;
        }
        
        .rtl .teacher-table-container th.sortable .sorting-indicator {
            left: 10px;
            right: auto;
        }
        
        /* Form field styling */
        .form-field {
            margin-bottom: 1.5em;
        }
        
        .form-field label {
            display: block;
            margin-bottom: 0.5em;
            font-weight: 500;
        }
        
        .form-field input[type="text"],
        .form-field input[type="email"],
        .form-field input[type="tel"],
        .form-field input[type="password"],
        .form-field select,
        .form-field textarea {
            width: 100%;
            max-width: 500px;
            padding: 8px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        /* Responsive */
        @media screen and (max-width: 782px) {
            .form-section {
                padding: 1em;
            }
            
            .teacher-table-container {
                margin: 1em -1em;
                border-left: none;
                border-right: none;
                border-radius: 0;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .teacher-table-container .tablenav {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .teacher-table-container .tablenav .actions {
                width: 100%;
                margin-bottom: 8px;
            }
            
            .teacher-table-container .tablenav-pages {
                width: 100%;
                justify-content: space-between;
            }
            
            .teacher-table-container table {
                min-width: 100%;
            }
            
            .teacher-table-container th,
            .teacher-table-container td {
                padding: 8px 10px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Pagination functionality
            function updatePagination() {
                const $table = $('.teacher-table-container');
                const $rows = $table.find('tbody tr');
                const rowsPerPage = 10;
                const totalRows = $rows.length;
                const totalPages = Math.ceil(totalRows / rowsPerPage);
                let currentPage = 1;
                
                // Initialize pagination
                function initPagination() {
                    // Update pagination controls
                    $table.find('.total-pages').text(totalPages);
                    $table.find('.displaying-num').text(totalRows + ' ' + (totalRows === 1 ? 'item' : 'items'));
                    
                    // Show/hide pagination based on number of pages
                    if (totalPages <= 1) {
                        $table.find('.tablenav-pages').hide();
                    } else {
                        $table.find('.tablenav-pages').show();
                    }
                    
                    // Handle pagination clicks
                    $table.on('click', '.first-page', function(e) {
                        e.preventDefault();
                        if (currentPage > 1) {
                            currentPage = 1;
                            showPage(currentPage);
                        }
                    });
                    
                    $table.on('click', '.prev-page', function(e) {
                        e.preventDefault();
                        if (currentPage > 1) {
                            currentPage--;
                            showPage(currentPage);
                        }
                    });
                    
                    $table.on('click', '.next-page', function(e) {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            currentPage++;
                            showPage(currentPage);
                        }
                    });
                    
                    $table.on('click', '.last-page', function(e) {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            currentPage = totalPages;
                            showPage(currentPage);
                        }
                    });
                    
                    $table.on('change', '.current-page', function() {
                        const page = parseInt($(this).val());
                        if (!isNaN(page) && page >= 1 && page <= totalPages) {
                            currentPage = page;
                            showPage(currentPage);
                        } else {
                            $(this).val(currentPage);
                        }
                    });
                    
                    // Show first page by default
                    showPage(1);
                }
                
                // Show specific page
                function showPage(page) {
                    const start = (page - 1) * rowsPerPage;
                    const end = start + rowsPerPage;
                    
                    // Hide all rows
                    $rows.hide();
                    
                    // Show rows for current page
                    $rows.slice(start, end).show();
                    
                    // Update pagination controls
                    $table.find('.current-page').val(page);
                    
                    // Update button states
                    $table.find('.first-page, .prev-page').toggleClass('disabled', page === 1);
                    $table.find('.next-page, .last-page').toggleClass('disabled', page === totalPages);
                    
                    // Update URL without page reload
                    const url = new URL(window.location);
                    url.searchParams.set('paged', page);
                    window.history.pushState({}, '', url);
                }
                
                initPagination();
            }
            
            // Initialize pagination
            updatePagination();
            
            // Handle search
            $('#teacher-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                const $rows = $('.teacher-row');
                let visibleCount = 0;
                
                $rows.each(function() {
                    const $row = $(this);
                    const text = $row.text().toLowerCase();
                    if (text.includes(searchTerm)) {
                        $row.show();
                        visibleCount++;
                    } else {
                        $row.hide();
                    }
                });
                
                // Update pagination with filtered results
                $('.displaying-num').text(visibleCount + ' ' + (visibleCount === 1 ? 'item' : 'items'));
                updatePagination();
            });
            
            // Handle row click
            $('.teacher-table-container').on('click', '.teacher-row', function(e) {
                // Don't trigger for input clicks (handled separately)
                if ($(e.target).is('input, a, button, .dashicons')) return;
                
                // Uncheck all radios
                $('.teacher-radio').prop('checked', false);
                
                // Check the radio in this row
                var $radio = $(this).find('.teacher-radio');
                $radio.prop('checked', true);
                
                // Update row styling
                $('.teacher-row').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Handle radio button click
            $('.teacher-radio').on('click', function(e) {
                e.stopPropagation();
                $('.teacher-row').removeClass('selected');
                $(this).closest('tr').addClass('selected');
            });
            
            // Search functionality
            $('#teacher-search').on('keyup', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                if (searchTerm.length < 2) {
                    $('.teacher-row').show();
                    return;
                }
                
                $('.teacher-row').each(function() {
                    var $row = $(this);
                    var name = $row.data('name');
                    var email = $row.data('email');
                    var phone = $row.data('phone');
                    
                    if (name.includes(searchTerm) || 
                        email.includes(searchTerm) || 
                        phone.includes(searchTerm)) {
                        $row.show();
                    } else {
                        $row.hide();
                    }
                });
            });
            
            // Sorting functionality
            $('.sortable').on('click', function() {
                var $header = $(this);
                var $table = $header.closest('table');
                var $rows = $table.find('tbody > tr').get();
                var column = $header.index();
                var sortDir = $header.hasClass('sorted-desc') ? 1 : -1;
                
                // Reset sort indicators
                $table.find('.sortable').removeClass('sorted-asc sorted-desc');
                
                // Toggle sort direction
                $header.addClass(sortDir === 1 ? 'sorted-asc' : 'sorted-desc');
                $header.find('.sorting-indicator')
                    .removeClass('dashicons-arrow-up dashicons-arrow-down')
                    .addClass('dashicons-' + (sortDir === 1 ? 'arrow-up' : 'arrow-down'));
                
                // Sort rows
                $rows.sort(function(a, b) {
                    var aVal = $(a).find('td').eq(column).text().toLowerCase();
                    var bVal = $(b).find('td').eq(column).text().toLowerCase();
                    return aVal.localeCompare(bVal) * sortDir;
                });
                
                // Re-append rows
                $.each($rows, function(index, row) {
                    $table.find('tbody').append(row);
                });
            });
            
            // Initialize sorting indicators
            $('.sortable').each(function() {
                $(this).append(' <span class="sorting-indicator dashicons dashicons-arrow-down"></span>');
            });
            
            // Initialize selected row
            $('.teacher-row[data-selected="true"]').addClass('selected');
        });
        </script>
    </div>
    
    <div class="form-section new-teacher-section">
        <h3><?php _e('Or Create New Teacher', 'hello-theme-child'); ?></h3>
        
        <div class="form-row">
            <div class="form-field">
                <label for="new_teacher_first_name"><?php _e('First Name', 'hello-theme-child'); ?> *</label>
                <input type="text" name="new_teacher_first_name" id="new_teacher_first_name" class="regular-text" required>
            </div>
            
            <div class="form-field">
                <label for="new_teacher_last_name"><?php _e('Last Name', 'hello-theme-child'); ?> *</label>
                <input type="text" name="new_teacher_last_name" id="new_teacher_last_name" class="regular-text" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-field">
                <label for="new_teacher_phone"><?php _e('Phone Number', 'hello-theme-child'); ?> *</label>
                <input type="tel" name="new_teacher_phone" id="new_teacher_phone" class="regular-text" 
                       pattern="[0-9]{9,15}" title="<?php esc_attr_e('Please enter a valid phone number (9-15 digits)', 'hello-theme-child'); ?>" required>
                <p class="description"><?php _e('This will be the username for login', 'hello-theme-child'); ?></p>
            </div>
            
            <div class="form-field">
                <label for="new_teacher_email"><?php _e('Email', 'hello-theme-child'); ?></label>
                <input type="email" name="new_teacher_email" id="new_teacher_email" class="regular-text">
                <p class="description"><?php _e('Optional - can be left blank', 'hello-theme-child'); ?></p>
            </div>
        </div>
        
        <div class="form-field">
            <label>
                <input type="checkbox" name="send_credentials" id="send_credentials" value="1" checked>
                <?php _e('Send login credentials to teacher', 'hello-theme-child'); ?>
            </label>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Auto-format phone number
        $('#new_teacher_phone').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Toggle between existing and new teacher
        $('input[name="teacher_type"]').on('change', function() {
            if ($(this).val() === 'new') {
                $('.new-teacher-section').show();
                $('.existing-teacher-section').hide();
                // Remove required from existing teacher radio buttons
                $('.teacher-radio').prop('required', false);
                // Add required to new teacher fields
                $('.new-teacher-section [required]').prop('required', true);
            } else {
                $('.new-teacher-section').hide();
                $('.existing-teacher-section').show();
                // Remove required from new teacher fields
                $('.new-teacher-section [required]').prop('required', false);
                // Add required to teacher radio buttons
                $('.teacher-radio').prop('required', true);
            }
        });
        
        // Handle form submission
        $('.wizard-form').on('submit', function(e) {
            var form = this;
            
            // Only validate visible fields
            if ($('input[name="teacher_type"]:checked').val() === 'existing') {
                // For existing teacher, check if a teacher is selected
                if ($('.teacher-radio:checked').length === 0) {
                    e.preventDefault();
                    alert('<?php echo esc_js(__('Please select a teacher', 'hello-theme-child')); ?>');
                    return false;
                }
            } else {
                // For new teacher, validate required fields
                var isValid = true;
                $('.new-teacher-section [required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('error');
                    } else {
                        $(this).removeClass('error');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('<?php echo esc_js(__('Please fill in all required fields', 'hello-theme-child')); ?>');
                    return false;
                }
                
                // Phone number validation
                var phone = $('#new_teacher_phone').val();
                if (phone && !/^\d{9,15}$/.test(phone)) {
                    e.preventDefault();
                    alert('<?php echo esc_js(__('Please enter a valid phone number (9-15 digits)', 'hello-theme-child')); ?>');
                    return false;
                }
            }
            
            return true;
        });
        
        // Initialize form state
        $('input[name="teacher_type"]:checked').trigger('change');
    });
    </script>
    
    <style>
    /* Add error styling for invalid fields */
    .error {
        border-color: #dc3232 !important;
    }
    
    /* Ensure proper spacing for error messages */
    .form-field {
        margin-bottom: 15px;
    }
    
    .form-field .error-message {
        color: #dc3232;
        font-size: 12px;
        margin-top: 5px;
        display: none;
    }
    
    .form-field.error .error-message {
        display: block;
    }
    </style>
</div>
