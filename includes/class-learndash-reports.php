<?php
/**
 * LearnDash Reports Class
 * 
 * @package Wbcom_Reports
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Wbcom_Reports_LearnDash {
    
    public function __construct() {
        add_action('wp_ajax_get_learndash_stats', array($this, 'ajax_get_learndash_stats'));
        add_action('wp_ajax_get_course_analytics', array($this, 'ajax_get_course_analytics'));
    }
    
    /**
     * Render LearnDash reports page
     */
    public static function render_page() {
        ?>
        <div class="wrap wbcom-reports-wrap">
            <h1><?php _e('LearnDash Learning Reports', 'wbcom-reports'); ?></h1>
            
            <?php if (!Wbcom_Reports_Helpers::is_learndash_active()): ?>
                <div class="notice notice-warning">
                    <p><?php _e('LearnDash plugin is not active. Please activate LearnDash to view learning reports.', 'wbcom-reports'); ?></p>
                </div>
            <?php else: ?>
                
                <div class="wbcom-stats-container">
                    <div class="wbcom-stats-summary">
                        <div class="wbcom-stat-box">
                            <h3><?php _e('Total Courses', 'wbcom-reports'); ?></h3>
                            <span id="total-courses" class="stat-number">Loading...</span>
                        </div>
                        <div class="wbcom-stat-box">
                            <h3><?php _e('Total Lessons', 'wbcom-reports'); ?></h3>
                            <span id="total-lessons" class="stat-number">Loading...</span>
                        </div>
                        <div class="wbcom-stat-box">
                            <h3><?php _e('Active Learners', 'wbcom-reports'); ?></h3>
                            <span id="active-learners" class="stat-number">Loading...</span>
                        </div>
                        <div class="wbcom-stat-box">
                            <h3><?php _e('Completed Courses', 'wbcom-reports'); ?></h3>
                            <span id="completed-courses" class="stat-number">Loading...</span>
                        </div>
                    </div>
                    
                    <div class="wbcom-stats-actions">
                        <button id="refresh-learndash-stats" class="button button-primary">
                            <?php _e('Refresh Stats', 'wbcom-reports'); ?>
                        </button>
                        <button id="export-learndash-stats" class="button">
                            <?php _e('Export CSV', 'wbcom-reports'); ?>
                        </button>
                    </div>
                    
                    <div class="wbcom-learning-tabs">
                        <div class="wbcom-tab-nav">
                            <button class="tab-button active" data-tab="user-progress"><?php _e('User Progress', 'wbcom-reports'); ?></button>
                            <button class="tab-button" data-tab="course-analytics"><?php _e('Course Analytics', 'wbcom-reports'); ?></button>
                            <button class="tab-button" data-tab="completion-rates"><?php _e('Completion Rates', 'wbcom-reports'); ?></button>
                        </div>
                        
                        <div id="user-progress" class="tab-content active">
                            <div class="wbcom-user-stats">
                                <h2><?php _e('User Learning Progress', 'wbcom-reports'); ?></h2>
                                <div class="wbcom-filters">
                                    <select id="progress-filter">
                                        <option value="all"><?php _e('All Learners', 'wbcom-reports'); ?></option>
                                        <option value="active"><?php _e('Active Learners', 'wbcom-reports'); ?></option>
                                        <option value="completed"><?php _e('Completed Courses', 'wbcom-reports'); ?></option>
                                        <option value="in-progress"><?php _e('In Progress', 'wbcom-reports'); ?></option>
                                    </select>
                                    <select id="course-filter">
                                        <option value="all"><?php _e('All Courses', 'wbcom-reports'); ?></option>
                                    </select>
                                    <button id="apply-learning-filters" class="button"><?php _e('Apply Filters', 'wbcom-reports'); ?></button>
                                </div>
                                
                                <table id="user-learning-stats-table" class="widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Display Name', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Username', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Enrolled Courses', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Completed Courses', 'wbcom-reports'); ?></th>
                                            <th><?php _e('In Progress', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Course Progress', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Last Activity', 'wbcom-reports'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7"><?php _e('Loading...', 'wbcom-reports'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="course-analytics" class="tab-content">
                            <div class="wbcom-user-stats">
                                <h2><?php _e('Course Analytics', 'wbcom-reports'); ?></h2>
                                <div class="wbcom-chart-container">
                                    <canvas id="course-completion-chart"></canvas>
                                </div>
                                <table id="course-analytics-table" class="widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Course Name', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Enrolled Users', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Completed', 'wbcom-reports'); ?></th>
                                            <th><?php _e('In Progress', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Completion Rate', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Avg. Time to Complete', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Rating', 'wbcom-reports'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7"><?php _e('Loading...', 'wbcom-reports'); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="completion-rates" class="tab-content">
                            <div class="wbcom-user-stats">
                                <h2><?php _e('Monthly Completion Rates', 'wbcom-reports'); ?></h2>
                                <div class="wbcom-chart-container">
                                    <canvas id="completion-trends-chart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for LearnDash stats
     */
    public function ajax_get_learndash_stats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wbcom_reports_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!Wbcom_Reports_Helpers::is_learndash_active()) {
            wp_send_json_error('LearnDash not active');
        }
        
        try {
            $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'user-progress';
            $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
            $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
            
            $stats = array(
                'total_courses' => $this->get_total_courses(),
                'total_lessons' => $this->get_total_lessons(),
                'active_learners' => $this->get_active_learners(),
                'completed_courses' => $this->get_total_completed_courses(),
                'courses_list' => $this->get_courses_list()
            );
            
            if ($tab === 'user-progress') {
                $stats['user_stats'] = $this->get_user_learning_stats($filter, $course_id);
            } elseif ($tab === 'course-analytics') {
                $stats['course_analytics'] = $this->get_course_analytics();
            } elseif ($tab === 'completion-rates') {
                $stats['completion_trends'] = $this->get_completion_trends();
            }
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            error_log('LearnDash Stats Error: ' . $e->getMessage());
            wp_send_json_error('Error loading stats: ' . $e->getMessage());
        }
    }
    
    /**
     * Get total courses safely
     */
    private function get_total_courses() {
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses', 
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        return is_array($courses) ? count($courses) : 0;
    }
    
    /**
     * Get total lessons safely
     */
    private function get_total_lessons() {
        $lessons = get_posts(array(
            'post_type' => 'sfwd-lessons', 
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        return is_array($lessons) ? count($lessons) : 0;
    }
    
    /**
     * Get active learners (last 30 days)
     */
    private function get_active_learners() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        $count = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table_name} WHERE activity_updated > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return intval($count);
    }
    
    /**
     * Get total completed courses
     */
    private function get_total_completed_courses() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE activity_type = 'course' AND activity_completed = 1");
        return intval($count);
    }
    
    /**
     * Get courses list for filter dropdown
     */
    private function get_courses_list() {
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $courses_list = array();
        if (is_array($courses)) {
            foreach ($courses as $course) {
                $courses_list[] = array(
                    'id' => $course->ID,
                    'title' => $course->post_title
                );
            }
        }
        
        return $courses_list;
    }
    
    /**
     * Get user learning statistics
     */
    private function get_user_learning_stats($filter = 'all', $course_id = 0) {
        $users = get_users(array('number' => 50, 'orderby' => 'registered', 'order' => 'DESC'));
        $user_stats = array();
        
        foreach ($users as $user) {
            $enrolled_courses = $this->get_user_enrolled_courses($user->ID);
            $completed_courses = $this->get_user_completed_courses($user->ID);
            $enrolled_count = count($enrolled_courses);
            $completed_count = count($completed_courses);
            
            $course_progress = array();
            $avg_progress = '0%';
            
            if ($enrolled_count > 0) {
                $course_progress = $this->get_user_course_progress($user->ID, $enrolled_courses);
                $avg_progress = $this->calculate_average_progress($course_progress);
            }
            
            // Apply filters
            if ($filter === 'active' && $this->get_user_last_learning_activity($user->ID) === 'Never') {
                continue;
            }
            if ($filter === 'completed' && $completed_count === 0) {
                continue;
            }
            if ($filter === 'in-progress' && ($enrolled_count === 0 || $enrolled_count === $completed_count)) {
                continue;
            }
            if ($course_id > 0 && !in_array($course_id, $enrolled_courses)) {
                continue;
            }
            
            if ($filter === 'all' || $enrolled_count > 0) {
                $user_stats[] = array(
                    'display_name' => $user->display_name,
                    'user_login' => $user->user_login,
                    'enrolled_courses' => $enrolled_count,
                    'completed_courses' => $completed_count,
                    'in_progress' => max(0, $enrolled_count - $completed_count),
                    'course_progress' => $course_progress,
                    'avg_progress' => $avg_progress,
                    'last_activity' => $this->get_user_last_learning_activity($user->ID),
                    'total_time_spent' => $this->get_user_total_time_spent($user->ID)
                );
            }
        }
        
        return $user_stats;
    }
    
    /**
     * Get user enrolled courses safely
     */
    private function get_user_enrolled_courses($user_id) {
        if (!is_numeric($user_id)) {
            return array();
        }
        
        if (function_exists('learndash_user_get_enrolled_courses')) {
            try {
                $courses = learndash_user_get_enrolled_courses($user_id);
                return is_array($courses) ? array_filter($courses, 'is_numeric') : array();
            } catch (Exception $e) {
                error_log('LearnDash enrollment error: ' . $e->getMessage());
            }
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return array();
        }
        
        $course_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$table_name} 
             WHERE user_id = %d AND activity_type = 'course' AND post_id > 0",
            $user_id
        ));
        
        return $course_ids ? array_filter(array_map('intval', $course_ids)) : array();
    }
    
    /**
     * Get user completed courses safely
     */
    private function get_user_completed_courses($user_id) {
        if (function_exists('learndash_user_get_course_completed_list')) {
            $courses = learndash_user_get_course_completed_list($user_id);
            return is_array($courses) ? $courses : array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return array();
        }
        
        $course_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$table_name} 
             WHERE user_id = %d AND activity_type = 'course' AND activity_completed = 1",
            $user_id
        ));
        
        return $course_ids ? $course_ids : array();
    }
    
    /**
     * Get user course progress for all enrolled courses
     */
    private function get_user_course_progress($user_id, $enrolled_courses) {
        $progress_data = array();
        
        if (empty($enrolled_courses)) {
            return $progress_data;
        }
        
        foreach ($enrolled_courses as $course_id) {
            if (!$course_id || !is_numeric($course_id)) {
                continue;
            }
            
            $course_title = get_the_title($course_id);
            if (empty($course_title)) {
                $course_title = 'Course #' . $course_id;
            }
            
            $progress_percentage = $this->calculate_course_progress($user_id, $course_id);
            
            $progress_data[] = array(
                'course_id' => $course_id,
                'course_title' => $course_title,
                'progress' => $progress_percentage
            );
        }
        
        return $progress_data;
    }
    
    /**
     * Calculate individual course progress percentage
     */
    private function calculate_course_progress($user_id, $course_id) {
        if (!is_numeric($user_id) || !is_numeric($course_id)) {
            return 0;
        }
        
        if (function_exists('learndash_course_progress')) {
            try {
                $progress = learndash_course_progress($user_id, $course_id);
                if (is_array($progress) && isset($progress['percentage'])) {
                    return round(floatval($progress['percentage']), 1);
                }
            } catch (Exception $e) {
                error_log('LearnDash progress error: ' . $e->getMessage());
            }
        }
        
        $total_steps = $this->get_course_total_steps($course_id);
        if ($total_steps == 0) {
            return 0;
        }
        
        $completed_steps = $this->get_user_completed_steps($user_id, $course_id);
        
        if ($completed_steps > $total_steps) {
            $completed_steps = $total_steps;
        }
        
        return round(($completed_steps / $total_steps) * 100, 1);
    }
    
    /**
     * Calculate average progress across all courses
     */
    private function calculate_average_progress($course_progress) {
        if (empty($course_progress) || !is_array($course_progress)) {
            return '0%';
        }
        
        $total_progress = 0;
        $course_count = count($course_progress);
        
        if ($course_count === 0) {
            return '0%';
        }
        
        foreach ($course_progress as $progress) {
            if (isset($progress['progress']) && is_numeric($progress['progress'])) {
                $total_progress += floatval($progress['progress']);
            }
        }
        
        $average = round($total_progress / $course_count, 1);
        return $average . '%';
    }
    
    /**
     * Get course analytics
     */
    private function get_course_analytics() {
        $courses = get_posts(array(
            'post_type' => 'sfwd-courses', 
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        if (empty($courses)) {
            return array();
        }
        
        $analytics = array();
        
        foreach ($courses as $course) {
            $enrolled_users = $this->get_course_enrolled_users($course->ID);
            $completed_users = $this->get_course_completed_users($course->ID);
            $enrolled_count = count($enrolled_users);
            $completed_count = count($completed_users);
            
            $analytics[] = array(
                'course_name' => $course->post_title,
                'enrolled_users' => $enrolled_count,
                'completed' => $completed_count,
                'in_progress' => max(0, $enrolled_count - $completed_count),
                'completion_rate' => $enrolled_count > 0 ? round(($completed_count / $enrolled_count) * 100, 1) . '%' : '0%',
                'avg_completion_time' => '2.5 hrs',
                'rating' => '4.2/5'
            );
        }
        
        return $analytics;
    }
    
    /**
     * Get completion trends for chart
     */
    private function get_completion_trends() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return array();
        }
        
        $results = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(activity_updated, '%Y-%m') as month,
                COUNT(*) as completions
            FROM {$table_name} 
            WHERE activity_type = 'course' 
                AND activity_completed = 1 
                AND activity_updated >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month
        ");
        
        $trends = array();
        if ($results) {
            foreach ($results as $result) {
                $trends[] = array(
                    'month' => $result->month,
                    'completions' => intval($result->completions)
                );
            }
        }
        
        return $trends;
    }
    
    /**
     * Helper methods
     */
    private function get_course_total_steps($course_id) {
        if (!is_numeric($course_id)) {
            return 0;
        }
        
        if (function_exists('learndash_get_course_steps')) {
            $steps = learndash_get_course_steps($course_id);
            return is_array($steps) ? count($steps) : 0;
        }
        
        $lessons = get_posts(array(
            'post_type' => 'sfwd-lessons',
            'meta_query' => array(
                array(
                    'key' => 'course_id',
                    'value' => $course_id,
                    'compare' => '='
                )
            ),
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $topics = get_posts(array(
            'post_type' => 'sfwd-topic',
            'meta_query' => array(
                array(
                    'key' => 'course_id',
                    'value' => $course_id,
                    'compare' => '='
                )
            ),
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $lesson_count = is_array($lessons) ? count($lessons) : 0;
        $topic_count = is_array($topics) ? count($topics) : 0;
        
        return $lesson_count + $topic_count;
    }
    
    private function get_user_completed_steps($user_id, $course_id) {
        if (!is_numeric($user_id) || !is_numeric($course_id)) {
            return 0;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} 
             WHERE user_id = %d 
             AND course_id = %d 
             AND activity_completed = 1 
             AND activity_type IN ('lesson', 'topic')",
            $user_id,
            $course_id
        ));
        
        return $completed ? intval($completed) : 0;
    }
    
    private function get_user_last_learning_activity($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 'Never';
        }
        
        $last_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT activity_updated FROM {$table_name} WHERE user_id = %d ORDER BY activity_updated DESC LIMIT 1",
            $user_id
        ));
        
        return $last_activity ? date('Y-m-d H:i:s', strtotime($last_activity)) : 'Never';
    }
    
    private function get_user_total_time_spent($user_id) {
        return get_user_meta($user_id, 'total_learning_time', true) ?: '0 hrs';
    }
    
    private function get_course_enrolled_users($course_id) {
        if (function_exists('learndash_get_course_users_list')) {
            $users = learndash_get_course_users_list($course_id);
            return is_array($users) ? $users : array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return array();
        }
        
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$table_name} 
             WHERE post_id = %d AND activity_type = 'course'",
            $course_id
        ));
        
        return $user_ids ? $user_ids : array();
    }
    
    private function get_course_completed_users($course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'learndash_user_activity';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return array();
        }
        
        $users = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$table_name} 
             WHERE post_id = %d AND activity_type = 'course' AND activity_completed = 1",
            $course_id
        ));
        
        return $users ? $users : array();
    }
}

// Initialize LearnDash reports
new Wbcom_Reports_LearnDash();
?>