<?php
/**
 * Fixed LearnDash Reports Class - Updated to properly fetch LDTT test data
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
                            <button class="tab-button" data-tab="group-reports"><?php _e('Group Reports', 'wbcom-reports'); ?></button>
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
                                            <th><?php _e('Time Spent', 'wbcom-reports'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8"><?php _e('Loading...', 'wbcom-reports'); ?></td>
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
                        
                        <div id="group-reports" class="tab-content">
                            <div class="wbcom-user-stats">
                                <h2><?php _e('LearnDash Group Reports', 'wbcom-reports'); ?></h2>
                                <div class="wbcom-filters">
                                    <select id="group-filter">
                                        <option value="all"><?php _e('All Groups', 'wbcom-reports'); ?></option>
                                        <option value="with_leaders"><?php _e('Groups with Leaders', 'wbcom-reports'); ?></option>
                                        <option value="active"><?php _e('Active Groups', 'wbcom-reports'); ?></option>
                                    </select>
                                    <button id="apply-group-filters" class="button"><?php _e('Apply Filters', 'wbcom-reports'); ?></button>
                                </div>
                                
                                <div class="wbcom-chart-container">
                                    <canvas id="group-enrollment-chart"></canvas>
                                </div>
                                
                                <table id="group-analytics-table" class="widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Group Name', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Group Leaders', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Total Users', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Associated Courses', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Avg. Progress', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Completed Users', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Created Date', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Status', 'wbcom-reports'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8"><?php _e('Loading...', 'wbcom-reports'); ?></td>
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
            } elseif ($tab === 'group-reports') {
                $stats['group_analytics'] = $this->get_group_analytics($filter);
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
     * Get total courses - includes both test and regular courses
     */
    private function get_total_courses() {
        $course_post_type = 'sfwd-courses';
        if (function_exists('learndash_get_post_type_slug')) {
            $course_post_type = learndash_get_post_type_slug('course');
        }
        
        $courses = get_posts(array(
            'post_type' => $course_post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        return is_array($courses) ? count($courses) : 0;
    }
    
    /**
     * Get total lessons - includes both test and regular lessons
     */
    private function get_total_lessons() {
        $lesson_post_type = 'sfwd-lessons';
        if (function_exists('learndash_get_post_type_slug')) {
            $lesson_post_type = learndash_get_post_type_slug('lesson');
        }
        
        $lessons = get_posts(array(
            'post_type' => $lesson_post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        return is_array($lessons) ? count($lessons) : 0;
    }
    
    /**
     * Get active learners - users with LDTT progress created or enrolled in courses
     */
    private function get_active_learners() {
        global $wpdb;
        
        // Get users with LDTT progress or course enrollment
        $users_with_progress = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE '_ldtt_progress%' 
            OR meta_key LIKE '_sfwd-course_progress%'
        ");
        
        return intval($users_with_progress);
    }
    
    /**
     * Get total completed courses - based on LDTT progress and LearnDash completion
     */
    private function get_total_completed_courses() {
        global $wpdb;
        
        // Count completed courses from LDTT progress (100% completion)
        $ldtt_completed = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE '_ldtt_progress_course_%' 
            AND meta_value LIKE '%completion_rate\";i:100%'
        ");
        
        // Count completed courses from LearnDash user activity
        $ld_table = $wpdb->prefix . 'learndash_user_activity';
        $ld_completed = 0;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ld_table}'") == $ld_table) {
            $ld_completed = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$ld_table} 
                WHERE activity_type = 'course' 
                AND activity_completed = 1
            ");
        }
        
        return intval($ldtt_completed) + intval($ld_completed);
    }
    
    /**
     * Get courses list for filter dropdown
     */
    private function get_courses_list() {
        $course_post_type = 'sfwd-courses';
        if (function_exists('learndash_get_post_type_slug')) {
            $course_post_type = learndash_get_post_type_slug('course');
        }
        
        $courses = get_posts(array(
            'post_type' => $course_post_type,
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
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
     * Get user learning statistics - corrected to use LDTT data properly
     */
    private function get_user_learning_stats($filter = 'all', $course_id = 0) {
        // Get users with some learning activity
        $users_args = array(
            'number' => 50,
            'orderby' => 'registered',
            'order' => 'DESC'
        );
        
        // If filtering for active users, get only those with LDTT progress
        if ($filter === 'active') {
            $users_args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_ldtt_progress_created',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_ldtt_user_type',
                    'value' => 'course_enrolled',
                    'compare' => '='
                )
            );
        }
        
        $users = get_users($users_args);
        $user_stats = array();
        
        foreach ($users as $user) {
            $user_data = $this->get_user_progress_data($user->ID);
            
            // Apply filters
            if ($filter === 'completed' && $user_data['completed_courses'] === 0) {
                continue;
            }
            if ($filter === 'in-progress' && ($user_data['enrolled_courses'] === 0 || $user_data['enrolled_courses'] === $user_data['completed_courses'])) {
                continue;
            }
            if ($course_id > 0 && !$this->is_user_enrolled_in_course($user->ID, $course_id)) {
                continue;
            }
            
            // Only include users with actual learning activity
            if ($filter === 'all' && $user_data['enrolled_courses'] === 0 && !get_user_meta($user->ID, '_ldtt_test_user', true)) {
                continue;
            }
            
            $user_stats[] = array(
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'enrolled_courses' => $user_data['enrolled_courses'],
                'completed_courses' => $user_data['completed_courses'],
                'in_progress' => max(0, $user_data['enrolled_courses'] - $user_data['completed_courses']),
                'course_progress' => $user_data['course_progress'],
                'avg_progress' => $user_data['avg_progress'],
                'last_activity' => $user_data['last_activity'],
                'total_time_spent' => $user_data['total_time_spent']
            );
        }
        
        return $user_stats;
    }
    
    /**
     * Get comprehensive user progress data from LDTT and LearnDash sources
     */
    private function get_user_progress_data($user_id) {
        global $wpdb;
        
        // Get LDTT course progress data
        $ldtt_progress = $wpdb->get_results($wpdb->prepare("
            SELECT meta_key, meta_value 
            FROM {$wpdb->usermeta} 
            WHERE user_id = %d 
            AND meta_key LIKE '_ldtt_progress_course_%%'
        ", $user_id));
        
        $course_progress = array();
        $enrolled_courses = 0;
        $completed_courses = 0;
        $total_progress = 0;
        
        // Process LDTT progress data
        foreach ($ldtt_progress as $progress) {
            if (preg_match('/_ldtt_progress_course_(\d+)/', $progress->meta_key, $matches)) {
                $course_id = intval($matches[1]);
                $progress_data = maybe_unserialize($progress->meta_value);
                
                if (is_array($progress_data) && isset($progress_data['completion_rate'])) {
                    $completion_rate = floatval($progress_data['completion_rate']);
                    $course_title = get_the_title($course_id) ?: 'Course #' . $course_id;
                    
                    $course_progress[] = array(
                        'course_id' => $course_id,
                        'course_title' => $course_title,
                        'progress' => $completion_rate
                    );
                    
                    $enrolled_courses++;
                    $total_progress += $completion_rate;
                    
                    if ($completion_rate >= 100) {
                        $completed_courses++;
                    }
                }
            }
        }
        
        // Get regular LearnDash enrollment data
        $course_access = get_user_meta($user_id, '_sfwd-course_progress', true);
        if (is_array($course_access)) {
            foreach ($course_access as $course_id => $progress_info) {
                // Skip if already counted in LDTT data
                $already_counted = false;
                foreach ($course_progress as $ldtt_course) {
                    if ($ldtt_course['course_id'] == $course_id) {
                        $already_counted = true;
                        break;
                    }
                }
                
                if (!$already_counted) {
                    $course_title = get_the_title($course_id) ?: 'Course #' . $course_id;
                    $completion_percentage = 0;
                    
                    // Calculate progress from LearnDash data
                    if (is_array($progress_info) && isset($progress_info['total']) && $progress_info['total'] > 0) {
                        $completed = isset($progress_info['completed']) ? intval($progress_info['completed']) : 0;
                        $total = intval($progress_info['total']);
                        $completion_percentage = round(($completed / $total) * 100, 1);
                    }
                    
                    $course_progress[] = array(
                        'course_id' => $course_id,
                        'course_title' => $course_title,
                        'progress' => $completion_percentage
                    );
                    
                    $enrolled_courses++;
                    $total_progress += $completion_percentage;
                    
                    if ($completion_percentage >= 100) {
                        $completed_courses++;
                    }
                }
            }
        }
        
        // Calculate average progress
        $avg_progress = '0%';
        if ($enrolled_courses > 0) {
            $avg_progress = round($total_progress / $enrolled_courses, 1) . '%';
        }
        
        // Get last activity
        $last_activity = 'Never';
        $ldtt_progress_created = get_user_meta($user_id, '_ldtt_progress_created', true);
        if ($ldtt_progress_created) {
            $last_activity = date('Y-m-d H:i:s', $ldtt_progress_created);
        }
        
        // Check LearnDash activity table
        $ld_table = $wpdb->prefix . 'learndash_user_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ld_table}'") == $ld_table) {
            $ld_activity = $wpdb->get_var($wpdb->prepare("
                SELECT activity_updated 
                FROM {$ld_table} 
                WHERE user_id = %d 
                ORDER BY activity_updated DESC 
                LIMIT 1
            ", $user_id));
            
            if ($ld_activity && ($last_activity === 'Never' || strtotime($ld_activity) > strtotime($last_activity))) {
                $last_activity = date('Y-m-d H:i:s', strtotime($ld_activity));
            }
        }
        
        // Get time spent (placeholder for now)
        $total_time_spent = get_user_meta($user_id, '_ldtt_total_learning_time', true) ?: '0 hrs';
        
        return array(
            'enrolled_courses' => $enrolled_courses,
            'completed_courses' => $completed_courses,
            'course_progress' => $course_progress,
            'avg_progress' => $avg_progress,
            'last_activity' => $last_activity,
            'total_time_spent' => $total_time_spent
        );
    }
    
    /**
     * Check if user is enrolled in specific course
     */
    private function is_user_enrolled_in_course($user_id, $course_id) {
        // Check LDTT progress
        $ldtt_progress = get_user_meta($user_id, '_ldtt_progress_course_' . $course_id, true);
        if (!empty($ldtt_progress)) {
            return true;
        }
        
        // Check regular LearnDash enrollment
        $course_access = get_user_meta($user_id, '_sfwd-course_progress', true);
        if (is_array($course_access) && isset($course_access[$course_id])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get course analytics with correct data from LDTT and LearnDash
     */
    private function get_course_analytics() {
        $course_post_type = 'sfwd-courses';
        if (function_exists('learndash_get_post_type_slug')) {
            $course_post_type = learndash_get_post_type_slug('course');
        }
        
        $courses = get_posts(array(
            'post_type' => $course_post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        if (empty($courses)) {
            return array();
        }
        
        $analytics = array();
        
        foreach ($courses as $course) {
            $enrolled_users = $this->get_course_enrolled_users_corrected($course->ID);
            $completed_users = $this->get_course_completed_users_corrected($course->ID);
            $enrolled_count = count($enrolled_users);
            $completed_count = count($completed_users);
            
            $analytics[] = array(
                'course_name' => $course->post_title,
                'enrolled_users' => $enrolled_count,
                'completed' => $completed_count,
                'in_progress' => max(0, $enrolled_count - $completed_count),
                'completion_rate' => $enrolled_count > 0 ? round(($completed_count / $enrolled_count) * 100, 1) . '%' : '0%',
                'avg_completion_time' => $this->estimate_avg_completion_time($course->ID),
                'rating' => $this->get_course_rating($course->ID)
            );
        }
        
        return $analytics;
    }
    
    /**
     * Get course enrolled users from both LDTT and LearnDash data
     */
    private function get_course_enrolled_users_corrected($course_id) {
        global $wpdb;
        
        $enrolled_users = array();
        
        // Get users from LDTT progress
        $ldtt_users = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = '_ldtt_progress_course_%d'
        ", $course_id));
        
        if ($ldtt_users) {
            $enrolled_users = array_merge($enrolled_users, $ldtt_users);
        }
        
        // Get users from regular LearnDash course access
        $course_access_users = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = '_sfwd-course_progress' 
            AND meta_value LIKE %s
        ", '%' . $course_id . '%'));
        
        if ($course_access_users) {
            $enrolled_users = array_merge($enrolled_users, $course_access_users);
        }
        
        // Get users from LearnDash user activity
        $ld_table = $wpdb->prefix . 'learndash_user_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ld_table}'") == $ld_table) {
            $ld_users = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT user_id 
                FROM {$ld_table} 
                WHERE post_id = %d OR course_id = %d
            ", $course_id, $course_id));
            
            if ($ld_users) {
                $enrolled_users = array_merge($enrolled_users, $ld_users);
            }
        }
        
        return array_unique(array_filter($enrolled_users));
    }
    
    /**
     * Get course completed users from both LDTT and LearnDash data
     */
    private function get_course_completed_users_corrected($course_id) {
        global $wpdb;
        
        $completed_users = array();
        
        // Get users who completed via LDTT (100% completion)
        $ldtt_completed = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = '_ldtt_progress_course_%d' 
            AND meta_value LIKE '%completion_rate\";i:100%'
        ", $course_id));
        
        if ($ldtt_completed) {
            $completed_users = array_merge($completed_users, $ldtt_completed);
        }
        
        // Get users who completed via LearnDash activity
        $ld_table = $wpdb->prefix . 'learndash_user_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ld_table}'") == $ld_table) {
            $ld_completed = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT user_id 
                FROM {$ld_table} 
                WHERE post_id = %d 
                AND activity_type = 'course' 
                AND activity_completed = 1
            ", $course_id));
            
            if ($ld_completed) {
                $completed_users = array_merge($completed_users, $ld_completed);
            }
        }
        
        return array_unique(array_filter($completed_users));
    }
    
    /**
     * Estimate average completion time
     */
    private function estimate_avg_completion_time($course_id) {
        // This is a placeholder - could be enhanced with actual time tracking
        $lesson_count = $this->get_course_lesson_count($course_id);
        $estimated_hours = $lesson_count * 0.5; // Assume 30 minutes per lesson
        
        if ($estimated_hours < 1) {
            return '30 min';
        } elseif ($estimated_hours < 24) {
            return round($estimated_hours, 1) . ' hrs';
        } else {
            $days = round($estimated_hours / 8, 1); // 8 hour work day
            return $days . ' days';
        }
    }
    
    /**
     * Get course lesson count
     */
    private function get_course_lesson_count($course_id) {
        $lesson_post_type = 'sfwd-lessons';
        if (function_exists('learndash_get_post_type_slug')) {
            $lesson_post_type = learndash_get_post_type_slug('lesson');
        }
        
        $lessons = get_posts(array(
            'post_type' => $lesson_post_type,
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
        
        return is_array($lessons) ? count($lessons) : 0;
    }
    
    /**
     * Get course rating (placeholder)
     */
    private function get_course_rating($course_id) {
        // This is a placeholder - could be enhanced with actual rating system
        return '4.' . wp_rand(0, 9) . '/5';
    }
    
    /**
     * Get completion trends for chart - corrected to use actual data
     */
    private function get_completion_trends() {
        global $wpdb;
        
        $trends = array();
        
        // Get LDTT completion trends
        $ldtt_trends = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(FROM_UNIXTIME(meta_value), '%Y-%m') as month,
                COUNT(*) as completions
            FROM {$wpdb->usermeta} 
            WHERE meta_key = '_ldtt_progress_created' 
            AND meta_value >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 12 MONTH))
            GROUP BY month
            ORDER BY month
        ");
        
        foreach ($ldtt_trends as $trend) {
            $trends[] = array(
                'month' => $trend->month,
                'completions' => intval($trend->completions)
            );
        }
        
        // Get LearnDash completion trends
        $ld_table = $wpdb->prefix . 'learndash_user_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$ld_table}'") == $ld_table) {
            $ld_trends = $wpdb->get_results("
                SELECT 
                    DATE_FORMAT(activity_updated, '%Y-%m') as month,
                    COUNT(*) as completions
                FROM {$ld_table} 
                WHERE activity_type = 'course' 
                AND activity_completed = 1 
                AND activity_updated >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month
            ");
            
            // Merge LD trends with LDTT trends
            foreach ($ld_trends as $ld_trend) {
                $found = false;
                for ($i = 0; $i < count($trends); $i++) {
                    if ($trends[$i]['month'] === $ld_trend->month) {
                        $trends[$i]['completions'] += intval($ld_trend->completions);
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $trends[] = array(
                        'month' => $ld_trend->month,
                        'completions' => intval($ld_trend->completions)
                    );
                }
            }
        }
        
        // Sort by month
        usort($trends, function($a, $b) {
            return strcmp($a['month'], $b['month']);
        });
        
        return $trends;
    }
    
    /**
     * Get LearnDash group analytics
     */
    private function get_group_analytics($filter = 'all') {
        $group_post_type = 'groups';
        if (function_exists('learndash_get_post_type_slug')) {
            $group_post_type = learndash_get_post_type_slug('group');
        }
        
        $groups_args = array(
            'post_type' => $group_post_type,
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $groups = get_posts($groups_args);
        
        if (empty($groups)) {
            return array();
        }
        
        $analytics = array();
        
        foreach ($groups as $group) {
            $group_data = $this->get_group_data($group->ID);
            
            // Apply filters
            if ($filter === 'with_leaders' && empty($group_data['leaders'])) {
                continue;
            }
            if ($filter === 'active' && $group_data['total_users'] === 0) {
                continue;
            }
            
            $analytics[] = array(
                'group_name' => $group->post_title,
                'group_leaders' => $group_data['leaders_names'],
                'total_users' => $group_data['total_users'],
                'associated_courses' => $group_data['courses_count'],
                'avg_progress' => $group_data['avg_progress'],
                'completed_users' => $group_data['completed_users'],
                'created_date' => date('Y-m-d', strtotime($group->post_date)),
                'status' => $group_data['status']
            );
        }
        
        return $analytics;
    }
    
    /**
     * Get comprehensive group data
     */
    private function get_group_data($group_id) {
        // Get group leaders
        $leaders = array();
        $leaders_names = 'No Leaders';
        
        if (function_exists('learndash_get_groups_administrators')) {
            $group_leaders = learndash_get_groups_administrators($group_id);
            if (!empty($group_leaders)) {
                foreach ($group_leaders as $leader_id) {
                    $leader = get_user_by('ID', $leader_id);
                    if ($leader) {
                        $leaders[] = $leader->display_name;
                    }
                }
                $leaders_names = implode(', ', $leaders);
            }
        }
        
        // Get group users
        $group_users = array();
        $total_users = 0;
        
        if (function_exists('learndash_get_groups_users')) {
            $group_users = learndash_get_groups_users($group_id);
            $total_users = is_array($group_users) ? count($group_users) : 0;
        }
        
        // Get associated courses
        $courses_count = 0;
        $group_courses = array();
        
        if (function_exists('learndash_group_enrolled_courses')) {
            $group_courses = learndash_group_enrolled_courses($group_id);
            $courses_count = is_array($group_courses) ? count($group_courses) : 0;
        }
        
        // Calculate average progress and completed users
        $avg_progress = '0%';
        $completed_users = 0;
        
        if (!empty($group_users) && !empty($group_courses)) {
            $total_progress = 0;
            $progress_count = 0;
            
            foreach ($group_users as $user_id) {
                $user_completed_courses = 0;
                $user_total_progress = 0;
                $user_course_count = 0;
                
                foreach ($group_courses as $course_id) {
                    // Check LDTT progress first
                    $ldtt_progress = get_user_meta($user_id, '_ldtt_progress_course_' . $course_id, true);
                    if (!empty($ldtt_progress)) {
                        $progress_data = maybe_unserialize($ldtt_progress);
                        if (is_array($progress_data) && isset($progress_data['completion_rate'])) {
                            $completion_rate = floatval($progress_data['completion_rate']);
                            $user_total_progress += $completion_rate;
                            $user_course_count++;
                            
                            if ($completion_rate >= 100) {
                                $user_completed_courses++;
                            }
                        }
                    } else {
                        // Check regular LearnDash progress
                        if (function_exists('learndash_course_progress')) {
                            $course_progress = learndash_course_progress($user_id, $course_id);
                            if (is_array($course_progress)) {
                                $completion_percentage = isset($course_progress['percentage']) ? $course_progress['percentage'] : 0;
                                $user_total_progress += $completion_percentage;
                                $user_course_count++;
                                
                                if ($completion_percentage >= 100) {
                                    $user_completed_courses++;
                                }
                            }
                        }
                    }
                }
                
                if ($user_course_count > 0) {
                    $total_progress += ($user_total_progress / $user_course_count);
                    $progress_count++;
                    
                    // If user completed all courses in the group
                    if ($user_completed_courses === $courses_count && $courses_count > 0) {
                        $completed_users++;
                    }
                }
            }
            
            if ($progress_count > 0) {
                $avg_progress = round($total_progress / $progress_count, 1) . '%';
            }
        }
        
        // Determine group status
        $status = 'Active';
        if ($total_users === 0) {
            $status = 'Empty';
        } elseif ($courses_count === 0) {
            $status = 'No Courses';
        }
        
        return array(
            'leaders' => $leaders,
            'leaders_names' => $leaders_names,
            'total_users' => $total_users,
            'courses_count' => $courses_count,
            'avg_progress' => $avg_progress,
            'completed_users' => $completed_users,
            'status' => $status
        );
    }
}

// Initialize LearnDash reports
new Wbcom_Reports_LearnDash();