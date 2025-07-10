<?php
/**
 * Dashboard Reports Class - Removed BuddyPress Groups
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
                        <h3><?php _e('New Users (30 days)', 'wbcom-reports'); ?></h3>
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
                            <h2><?php _e('Top LearnDash Groups by Members', 'wbcom-reports'); ?></h2>
                            <table id="top-groups-table" class="widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Rank', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Group Name', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Members Count', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Group Leaders', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Associated Courses', 'wbcom-reports'); ?></th>
                                        <th><?php _e('Created Date', 'wbcom-reports'); ?></th>
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
            'total_groups' => $this->get_new_users_30_days(),
            'top_users' => $this->get_top_active_users(),
            'top_groups' => $this->get_top_learndash_groups()
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
     * Get new users in last 30 days
     */
    private function get_new_users_30_days() {
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $user_query = new WP_User_Query(array(
            'date_query' => array(
                array(
                    'after' => $thirty_days_ago,
                    'inclusive' => true
                )
            ),
            'count_total' => true
        ));
        
        return intval($user_query->get_total());
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
     * Get top LearnDash groups by member count
     */
    private function get_top_learndash_groups($limit = 25) {
        if (!Wbcom_Reports_Helpers::is_learndash_active()) {
            return array();
        }
        
        $group_post_type = 'groups';
        if (function_exists('learndash_get_post_type_slug')) {
            $group_post_type = learndash_get_post_type_slug('group');
        }
        
        $groups = get_posts(array(
            'post_type' => $group_post_type,
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        if (empty($groups)) {
            return array();
        }
        
        $groups_data = array();
        
        foreach ($groups as $group) {
            $group_users = array();
            $members_count = 0;
            
            // Get group users
            if (function_exists('learndash_get_groups_users')) {
                $group_users = learndash_get_groups_users($group->ID);
                $members_count = is_array($group_users) ? count($group_users) : 0;
            }
            
            // Get group leaders
            $leaders_names = 'No Leaders';
            if (function_exists('learndash_get_groups_administrators')) {
                $group_leaders = learndash_get_groups_administrators($group->ID);
                if (!empty($group_leaders)) {
                    $leader_names = array();
                    foreach ($group_leaders as $leader_id) {
                        $leader = get_user_by('ID', $leader_id);
                        if ($leader) {
                            $leader_names[] = $leader->display_name;
                        }
                    }
                    if (!empty($leader_names)) {
                        $leaders_names = implode(', ', $leader_names);
                    }
                }
            }
            
            // Get associated courses count
            $courses_count = 0;
            if (function_exists('learndash_group_enrolled_courses')) {
                $group_courses = learndash_group_enrolled_courses($group->ID);
                $courses_count = is_array($group_courses) ? count($group_courses) : 0;
            }
            
            $groups_data[] = array(
                'group_name' => $group->post_title,
                'members_count' => $members_count,
                'group_leaders' => $leaders_names,
                'courses_count' => $courses_count,
                'created_date' => date('Y-m-d', strtotime($group->post_date))
            );
        }
        
        // Sort by member count (descending)
        usort($groups_data, function($a, $b) {
            return $b['members_count'] - $a['members_count'];
        });
        
        // Limit to top 25 and add rank
        $top_groups = array_slice($groups_data, 0, $limit);
        $rank = 1;
        
        foreach ($top_groups as &$group) {
            $group['rank'] = $rank++;
        }
        
        return $top_groups;
    }
}

// Initialize dashboard
new Wbcom_Reports_Dashboard();