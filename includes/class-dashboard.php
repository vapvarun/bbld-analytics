<?php
/**
 * Dashboard Reports Class
 * 
 * @package Wbcom_Reports
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Wbcom_Reports_Dashboard {
    
    public function __construct() {
        add_action('wp_ajax_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
    }
    
    /**
     * Render dashboard page
     */
    public static function render_page() {
        ?>
        <div class="wrap wbcom-reports-wrap">
            <h1><?php _e('Wbcom Reports Dashboard', 'wbcom-reports'); ?></h1>
            
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
                        <h3><?php _e('Total Courses', 'wbcom-reports'); ?></h3>
                        <span id="total-courses" class="stat-number">Loading...</span>
                    </div>
                    <div class="wbcom-stat-box">
                        <h3><?php _e('Active Groups', 'wbcom-reports'); ?></h3>
                        <span id="total-groups" class="stat-number">Loading...</span>
                    </div>
                </div>
                
                <div class="wbcom-stats-actions">
                    <button id="refresh-dashboard-stats" class="button button-primary">
                        <?php _e('Refresh Stats', 'wbcom-reports'); ?>
                    </button>
                </div>
                
                <div class="wbcom-dashboard-tables">
                    <div class="wbcom-table-section">
                        <div class="wbcom-user-stats">
                            <h2><?php _e('Top Active Users (BuddyPress)', 'wbcom-reports'); ?></h2>
                            <table id="top-users-table" class="widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Rank', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Display Name', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Username', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Activity Count', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Comments', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Last Activity', 'wbcom-reports'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6"><?php _e('Loading...', 'wbcom-reports'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="wbcom-table-section">
                        <div class="wbcom-user-stats">
                            <h2><?php _e('Top Groups by Members (BuddyPress)', 'wbcom-reports'); ?></h2>
                            <table id="top-groups-table" class="widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Rank', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Group Name', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Description', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Member Count', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Created Date', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Status', 'wbcom-reports'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6"><?php _e('Loading...', 'wbcom-reports'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_get_dashboard_stats() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wbcom_reports_nonce')) {
            wp_die('Security check failed');
        }
        
        $stats = array(
            'total_users' => $this->get_total_users(),
            'total_activities' => $this->get_total_activities(),
            'total_courses' => Wbcom_Reports_Helpers::is_learndash_active() ? $this->get_total_courses() : 0,
            'total_groups' => Wbcom_Reports_Helpers::is_buddypress_active() ? $this->get_total_bp_groups() : 0,
            'top_users' => $this->get_top_active_users(),
            'top_groups' => $this->get_top_groups_by_members()
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
        if (!Wbcom_Reports_Helpers::is_buddypress_active()) return 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'bp_activity';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE component = 'activity' AND type = 'activity_update'");
        return intval($count);
    }
    
    /**
     * Get total courses count
     */
    private function get_total_courses() {
        $courses = get_posts(array('post_type' => 'sfwd-courses', 'numberposts' => -1));
        return count($courses);
    }
    
    /**
     * Get total BuddyPress groups
     */
    private function get_total_bp_groups() {
        if (!Wbcom_Reports_Helpers::is_buddypress_active()) return 0;
        
        global $wpdb;
        $groups_table = $wpdb->prefix . 'bp_groups';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$groups_table}");
        return intval($count);
    }
    
    /**
     * Get top active users based on activity count
     */
    private function get_top_active_users($limit = 10) {
        if (!Wbcom_Reports_Helpers::is_buddypress_active()) {
            return array();
        }
        
        global $wpdb;
        $activity_table = $wpdb->prefix . 'bp_activity';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                u.user_login,
                COUNT(a.id) as activity_count,
                COUNT(CASE WHEN a.type = 'activity_comment' THEN 1 END) as comment_count,
                MAX(a.date_recorded) as last_activity
            FROM {$wpdb->users} u
            LEFT JOIN {$activity_table} a ON u.ID = a.user_id 
                AND a.component = 'activity' 
                AND a.type IN ('activity_update', 'activity_comment')
            GROUP BY u.ID
            HAVING activity_count > 0
            ORDER BY activity_count DESC
            LIMIT %d
        ", $limit));
        
        $top_users = array();
        $rank = 1;
        
        foreach ($results as $user) {
            $top_users[] = array(
                'rank' => $rank++,
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'activity_count' => intval($user->activity_count),
                'comment_count' => intval($user->comment_count),
                'last_activity' => $user->last_activity ? date('Y-m-d H:i:s', strtotime($user->last_activity)) : 'Never'
            );
        }
        
        return $top_users;
    }
    
    /**
     * Get top groups by member count
     */
    private function get_top_groups_by_members($limit = 10) {
        if (!Wbcom_Reports_Helpers::is_buddypress_active()) {
            return array();
        }
        
        global $wpdb;
        $groups_table = $wpdb->prefix . 'bp_groups';
        $groupmeta_table = $wpdb->prefix . 'bp_groups_groupmeta';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                g.id,
                g.name,
                g.description,
                g.status,
                g.date_created,
                COALESCE(gm.meta_value, 0) as member_count
            FROM {$groups_table} g
            LEFT JOIN {$groupmeta_table} gm ON g.id = gm.group_id AND gm.meta_key = 'total_member_count'
            ORDER BY CAST(COALESCE(gm.meta_value, 0) AS UNSIGNED) DESC
            LIMIT %d
        ", $limit));
        
        $top_groups = array();
        $rank = 1;
        
        foreach ($results as $group) {
            $top_groups[] = array(
                'rank' => $rank++,
                'name' => $group->name,
                'description' => wp_trim_words($group->description, 10),
                'member_count' => intval($group->member_count),
                'date_created' => date('Y-m-d', strtotime($group->date_created)),
                'status' => ucfirst($group->status)
            );
        }
        
        return $top_groups;
    }
}

// Initialize dashboard
new Wbcom_Reports_Dashboard();
?>