<?php
/**
 * Classes List Table
 *
 * @package Hello_Theme_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Hello_Theme_Child_Classes_List_Table
 * Custom table for displaying classes/groups with filtering and pagination
 */
class Hello_Theme_Child_Classes_List_Table extends WP_List_Table {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => __( 'Class', 'hello-theme-child' ),
            'plural'   => __( 'Classes', 'hello-theme-child' ),
            'ajax'     => false,
        ) );
    }

    /**
     * Prepare the items for the table
     *
     * @param int $teacher_id Optional teacher ID to filter by.
     */
    public function prepare_items( $teacher_id = 0 ) {
        // Column headers
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        // Get records
        $per_page = 10;
        $current_page = $this->get_pagenum();

        // Get all groups/classes - include past/inactive groups too
        $args = array(
            'post_type'      => 'groups',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'title',
            'order'          => isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'ASC',
            'post_status'    => array('publish', 'draft', 'private', 'future', 'pending'),  // Include all statuses
        );

        // Add search
        if ( ! empty( $_REQUEST['s'] ) ) {
            $args['s'] = sanitize_text_field( $_REQUEST['s'] );
        }

        // Filter by teacher if specified
        if ( $teacher_id > 0 ) {
            // Use _ld_group_leaders meta key which stores an array of teacher IDs
            // This is the key used by the Teacher Class Wizard and LearnDash
            $args['meta_query'] = array(
                array(
                    'key'     => '_ld_group_leaders',
                    'value'   => $teacher_id,
                    'compare' => 'LIKE',
                ),
            );
        }

        $query = new WP_Query( $args );
        $classes = array();

        // Get promo codes for linking
        // This will help us connect registration codes to groups
        $promo_codes_map = $this->get_promo_codes_by_group();

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                // Get student count
                $user_ids = learndash_get_groups_user_ids( $post->ID );
                $student_count = count( $user_ids );

                // Get teacher/leader info if available
                $leaders = learndash_get_groups_administrator_ids( $post->ID );
                $leader_names = array();
                
                if ( !empty( $leaders ) ) {
                    foreach ( $leaders as $leader_id ) {
                        $user_data = get_userdata( $leader_id );
                        if ( $user_data ) {
                            $leader_names[] = $user_data->display_name;
                        }
                    }
                }

                // Check if there are promo codes for this group
                $has_promo_codes = isset($promo_codes_map[$post->ID]) ? count($promo_codes_map[$post->ID]) : 0;

                // Get group status
                $status = $post->post_status;
                if ($status == 'publish') {
                    $status_display = __('Active', 'hello-theme-child');
                } else if ($status == 'draft') {
                    $status_display = __('Inactive', 'hello-theme-child');
                } else if ($status == 'private') {
                    $status_display = __('Private', 'hello-theme-child');
                } else {
                    $status_display = ucfirst($status);
                }

                $classes[] = array(
                    'ID'            => $post->ID,
                    'title'         => $post->post_title,
                    'date_created'  => get_the_date( get_option( 'date_format' ), $post->ID ),
                    'status'        => $status_display,
                    'student_count' => $student_count,
                    'leaders'       => !empty($leader_names) ? implode( ', ', $leader_names ) : __('None', 'hello-theme-child'),
                    'promo_codes'   => $has_promo_codes,
                );
            }
        }

        // Set up pagination
        $this->set_pagination_args( array(
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages,
        ) );

        $this->items = $classes;
    }

    /**
     * Get promo codes organized by group ID
     * 
     * @return array Map of group IDs to arrays of promo codes
     */
    private function get_promo_codes_by_group() {
        $promo_codes = array();
        
        // Query promo codes and organize them by group ID
        $args = array(
            'post_type'      => 'ld_promo_code',  // Correct post type
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_ld_promo_code_group_id',  // Correct meta key
                    'compare' => 'EXISTS',
                )
            )
        );
        
        $query = new WP_Query( $args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                $code_id = get_the_ID();
                $group_id = get_post_meta( $code_id, '_ld_promo_code_group_id', true );  // Correct meta key
                
                if ( !empty( $group_id ) ) {
                    if ( !isset( $promo_codes[$group_id] ) ) {
                        $promo_codes[$group_id] = array();
                    }
                    
                    $promo_codes[$group_id][] = array(
                        'id'   => $code_id,
                        'code' => get_post_meta( $code_id, '_ld_promo_code_code', true ),
                    );
                }
            }
            
            wp_reset_postdata();
        }
        
        return $promo_codes;
    }

    /**
     * Define the columns
     *
     * @return array
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'title'         => __( 'Class Name', 'hello-theme-child' ),
            'leaders'       => __( 'Teachers', 'hello-theme-child' ),
            'student_count' => __( 'Students', 'hello-theme-child' ),
            'status'        => __( 'Status', 'hello-theme-child' ),
            'promo_codes'   => __( 'Promo Codes', 'hello-theme-child' ),
            'date_created'  => __( 'Created', 'hello-theme-child' ),
        );
        return $columns;
    }

    /**
     * Define sortable columns
     *
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'title'        => array( 'title', true ),
            'date_created' => array( 'date', false ),
            'status'       => array( 'status', false ),
        );
    }

    /**
     * Checkbox column
     *
     * @param array $item Item data.
     * @return string
     */
    public function column_cb( $item ) {
        return sprintf(
            '<input type="radio" name="class_id" value="%s" %s />',
            $item['ID'],
            isset( $_REQUEST['class_id'] ) && $_REQUEST['class_id'] == $item['ID'] ? 'checked="checked"' : ''
        );
    }

    /**
     * Title column
     *
     * @param array $item Item data.
     * @return string
     */
    public function column_title( $item ) {
        $actions = array(
            'view' => sprintf(
                '<a href="%s">%s</a>',
                esc_url( add_query_arg( array(
                    'page'     => 'view-school-classes',
                    'class_id' => $item['ID'],
                ), admin_url( 'admin.php' ) ) ),
                __( 'View Students', 'hello-theme-child' )
            ),
        );

        return sprintf(
            '<strong>%1$s</strong> %2$s',
            $item['title'],
            $this->row_actions( $actions )
        );
    }
    
    /**
     * Promo codes column
     *
     * @param array $item Item data.
     * @return string
     */
    public function column_promo_codes( $item ) {
        if ($item['promo_codes'] > 0) {
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('edit.php?post_type=promo_code&group_id=' . $item['ID'])),
                sprintf(_n('%d code', '%d codes', $item['promo_codes'], 'hello-theme-child'), $item['promo_codes'])
            );
        } else {
            return '—';
        }
    }

    /**
     * Student count column
     *
     * @param array $item Item data.
     * @return string
     */
    public function column_student_count( $item ) {
        // Make student count clickable with data-class-id attribute for JavaScript handling
        return sprintf(
            '<a href="#" class="student-count-link" data-class-id="%s">%s</a>',
            esc_attr($item['ID']),
            esc_html($item['student_count'])
        );
    }
    
    /**
     * Default column renderer
     *
     * @param array  $item Item data.
     * @param string $column_name Column name.
     * @return string
     */
    public function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
    }

    /**
     * Extra table navigation controls
     *
     * @param string $which Top or bottom.
     */
    public function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            // Add a search box
            ?>
            <div class="alignleft actions">
                <?php $this->search_box( __( 'Search Classes', 'hello-theme-child' ), 'class-search' ); ?>
            </div>
            <?php
        }
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        // Future implementation for bulk actions
        // Currently not used
    }
    
    /**
     * Display the table
     *
     * @param bool $echo Whether to echo or return the output. Default is echo.
     */
    public function display_table($echo = true) {
        ob_start();
        $this->prepare_items();
        ?>
        <form id="classes-list-form" method="get">
            <input type="hidden" name="page" value="view-school-classes" />
            <?php 
            $this->display(); 
            ?>
        </form>
        <?php
        $output = ob_get_clean();
        
        if ($echo) {
            echo $output;
        }
        
        return $output;
    }
}
