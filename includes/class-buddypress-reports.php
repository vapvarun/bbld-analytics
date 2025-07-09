<?php
/**
 * Fixed BuddyPress Reports Class - Updated to handle both test and real data
 * 
 * @package Wbcom_Reports
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Wbcom_Reports_BuddyPress {
    
    public function __construct() {
        add_action('wp_ajax_get_buddypress_stats', array($this, 'ajax_get_buddypress_stats'));
    }
    
    /**
     * Render BuddyPress reports page
     */
    public static function render_page() {
        ?>
        <div class="wrap wbcom-reports-wrap">
            <h1><?php _e('BuddyPress Activity Reports', 'wbcom-reports'); ?></h1>
            
            <?php if (!Wbcom_Reports_Helpers::is_buddypress_active()): ?>
                <div class="notice notice-warning">
                    <p><?php _e('BuddyPress plugin is not active. Please activate BuddyPress to view activity reports.', 'wbcom-reports'); ?></p>
                </div>
            <?php else: ?>
            
            <div class="wbcom-stats-container">
                <div class="wbcom-stats-summary">
                    <div class="wbcom-stat-box">
                        <h3><?php _e('Total Users', 'wbcom-reports'); ?></h3>
                        <span id="total-users" class="stat-number">Loading...</span>
                    </div>
                    <div class="wbcom-stat-box">
                        <h3><?php _e('Total Activities', 'wbcom-reports'); ?></h3>
                        <span id="total-activities" class="stat-number">Loading...</span>
                    </div>
                    <div class="wbcom-stat-box">
                        <h3><?php _e('Total Comments', 'wbcom-reports'); ?></h3>
                        <span id="total-comments" class="stat-number">Loading...</span>
                    </div>
                    <div class="wbcom-stat-box">
                        <h3><?php _e('Total Groups', 'wbcom-reports'); ?></h3>
                        <span id="total-likes" class="stat-number">Loading...</span>
                    </div>
                </div>
                
                <div class="wbcom-stats-actions">
                    <button id="refresh-buddypress-stats" class="button button-primary">
                        <?php _e('Refresh Stats', 'wbcom-reports'); ?>
                    </button>
                    <button id="export-buddypress-stats" class="button">
                        <?php _e('Export CSV', 'wbcom-reports'); ?>
                    </button>
                </div>
                
                <div class="wbcom-user-stats">
                    <h2><?php _e('User Activity Statistics', 'wbcom-reports'); ?></h2>
                    <div class="wbcom-filters">
                        <select id="activity-filter">
                            <option value="all"><?php _e('All Users', 'wbcom-reports'); ?></option>
                            <option value="active"><?php _e('Active Users (Last 30 days)', 'wbcom-reports'); ?></option>
                            <option value="test_users"><?php _e('Test Users (LDTT)', 'wbcom-reports'); ?></option>
                            <option value="real_users"><?php _e('Real Users (Non-Test)', 'wbcom-reports'); ?></option>
                        </select>
                        <input type="date" id="date-from" placeholder="<?php _e('From Date', 'wbcom-reports'); ?>">
                        <input type="date" id="date-to" placeholder="<?php _e('To Date', 'wbcom-reports'); ?>">
                        <button id="apply-filters" class="button"><?php _e('Apply Filters', 'wbcom-reports'); ?></button>
                    </div>
                    
                    <table id="user-activity-stats-table" class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Display Name', 'wbcom-reports'); ?></th>
                                <th><?php _e('Username', 'wbcom-reports'); ?></th>
                                <th><?php _e('User Type', 'wbcom-reports'); ?></th>
                                <th><?php _e('Registration Date', 'wbcom-reports'); ?></th>
                                <th><?php _e('Last Login', 'wbcom-reports'); ?></th>
                                <th><?php _e('Course Enrollments', 'wbcom-reports'); ?></th>
                                <th><?php _e('Groups Joined', 'wbcom-reports'); ?></th>
                                <th><?php _e('Course Progress', 'wbcom-reports'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8"><?php _e('Loading...', 'wbcom-reports'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="wbcom-pagination">
                        <button id="prev-page" class="button" disabled><?php _e('Previous', 'wbcom-reports'); ?></button>
                        <span id="page-info">Page 1 of 1</span>
                        <button id="next-page" class="button" disabled><?php _e('Next', 'wbcom-reports'); ?></button>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for BuddyPress stats
     */
    public function ajax_get_buddypress_stats() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wbcom_reports_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!Wbcom_Reports_Helpers::is_buddypress_active()) {
            wp_send_json_error('BuddyPress not active');
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $stats = array(
            'total_users' => $this->get_total_users(),
            'total_activities' => $this->get_total_activities(),
            'total_comments' => $this->get_total_activity_comments(),
            'total_likes' => $this->get_total_groups(), // Changed to groups since we're dealing with LearnDash + BP
            'user_stats' => $this->get_user_activity_stats($page, $filter, $date_from, $date_to),
            'pagination' => $this->get_pagination_info($page, $filter, $date_from, $date_to)
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get total users count - including both test and real users
     */
    private function get_total_users() {
        $user_count = count_users();
        return $user_count['total_users'];
    }
    
    /**
     * Get total activities count - if BP activities exist, otherwise count user activities
     */
    private function get_total_activities() {
        global $wpdb;
        
        // Check if BuddyPress activity table exists
        $bp_activity_table = $wpdb->prefix . 'bp_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$bp_activity_table}'") == $bp_activity_table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$bp_activity_table} WHERE component = 'activity' AND type = 'activity_update'");
            return intval($count);
        }
        
        // Fallback: count based on user activity from LDTT
        $ldtt_activities = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE '_ldtt_progress%'
        ");
        
        return intval($ldtt_activities);
    }
    
    /**
     * Get total activity comments - if BP exists, otherwise count progress activities
     */
    private function get_total_activity_comments() {
        global $wpdb;
        
        // Check if BuddyPress activity table exists
        $bp_activity_table = $wpdb->prefix . 'bp_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$bp_activity_table}'") == $bp_activity_table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$bp_activity_table} WHERE component = 'activity' AND type = 'activity_comment'");
            return intval($count);
        }
        
        // Fallback: count completed courses as "interactions"
        $completed_courses = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE '_ldtt_progress_course_%' 
            AND meta_value LIKE '%completion_rate\";i:100%'
        ");
        
        return intval($completed_courses);
    }
    
    /**
     * Get total groups count
     */
    private function get_total_groups() {
        global $wpdb;
        
        // Check BuddyPress groups first
        $bp_groups_table = $wpdb->prefix . 'bp_groups';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$bp_groups_table}'") == $bp_groups_table) {
            $bp_groups = $wpdb->get_var("SELECT COUNT(*) FROM {$bp_groups_table}");
        } else {
            $bp_groups = 0;
        }
        
        // Count LearnDash groups
        $ld_group_post_type = 'groups';
        if (function_exists('learndash_get_post_type_slug')) {
            $ld_group_post_type = learndash_get_post_type_slug('group');
        }
        
        $ld_groups = get_posts(array(
            'post_type' => $ld_group_post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $ld_groups_count = is_array($ld_groups) ? count($ld_groups) : 0;
        
        return intval($bp_groups) + $ld_groups_count;
    }
    
    /**
     * Get user activity statistics with pagination and filters - corrected for LDTT data
     */
    private function get_user_activity_stats($page = 1, $filter = 'all', $date_from = '', $date_to = '') {
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $users_args = array(
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => 'registered',
            'order' => 'DESC'
        );
        
        // Apply filters
        if ($filter === 'test_users') {
            $users_args['meta_query'] = array(
                array(
                    'key' => '_ldtt_test_user',
                    'value' => true,
                    'compare' => '='
                )
            );
        } elseif ($filter === 'real_users') {
            $users_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_ldtt_test_user',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_ldtt_test_user',
                    'value' => true,
                    'compare' => '!='
                )
            );
        } elseif ($filter === 'active') {
            $users_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => 'last_login',
                    'value' => strtotime('-30 days'),
                    'compare' => '>='
                ),
                array(
                    'key' => '_ldtt_progress_created',
                    'value' => strtotime('-30 days'),
                    'compare' => '>='
                )
            );
        }
        
        if ($date_from && $date_to) {
            $users_args['date_query'] = array(
                array(
                    'after' => $date_from,
                    'before' => $date_to,
                    'inclusive' => true
                )
            );
        }
        
        $users = get_users($users_args);
        $user_stats = array();
        
        foreach ($users as $user) {
            $user_type = $this->get_user_type($user->ID);
            $course_enrollments = $this->get_user_course_enrollments($user->ID);
            $groups_joined = $this->get_user_groups($user->ID);
            $progress_summary = $this->get_user_progress_summary($user->ID);
            
            $user_stats[] = array(
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'user_type' => $user_type,
                'registration_date' => date('Y-m-d H:i:s', strtotime($user->user_registered)),
                'last_login' => Wbcom_Reports_Helpers::get_user_last_login($user->ID),
                'course_enrollments' => $course_enrollments,
                'groups_joined' => $groups_joined,
                'course_progress' => $progress_summary
            );
        }
        
        return $user_stats;
    }
    
    /**
     * Get user type (test user or regular)
     */
    private function get_user_type($user_id) {
        $is_test_user = get_user_meta($user_id, '_ldtt_test_user', true);
        $user_type = get_user_meta($user_id, '_ldtt_user_type', true);
        
        if ($is_test_user) {
            if ($user_type) {
                return 'Test (' . ucfirst(str_replace('_', ' ', $user_type)) . ')';
            }
            return 'Test User';
        }
        
        return 'Regular User';
    }
    
    /**
     * Get user course enrollments count
     */
    private function get_user_course_enrollments($user_id) {
        global $wpdb;
        
        $enrollments = 0;
        
        // Count LDTT progress courses
        $ldtt_courses = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE user_id = %d 
            AND meta_key LIKE '_ldtt_progress_course_%%'
        ", $user_id));
        
        $enrollments += intval($ldtt_courses);
        
        // Count regular LearnDash enrollments
        $course_access = get_user_meta($user_id, '_sfwd-course_progress', true);
        if (is_array($course_access)) {
            $enrollments += count($course_access);
        }
        
        return $enrollments;
    }
    
    /**
     * Get user groups (both BuddyPress and LearnDash)
     */
    private function get_user_groups($user_id) {
        $groups_count = 0;
        
        // BuddyPress groups
        if (function_exists('groups_get_user_groups')) {
            $bp_groups = groups_get_user_groups($user_id);
            if (is_array($bp_groups) && isset($bp_groups['groups'])) {
                $groups_count += count($bp_groups['groups']);
            }
        }
        
        // LearnDash groups
        if (function_exists('learndash_get_users_group_ids')) {
            $ld_groups = learndash_get_users_group_ids($user_id);
            if (is_array($ld_groups)) {
                $groups_count += count($ld_groups);
            }
        }
        
        return $groups_count;
    }
    
    /**
     * Get user progress summary
     */
    private function get_user_progress_summary($user_id) {
        global $wpdb;
        
        // Get LDTT progress
        $progress_data = $wpdb->get_results($wpdb->prepare("
            SELECT meta_value 
            FROM {$wpdb->usermeta} 
            WHERE user_id = %d 
            AND meta_key LIKE '_ldtt_progress_course_%%'
        ", $user_id));
        
        if (empty($progress_data)) {
            return 'No progress data';
        }
        
        $total_progress = 0;
        $course_count = 0;
        $completed_courses = 0;
        
        foreach ($progress_data as $progress) {
            $data = maybe_unserialize($progress->meta_value);
            if (is_array($data) && isset($data['completion_rate'])) {
                $completion_rate = floatval($data['completion_rate']);
                $total_progress += $completion_rate;
                $course_count++;
                
                if ($completion_rate >= 100) {
                    $completed_courses++;
                }
            }
        }
        
        if ($course_count === 0) {
            return 'No progress data';
        }
        
        $avg_progress = round($total_progress / $course_count, 1);
        return "{$completed_courses}/{$course_count} completed (Avg: {$avg_progress}%)";
    }
    
    /**
     * Get pagination information
     */
    private function get_pagination_info($page, $filter, $date_from, $date_to) {
        $per_page = 20;
        
        $users_args = array('count_total' => true);
        
        // Apply same filters as in get_user_activity_stats
        if ($filter === 'test_users') {
            $users_args['meta_query'] = array(
                array(
                    'key' => '_ldtt_test_user',
                    'value' => true,
                    'compare' => '='
                )
            );
        } elseif ($filter === 'real_users') {
            $users_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_ldtt_test_user',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_ldtt_test_user',
                    'value' => true,
                    'compare' => '!='
                )
            );
        } elseif ($filter === 'active') {
            $users_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => 'last_login',
                    'value' => strtotime('-30 days'),
                    'compare' => '>='
                ),
                array(
                    'key' => '_ldtt_progress_created',
                    'value' => strtotime('-30 days'),
                    'compare' => '>='
                )
            );
        }
        
        if ($date_from && $date_to) {
            $users_args['date_query'] = array(
                array(
                    'after' => $date_from,
                    'before' => $date_to,
                    'inclusive' => true
                )
            );
        }
        
        $user_query = new WP_User_Query($users_args);
        $total_users = $user_query->get_total();
        $total_pages = ceil($total_users / $per_page);
        
        return array(
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_users' => $total_users,
            'per_page' => $per_page
        );
    }
}

// Initialize BuddyPress reports
new Wbcom_Reports_BuddyPress();