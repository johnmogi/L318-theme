<div class="wizard-step-content">
    <h2><?php _e('Step 2: Class Information', 'hello-theme-child'); ?></h2>
    
    <div class="form-section">
        <div class="tablenav top">
            <div class="alignleft actions">
                <label for="filter-search" class="screen-reader-text"><?php _e('Search Classes', 'hello-theme-child'); ?></label>
                <input type="search" id="filter-search" name="s" value="" placeholder="<?php esc_attr_e('Search classes...', 'hello-theme-child'); ?>">
                
                <label for="filter-status" class="screen-reader-text"><?php _e('Filter by status', 'hello-theme-child'); ?></label>
                <select name="status" id="filter-status">
                    <option value=""><?php _e('All Statuses', 'hello-theme-child'); ?></option>
                    <option value="active"><?php _e('Active', 'hello-theme-child'); ?></option>
                    <option value="inactive"><?php _e('Inactive', 'hello-theme-child'); ?></option>
                </select>
                
                <button type="button" class="button" id="filter-button"><?php _e('Filter', 'hello-theme-child'); ?></button>
            </div>
        </div>
        
        <div class="class-table-container" style="margin: 1em 0; position: relative;">
            <?php if (!empty($errors)) : ?>
                <div class="notice notice-error" style="margin: 0 0 15px;">
                    <p><?php echo esc_html(implode('<br>', $errors)); ?></p>
                </div>
            <?php endif; ?>
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('Select', 'hello-theme-child'); ?></th>
                        <th class="sortable <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'title' ? 'sorted ' . strtolower($_GET['order']) : 'sortable'; ?>" data-sort="title">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'title', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'title' && $_GET['order'] === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span><?php _e('Class Name', 'hello-theme-child'); ?></span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator"></span>
                                </span>
                            </a>
                        </th>
                        <th class="sortable <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'students' ? 'sorted ' . strtolower($_GET['order']) : 'sortable'; ?>" data-sort="students">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'students', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'students' && $_GET['order'] === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span><?php _e('Students', 'hello-theme-child'); ?></span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator"></span>
                                </span>
                            </a>
                        </th>
                        <th class="sortable <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'courses' ? 'sorted ' . strtolower($_GET['order']) : 'sortable'; ?>" data-sort="courses">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'courses', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'courses' && $_GET['order'] === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span><?php _e('Courses', 'hello-theme-child'); ?></span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator"></span>
                                </span>
                            </a>
                        </th>
                        <th class="sortable <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'teacher' ? 'sorted ' . strtolower($_GET['order']) : 'sortable'; ?>" data-sort="teacher">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'teacher', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'teacher' && $_GET['order'] === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span><?php _e('Teacher', 'hello-theme-child'); ?></span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator"></span>
                                </span>
                            </a>
                        </th>
                        <th class="sortable <?php echo isset($_GET['orderby']) && $_GET['orderby'] === 'created' ? 'sorted ' . strtolower($_GET['order']) : 'sortable'; ?>" data-sort="created">
                            <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'created', 'order' => (isset($_GET['orderby']) && $_GET['orderby'] === 'created' && $_GET['order'] === 'asc') ? 'desc' : 'asc'))); ?>">
                                <span><?php _e('Created', 'hello-theme-child'); ?></span>
                                <span class="sorting-indicators">
                                    <span class="sorting-indicator"></span>
                                </span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody id="class-list">
                    <?php if (!empty($classes)) : ?>
                        <?php 
                        $selected_class_id = !empty($form_data['class_id']) ? (int)$form_data['class_id'] : 0;
                        foreach ($classes as $class) : 
                            $is_selected = ($class->ID === $selected_class_id);
                            $student_count = 0; // Get actual student count
                            $course_count = 0; // Get actual course count
                            $teacher_name = ''; // Get teacher name
                            $created_date = get_the_date('', $class->ID);
                        ?>
                            <tr class="class-row <?php echo $is_selected ? 'selected' : ''; ?>" 
                                data-title="<?php echo esc_attr(strtolower($class->post_title)); ?>"
                                data-students="<?php echo esc_attr($student_count); ?>"
                                data-courses="<?php echo esc_attr($course_count); ?>"
                                data-teacher="<?php echo esc_attr(strtolower($teacher_name)); ?>"
                                data-created="<?php echo esc_attr(strtotime($created_date)); ?>"
                                <?php echo $is_selected ? 'data-selected="true"' : ''; ?>>
                                <td>
                                    <input type="radio" 
                                           name="class_id" 
                                           value="<?php echo esc_attr($class->ID); ?>" 
                                           id="class_<?php echo esc_attr($class->ID); ?>" 
                                           class="class-radio"
                                           <?php echo $is_selected ? 'checked' : ''; ?>>
                                    <input type="hidden" name="class_selected" value="<?php echo $is_selected ? '1' : '0'; ?>">
                                </td>
                                <td>
                                    <label for="class_<?php echo esc_attr($class->ID); ?>">
                                        <?php echo esc_html($class->post_title); ?>
                                    </label>
                                </td>
                                <td><?php echo esc_html($student_count); ?></td>
                                <td><?php echo esc_html($course_count); ?></td>
                                <td><?php echo esc_html($teacher_name); ?></td>
                                <td><?php echo esc_html($created_date); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <?php _e('No classes found.', 'hello-theme-child'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="tablenav-pages">
                            <span class="displaying-num">
                                <?php 
                                $count = is_array($classes) ? count($classes) : 0;
                                printf(_n('%s item', '%s items', $count, 'hello-theme-child'), number_format_i18n($count));
                                ?>
                            </span>
                            <span class="pagination-links">
                                <?php 
                                $current_page = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
                                $total_pages = 1; // This should be set based on your actual pagination logic
                                ?>
                                <a class="first-page button" href="<?php echo esc_url(add_query_arg('paged', 1)); ?>" <?php echo $current_page <= 1 ? 'disabled="disabled"' : ''; ?>><span class="screen-reader-text"><?php _e('First page', 'hello-theme-child'); ?></span><span aria-hidden="true">«</span></a>
                                <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', max(1, $current_page - 1))); ?>" <?php echo $current_page <= 1 ? 'disabled="disabled"' : ''; ?>><span class="screen-reader-text"><?php _e('Previous page', 'hello-theme-child'); ?></span><span aria-hidden="true">‹</span></a>
                                <span class="paging-input">
                                    <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'hello-theme-child'); ?></label>
                                    <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo esc_attr($current_page); ?>" size="1" aria-describedby="table-paging">
                                    <span class="tablenav-paging-text"> <?php _e('of', 'hello-theme-child'); ?> <span class="total-pages"><?php echo esc_html($total_pages); ?></span></span>
                                </span>
                                <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', min($total_pages, $current_page + 1))); ?>" <?php echo $current_page >= $total_pages ? 'disabled="disabled"' : ''; ?>><span class="screen-reader-text"><?php _e('Next page', 'hello-theme-child'); ?></span><span aria-hidden="true">›</span></a>
                                <a class="last-page button" href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>" <?php echo $current_page >= $total_pages ? 'disabled="disabled"' : ''; ?>><span class="screen-reader-text"><?php _e('Last page', 'hello-theme-child'); ?></span><span aria-hidden="true">»</span></a>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <p class="description"><?php _e('Select a class from the list above or create a new one below', 'hello-theme-child'); ?></p>
    </div>
    
    <div class="form-section" style="margin-top: 2em; border-top: 1px solid #ccd0d4; padding: 1em; background: #f9f9f9; border-radius: 4px;">
        <h3><?php _e('Or Create New Class', 'hello-theme-child'); ?></h3>
        <div class="form-field">
            <label for="new_class_name"><?php _e('Class Name', 'hello-theme-child'); ?> <span class="required">*</span></label>
            <input type="text" id="new_class_name" name="new_class_name" value="<?php echo esc_attr($new_class_name); ?>" class="regular-text" data-required-message="<?php esc_attr_e('Class name is required', 'hello-theme-child'); ?>" style="width: 100%; max-width: 400px;">
        </div>
        
        <div class="form-field">
            <label for="new_class_description"><?php _e('Description', 'hello-theme-child'); ?></label>
            <textarea id="new_class_description" name="new_class_description" rows="3" 
                      class="large-text" style="width: 100%; max-width: 600px;"></textarea>
        </div>
    </div>
    
    <style>
    /* Table styles */
    .class-table-container {
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        background: #fff;
        margin-top: 10px;
        position: relative;
    }
    
    .class-table-container table {
        margin: 0;
        border: none;
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
    }
    
    .class-table-container th {
        position: sticky;
        top: 0;
        background: #f0f0f1;
        z-index: 1;
        font-weight: 600;
        color: #1d2327;
        padding: 12px 10px;
        border-bottom: 1px solid #c3c4c7;
    }
    
    .class-table-container th.sortable {
        cursor: pointer;
        position: relative;
        padding: 8px 10px;
    }
    
    .class-table-container th.sortable a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        color: inherit;
    }
    
    .class-table-container th.sortable .sorting-indicators {
        position: relative;
        margin-left: 10px;
    }
    
    .class-table-container th.sortable .sorting-indicator {
        display: inline-block;
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid #a0a5aa;
        margin-left: 4px;
        vertical-align: middle;
    }
    
    .class-table-container th.sorted.asc .sorting-indicator {
        border-top: none;
        border-bottom: 4px solid #0073aa;
    }
    
    .class-table-container th.sorted.desc .sorting-indicator {
        border-top: 4px solid #0073aa;
        border-bottom: none;
    }
    
    .class-table-container th.sortable .sorting-indicator {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #646970;
    }
    
    .class-table-container td {
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f1;
        line-height: 1.4;
    }
    
    .class-table-container tr:hover {
        background-color: #f6f7f7;
    }
    
    .class-table-container tr.selected,
    .class-table-container tr[data-selected="true"] {
        background-color: #f0f6fc;
    }
    
    .class-table-container tr:hover {
        background-color: #f9f9f9;
    }
    
    .class-table-container tr.selected:hover,
    .class-table-container tr[data-selected="true"]:hover {
        background-color: #e0e9f2;
    }
    
    .class-radio {
        margin: 0;
        width: 16px;
        height: 16px;
    }
    
    /* Pagination - Centered positioning */
    tfoot .tablenav-pages {
        float: none !important;
        margin: 1em auto !important;
        height: 28px !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        position: relative !important;
        clear: both !important;
        text-align: center !important;
    }
    
    /* Target the pagination links specifically */
    tfoot .tablenav-pages .pagination-links {
        display: inline-flex !important;
        align-items: center !important;
        float: none !important;
        margin: 0 auto !important;
    }
    
    /* Ensure the entire pagination container is centered */
    tfoot .tablenav {
        text-align: center !important;
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
        padding-top: 10px !important;
        margin-top: 10px !important;
        border-top: 1px solid #ddd !important;
    }
    
    .tablenav-pages .button {
        min-width: 24px;
        height: 24px;
        line-height: 22px;
        padding: 0;
        margin: 0 0 0 4px;
        display: inline-block;
    }
    
    .tablenav-pages .current-page {
        width: 30px;
        height: 24px;
        line-height: 24px;
        padding: 0;
        text-align: center;
        margin: 0 4px;
    }
    
    .tablenav-pages .paging-input {
        display: flex;
        align-items: center;
    }
    
    .tablenav-pages .displaying-num {
        margin-right: 10px;
    }
    
    /* RTL support */
    .rtl .class-table-container th.sortable {
        padding-right: 10px;
        padding-left: 30px;
    }
    
    .rtl .class-table-container th.sortable .sorting-indicator {
        right: auto;
        left: 10px;
    }
    
    /* Responsive */
    @media screen and (max-width: 782px) {
        .class-table-container {
            overflow-x: auto;
            display: block;
        }
        
        .class-table-container table {
            width: 100%;
            min-width: 800px;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle form submission
        $('form.wizard-form').on('submit', function(e) {
            // Only validate on step 2
            if ($('input[name="step"]').val() !== '2') return;
            
            var isValid = true;
            
            // Check if either a class is selected or a new class name is provided
            var classSelected = $('input[name="class_id"]:checked').length > 0;
            var newClassName = $('input[name="new_class_name"]').val().trim();
            
            // Clear previous errors
            $('.error-message').remove();
            $('.error').removeClass('error');
            $('.notice-error').remove();
            
            if (!classSelected && !newClassName) {
                isValid = false;
                $('.class-table-container').before(
                    '<div class="notice notice-error" style="margin: 0 0 15px;">' +
                    '<p>Please either select an existing class or enter a name for a new class</p>' +
                    '</div>'
                );
                
                // Scroll to error
                $('html, body').animate({
                    scrollTop: $('.notice-error').offset().top - 50
                }, 500);
            } else {
                // If a new class name is provided (and no class is selected), validate it
                if (newClassName && !classSelected) {
                    if (newClassName.length < 3) {
                        isValid = false;
                        $('input[name="new_class_name"]').addClass('error')
                            .after('<span class="error-message">Class name must be at least 3 characters</span>');
                    }
                }
                
                // If a class is selected, clear any value in the new class name field
                if (classSelected) {
                    $('#new_class_name').val(''); // Clear the new class name field
                    console.log('Existing class selected, cleared new class name field');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Disable submit button to prevent double submission
            $(this).find('button[type="submit"]').prop('disabled', true).text('Processing...');
        });
        
        // Handle class selection toggle
        $('input[name="class_selection_type"]').on('change', function() {
            if ($(this).val() === 'existing') {
                $('.existing-class-section').show();
                $('.new-class-section').hide();
                $('input[name="new_class_name"], textarea[name="new_class_description"]').prop('required', false);
            } else {
                $('.existing-class-section').hide();
                $('.new-class-section').show();
                $('input[name="new_class_name"]').prop('required', true);
            }
        });
        
        // Trigger change on page load in case browser remembers selection
        $('input[name="class_selection_type"]:checked').trigger('change');
        // Handle row click
        $('.class-row').on('click', function(e) {
            // Don't trigger for input clicks (handled separately)
            if ($(e.target).is('input, a, button, .dashicons')) return;
            
            // Uncheck all radios
            $('.class-radio').prop('checked', false);
            
            // Check the radio in this row
            var $radio = $(this).find('.class-radio');
            $radio.prop('checked', true);
            $('input[name="class_selected"]').val('1');
            
            // Update row styling
            $('.class-row').removeClass('selected');
            $(this).addClass('selected');
            
            // Enable the next button if it's disabled
            $('.wizard-nav-next').prop('disabled', false);
        });
        
        // Handle radio button click
        $('.class-radio').on('click', function(e) {
            e.stopPropagation();
            $('.class-row').removeClass('selected');
            $(this).closest('tr').addClass('selected');
            $('input[name="class_selected"]').val('1');
            
            // Enable the next button if it's disabled
            $('.wizard-nav-next').prop('disabled', false);
        });
        
        // Initialize selected row
        $('.class-row[data-selected="true"]').addClass('selected');
        
        // Initialize sorting indicators
        $('.sortable').each(function() {
            $(this).append(' <span class="sorting-indicator dashicons dashicons-arrow-down"></span>');
        });
        
        // Handle pagination
        $('.tablenav-pages a').on('click', function(e) {
            if ($(this).attr('disabled')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Handle page input
        $('#current-page-selector').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                var page = parseInt($(this).val());
                if (!isNaN(page) && page > 0) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('paged', page);
                    window.location.href = url.toString();
                }
            }
        });
    });
    </script>
</div>
