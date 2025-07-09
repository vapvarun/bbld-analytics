<?php
/**
 * BuddyPress Reports Class
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
                        <h3><?php _e('Total Likes', 'wbcom-reports'); ?></h3>
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
                            <option value="inactive"><?php _e('Inactive Users', 'wbcom-reports'); ?></option>
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
                                <th><?php _e('Registration Date', 'wbcom-reports'); ?></th>
                                <th><?php _e('Last Login', 'wbcom-reports'); ?></th>
                                <th><?php _e('Activities Posted', 'wbcom-reports'); ?></th>
                                <th><?php _e('Comments Posted', 'wbcom-reports'); ?></th>
                                <th><?php _e('Likes Given', 'wbcom-reports'); ?></th>
                                <th><?php _e('Profile Views', 'wbcom-reports'); ?></th>
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
            'total_likes' => $this->get_total_likes(),
            'user_stats' => $this->get_user_activity_stats($page, $filter, $date_from, $date_to),
            'pagination' => $this->get_pagination_info($page, $filter, $date_from, $date_to)
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get total users count
     */
    private function get_total_users() {
        $user_count = count_users();
        return $user_count['total_users'];
    }
    
    /**
     * Get total activities count
     */
    private function get_total_activities() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE component = 'activity' AND type = 'activity_update'");
        return intval($count);
    }
    
    /**
     * Get total activity comments count
     */
    private function get_total_activity_comments() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE component = 'activity' AND type = 'activity_comment'");
        return intval($count);
    }
    
    /**
     * Get total likes count
     */
    private function get_total_likes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity_meta';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE meta_key = 'liked_by'");
        return intval($count);
    }
    
    /**
     * Get user activity statistics with pagination and filters
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
        if ($filter === 'active') {
            $users_args['meta_query'] = array(
                array(
                    'key' => 'last_login',
                    'value' => strtotime('-30 days'),
                    'compare' => '>='
                )
            );
        } elseif ($filter === 'inactive') {
            $users_args['meta_query'] = array(
                array(
                    'key' => 'last_login',
                    'value' => strtotime('-30 days'),
                    'compare' => '<'
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
            $user_stats[] = array(
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'registration_date' => date('Y-m-d H:i:s', strtotime($user->user_registered)),
                'last_login' => Wbcom_Reports_Helpers::get_user_last_login($user->ID),
                'activity_count' => $this->get_user_activity_count($user->ID),
                'comment_count' => $this->get_user_comment_count($user->ID),
                'like_count' => $this->get_user_like_count($user->ID),
                'profile_views' => $this->get_user_profile_views($user->ID)
            );
        }
        
        return $user_stats;
    }
    
    /**
     * Get pagination information
     */
    private function get_pagination_info($page, $filter, $date_from, $date_to) {
        $per_page = 20;
        
        $users_args = array('count_total' => true);
        
        // Apply same filters as in get_user_activity_stats
        if ($filter === 'active') {
            $users_args['meta_query'] = array(
                array(
                    'key' => 'last_login',
                    'value' => strtotime('-30 days'),
                    'compare' => '>='
                )
            );
        } elseif ($filter === 'inactive') {
            $users_args['meta_query'] = array(
                array(
                    'key' => 'last_login',
                    'value' => strtotime('-30 days'),
                    'compare' => '<'
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
    
    /**
     * Get user activity count
     */
    private function get_user_activity_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND component = 'activity' AND type = 'activity_update'",
            $user_id
        ));
        return intval($count);
    }
    
    /**
     * Get user comment count
     */
    private function get_user_comment_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND component = 'activity' AND type = 'activity_comment'",
            $user_id
        ));
        return intval($count);
    }
    
    /**
     * Get user like count
     */
    private function get_user_like_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity_meta';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE meta_key = 'liked_by' AND meta_value = %d",
            $user_id
        ));
        return intval($count);
    }
    
    /**
     * Get user profile views
     */
    private function get_user_profile_views($user_id) {
        $views = get_user_meta($user_id, 'profile_views', true);
        return $views ? intval($views) : 0;
    }
}

// Initialize BuddyPress reports
new Wbcom_Reports_BuddyPress();
?>