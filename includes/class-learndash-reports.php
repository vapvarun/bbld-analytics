<?php
/**
 * Enhanced LearnDash Reports Class with Full Indexing and Caching Support
 * 
 * @package Wbcom_Reports
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Wbcom_Reports_LearnDash {
    
    private $index;
    private $cache_group = 'wbcom_reports_ld';
    
    public function __construct() {
        // Initialize indexing system
        $this->index = new Wbcom_Reports_Index();
        
        add_action('wp_ajax_get_learndash_stats', array($this, 'ajax_get_learndash_stats'));
        add_action('wp_ajax_get_course_analytics', array($this, 'ajax_get_course_analytics'));
        add_action('wp_ajax_export_learndash_stats', array($this, 'ajax_export_learndash_stats'));
        add_action('wp_ajax_rebuild_ld_index', array($this, 'ajax_rebuild_index'));
        
        // Hook into LearnDash completion events for real-time indexing
        add_action('learndash_course_completed', array($this, 'update_course_completion_index'), 10, 1);
        add_action('learndash_lesson_completed', array($this, 'update_lesson_completion_index'), 10, 1);
        add_action('learndash_topic_completed', array($this, 'update_topic_completion_index'), 10, 1);
        add_action('learndash_quiz_completed', array($this, 'update_quiz_completion_index'), 10, 2);
        add_action('ld_course_access_granted', array($this, 'update_enrollment_index'), 10, 2);
        add_action('ld_course_access_removed', array($this, 'update_enrollment_index'), 10, 2);
    }
    
    /**
     * Render LearnDash reports page with index status
     */
    public static function render_page() {
        $index = new Wbcom_Reports_Index();
        $index_stats = $index->get_index_stats();
        $needs_rebuilding = $index->needs_rebuilding();
        $cache_stats = $index->get_cache_stats();
        ?>
        <div class="wrap wbcom-reports-wrap">
            <h1><?php _e('LearnDash Learning Reports', 'wbcom-reports'); ?></h1>
            
            <?php if (!Wbcom_Reports_Helpers::is_learndash_active()): ?>
                <div class="notice notice-warning">
                    <p><?php _e('LearnDash plugin is not active. Please activate LearnDash to view learning reports.', 'wbcom-reports'); ?></p>
                </div>
            <?php else: ?>
                
                <!-- Index Status -->
                <div class="wbcom-index-status">
                    <div class="index-info">
                        <div class="index-details">
                            <p>
                                <strong><?php _e('Learning Index Status:', 'wbcom-reports'); ?></strong>
                                <?php echo sprintf(__('%d of %d users indexed (%s%% coverage)', 'wbcom-reports'), 
                                    $index_stats['total_indexed'], 
                                    $index_stats['total_users'], 
                                    $index_stats['coverage_percentage']
                                ); ?>
                                <?php if ($index_stats['last_update']): ?>
                                    | <?php echo sprintf(__('Last updated: %s ago', 'wbcom-reports'), 
                                        human_time_diff(strtotime($index_stats['last_update']))
                                    ); ?>
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong><?php _e('Cache Status:', 'wbcom-reports'); ?></strong>
                                <?php if ($cache_stats['cache_enabled']): ?>
                                    <span class="cache-enabled"><?php _e('Object Cache Active', 'wbcom-reports'); ?></span>
                                <?php else: ?>
                                    <span class="cache-disabled"><?php _e('Using Database Cache', 'wbcom-reports'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($needs_rebuilding): ?>
                            <div class="notice notice-warning inline">
                                <p><?php _e('Learning index needs rebuilding for optimal performance.', 'wbcom-reports'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
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
                        <button id="rebuild-ld-index" class="button <?php echo $needs_rebuilding ? 'needs-rebuild' : ''; ?>" 
                                title="<?php _e('Rebuild learning index for optimal performance', 'wbcom-reports'); ?>">
                            <?php _e('Rebuild Index', 'wbcom-reports'); ?>
                            <?php if ($needs_rebuilding): ?>
                                <span class="dashicons dashicons-warning" style="color: #ff6b6b; margin-left: 5px;"></span>
                            <?php endif; ?>
                        </button>
                    </div>
                    
                    <div class="wbcom-learning-tabs">
                        <div class="wbcom-tab-nav">
                            <button class="tab-button active" data-tab="user-progress"><?php _e('User Progress', 'wbcom-reports'); ?></button>
                            <button class="tab-button" data-tab="course-analytics"><?php _e('Course Analytics', 'wbcom-reports'); ?></button>
                            <button class="tab-button" data-tab="group-reports"><?php _e('Group Reports', 'wbcom-reports'); ?></button>
                        </div>
                        
                        <div id="user-progress" class="tab-content active">
                            <div class="wbcom-user-stats">
                                <h2><?php _e('User Learning Progress', 'wbcom-reports'); ?></h2>
                                
                                <!-- Search Controls -->
                                <div class="search-controls">
                                    <div class="search-input-wrapper">
                                        <input type="text" id="user-search" placeholder="<?php _e('Search learners by name or username...', 'wbcom-reports'); ?>" class="regular-text">
                                        <button type="button" id="clear-search" class="search-clear" title="<?php _e('Clear search', 'wbcom-reports'); ?>">&times;</button>
                                    </div>
                                    <button id="apply-learning-filters" class="button"><?php _e('Apply Filters', 'wbcom-reports'); ?></button>
                                </div>
                                
                                <!-- Search Results Info -->
                                <div id="search-info" style="display: none;"></div>
                                
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
                                </div>
                                
                                <table id="user-learning-stats-table" class="widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th class="sortable-header" data-sort="display_name"><?php _e('Display Name', 'wbcom-reports'); ?></th>
                                            <th class="sortable-header" data-sort="user_login"><?php _e('Username', 'wbcom-reports'); ?></th>
                                            <th class="sortable-header" data-sort="enrolled_courses"><?php _e('Enrolled Courses', 'wbcom-reports'); ?></th>
                                            <th class="sortable-header" data-sort="completed_courses"><?php _e('Completed Courses', 'wbcom-reports'); ?></th>
                                            <th class="sortable-header" data-sort="in_progress"><?php _e('In Progress', 'wbcom-reports'); ?></th>
                                            <th><?php _e('Avg. Progress', 'wbcom-reports'); ?></th>
                                            <th class="sortable-header" data-sort="last_activity"><?php _e('Last Activity', 'wbcom-reports'); ?></th>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5"><?php _e('Loading...', 'wbcom-reports'); ?></td>
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
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
        
        <style>
        .wbcom-index-status {
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .index-details p {
            margin: 5px 0;
        }
        .cache-enabled {
            color: #46b450;
            font-weight: bold;
        }
        .cache-disabled {
            color: #ffb900;
            font-weight: bold;
        }
        .needs-rebuild {
            background-color: #ff6b6b !important;
            color: white !important;
        }
        .needs-rebuild:hover {
            background-color: #ff5252 !important;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for LearnDash stats using indexed data
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
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'enrolled_courses';
            $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'desc';
            
            $stats = array(
                'total_courses' => $this->get_cached_total_courses(),
                'total_lessons' => $this->get_cached_total_lessons(),
                'active_learners' => $this->get_cached_active_learners(),
                'completed_courses' => $this->get_cached_total_completed_courses(),
                'courses_list' => $this->get_cached_courses_list()
            );
            
            if ($tab === 'user-progress') {
                // Use indexed data for user progress
                $indexed_users = $this->get_indexed_learning_users($filter, $course_id, $search, $sort_by, $sort_order);
                $stats['user_stats'] = $indexed_users['users'];
                $stats['search_info'] = array(
                    'active_search' => !empty($search) || $filter !== 'all',
                    'search_term' => $search,
                    'filtered_count' => $indexed_users['total_count'],
                    'total_count' => $indexed_users['total_count']
                );
                $stats['using_index'] = true;
            } elseif ($tab === 'course-analytics') {
                $stats['course_analytics'] = $this->get_cached_course_analytics();
            } elseif ($tab === 'group-reports') {
                $stats['group_analytics'] = $this->get_cached_group_analytics($filter);
            }
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            error_log('LearnDash Stats Error: ' . $e->getMessage());
            wp_send_json_error('Error loading stats: ' . $e->getMessage());
        }
    }
    
    /**
     * Get indexed learning users with enhanced data
     */
    private function get_indexed_learning_users($filter = 'all', $course_id = 0, $search = '', $sort_by = 'enrolled_courses', $sort_order = 'desc') {
        // Build filter for learning users
        $learning_filter = $filter;
        if ($filter === 'active') {
            $learning_filter = 'all'; // We'll filter by enrolled_courses > 0
        } elseif ($filter === 'completed') {
            $learning_filter = 'all'; // We'll filter by completed_courses > 0
        } elseif ($filter === 'in-progress') {
            $learning_filter = 'all'; // We'll filter by in_progress_courses > 0
        }
        
        $indexed_users = $this->index->get_indexed_users(array(
            'page' => 1,
            'per_page' => 50,
            'search' => $search,
            'filter' => $learning_filter,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ));
        
        // Transform and filter data
        $user_stats = array();
        foreach ($indexed_users['users'] as $user) {
            // Apply learning-specific filters
            if ($filter === 'active' && $user['enrolled_courses'] == 0) {
                continue;
            }
            if ($filter === 'completed' && $user['completed_courses'] == 0) {
                continue;
            }
            if ($filter === 'in-progress' && $user['in_progress_courses'] == 0) {
                continue;
            }
            
            // Apply course filter
            if ($course_id > 0 && !$this->is_user_enrolled_in_course($user['user_id'], $course_id)) {
                continue;
            }
            
            // Skip users with no learning activity unless they are test users
            if ($filter === 'all' && $user['enrolled_courses'] == 0 && !$user['is_test_user']) {
                continue;
            }
            
            $user_stats[] = array(
                'user_id' => $user['user_id'],
                'display_name' => $user['display_name'],
                'user_login' => $user['user_login'],
                'enrolled_courses' => $user['enrolled_courses'],
                'completed_courses' => $user['completed_courses'],
                'in_progress' => $user['in_progress_courses'],
                'avg_progress' => $user['avg_progress'] . '%',
                'last_activity' => $user['last_activity'] ?: 'Never'
            );
        }
        
        return array(
            'users' => $user_stats,
            'total_count' => count($user_stats)
        );
    }
    
    /**
     * Get cached total courses
     */
    private function get_cached_total_courses() {
        $cache_key = 'total_courses';
        $total = wp_cache_get($cache_key, $this->cache_group);
        
        if ($total === false) {
            $course_post_type = 'sfwd-courses';
            if (function_exists('learndash_get_post_type_slug')) {
                $course_post_type = learndash_get_post_type_slug('course');
            }
            
            $courses = get_posts(array(
                'post_type' => $course_post_type,
                'numberposts' => -1,
                'post_status' => 'publish'
            ));
            $total = is_array($courses) ? count($courses) : 0;
            wp_cache_set($cache_key, $total, $this->cache_group, 3600);
        }
        
        return $total;
    }
    
    /**
     * Get cached total lessons
     */
    private function get_cached_total_lessons() {
        $cache_key = 'total_lessons';
        $total = wp_cache_get($cache_key, $this->cache_group);
        
        if ($total === false) {
            $lesson_post_type = 'sfwd-lessons';
            if (function_exists('learndash_get_post_type_slug')) {
                $lesson_post_type = learndash_get_post_type_slug('lesson');
            }
            
            $lessons = get_posts(array(
                'post_type' => $lesson_post_type,
                'numberposts' => -1,
                'post_status' => 'publish'
            ));
            $total = is_array($lessons) ? count($lessons) : 0;
            wp_cache_set($cache_key, $total, $this->cache_group, 3600);
        }
        
        return $total;
    }
    
    /**
     * Get cached active learners
     */
    private function get_cached_active_learners() {
        $cache_key = 'active_learners';
        $total = wp_cache_get($cache_key, $this->cache_group);
        
        if ($total === false) {
            global $wpdb;
            $index_table = $wpdb->prefix . 'wbcom_reports_index';
            
            $total = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$index_table} 
                WHERE enrolled_courses > 0
            ");
            
            wp_cache_set($cache_key, intval($total), $this->cache_group, 1800);
        }
        
        return $total;
    }
    
    /**
     * Get cached total completed courses
     */
    private function get_cached_total_completed_courses() {
        $cache_key = 'total_completed_courses';
        $total = wp_cache_get($cache_key, $this->cache_group);
        
        if ($total === false) {
            global $wpdb;
            $index_table = $wpdb->prefix . 'wbcom_reports_index';
            
            $total = $wpdb->get_var("
                SELECT SUM(completed_courses) 
                FROM {$index_table}
            ");
            
            wp_cache_set($cache_key, intval($total), $this->cache_group, 1800);
        }
        
        return $total;
    }
    
    /**
     * Get cached courses list
     */
    private function get_cached_courses_list() {
        $cache_key = 'courses_list';
        $courses_list = wp_cache_get($cache_key, $this->cache_group);
        
        if ($courses_list === false) {
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
            
            wp_cache_set($cache_key, $courses_list, $this->cache_group, 3600);
        }
        
        return $courses_list;
    }
    
    /**
     * Get cached course analytics
     */
    private function get_cached_course_analytics() {
        $cache_key = 'course_analytics';
        $analytics = wp_cache_get($cache_key, $this->cache_group);
        
        if ($analytics === false) {
            $analytics = $this->calculate_course_analytics();
            wp_cache_set($cache_key, $analytics, $this->cache_group, 1800);
        }
        
        return $analytics;
    }
    
    /**
     * Get cached group analytics
     */
    private function get_cached_group_analytics($filter) {
        $cache_key = 'group_analytics_' . $filter;
        $analytics = wp_cache_get($cache_key, $this->cache_group);
        
        if ($analytics === false) {
            $analytics = $this->calculate_group_analytics($filter);
            wp_cache_set($cache_key, $analytics, $this->cache_group, 1800);
        }
        
        return $analytics;
    }
    
    /**
     * Calculate course analytics using indexed data
     */
    private function calculate_course_analytics() {
        global $wpdb;
        $index_table = $wpdb->prefix . 'wbcom_reports_index';
        
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
            $enrolled_users = $this->get_course_enrolled_users_from_index($course->ID);
            $completed_users = $this->get_course_completed_users_from_index($course->ID);
            $enrolled_count = count($enrolled_users);
            $completed_count = count($completed_users);
            
            $analytics[] = array(
                'course_name' => $course->post_title,
                'enrolled_users' => $enrolled_count,
                'completed' => $completed_count,
                'in_progress' => max(0, $enrolled_count - $completed_count),
                'completion_rate' => $enrolled_count > 0 ? round(($completed_count / $enrolled_count) * 100, 1) . '%' : '0%'
            );
        }
        
        return $analytics;
    }
    
    /**
     * Calculate group analytics using indexed data
     */
    private function calculate_group_analytics($filter) {
        // Implementation for group analytics using indexed data
        // This would be similar to the original but using the index table for better performance
        return array(); // Placeholder - implement based on your group requirements
    }
    
    /**
     * Get course enrolled users from index
     */
    private function get_course_enrolled_users_from_index($course_id) {
        // This would query the index table for users with this course
        // Implementation depends on how you want to store course enrollment in the index
        return array(); // Placeholder
    }
    
    /**
     * Get course completed users from index
     */
    private function get_course_completed_users_from_index($course_id) {
        // This would query the index table for users who completed this course
        // Implementation depends on how you want to store course completion in the index
        return array(); // Placeholder
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
     * AJAX handler for exporting LearnDash stats
     */
    public function ajax_export_learndash_stats() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'wbcom_reports_nonce')) {
            wp_die('Security check failed');
        }
        
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'user-progress';
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'enrolled_courses';
        $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'desc';
        
        $export_data = array();
        
        if ($tab === 'user-progress') {
            $users_data = $this->get_indexed_learning_users($filter, $course_id, $search, $sort_by, $sort_order);
            
            foreach ($users_data['users'] as $user) {
                $export_data[] = array(
                    'Display Name' => $user['display_name'],
                    'Username' => $user['user_login'],
                    'Enrolled Courses' => $user['enrolled_courses'],
                    'Completed Courses' => $user['completed_courses'],
                    'In Progress' => $user['in_progress'],
                    'Average Progress' => $user['avg_progress'],
                    'Last Activity' => $user['last_activity']
                );
            }
            $filename = 'learndash-user-progress-' . date('Y-m-d-H-i-s') . '.csv';
            
        } elseif ($tab === 'course-analytics') {
            $courses = $this->get_cached_course_analytics();
            
            foreach ($courses as $course) {
                $export_data[] = array(
                    'Course Name' => $course['course_name'],
                    'Enrolled Users' => $course['enrolled_users'],
                    'Completed' => $course['completed'],
                    'In Progress' => $course['in_progress'],
                    'Completion Rate' => $course['completion_rate']
                );
            }
            $filename = 'learndash-course-analytics-' . date('Y-m-d-H-i-s') . '.csv';
            
        } elseif ($tab === 'group-reports') {
            $groups = $this->get_cached_group_analytics($filter);
            
            foreach ($groups as $group) {
                $export_data[] = array(
                    'Group Name' => $group['group_name'],
                    'Group Leaders' => $group['group_leaders'],
                    'Total Users' => $group['total_users'],
                    'Associated Courses' => $group['associated_courses'],
                    'Average Progress' => $group['avg_progress'],
                    'Completed Users' => $group['completed_users'],
                    'Created Date' => $group['created_date'],
                    'Status' => $group['status']
                );
            }
            $filename = 'learndash-group-analytics-' . date('Y-m-d-H-i-s') . '.csv';
        }
        
        Wbcom_Reports_Helpers::export_to_csv($export_data, $filename);
    }
    
    /**
     * AJAX handler for rebuilding LearnDash index
     */
    public function ajax_rebuild_index() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'wbcom_reports_nonce')) {
            wp_die('Security check failed');
        }
        
        // Increase time limit for large sites
        if (function_exists('set_time_limit')) {
            set_time_limit(300); // 5 minutes
        }
        
        $indexed_count = $this->index->rebuild_user_index();
        
        // Clear LearnDash specific caches
        wp_cache_flush_group($this->cache_group);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully rebuilt learning index for %d users.', 'wbcom-reports'), $indexed_count),
            'indexed_count' => $indexed_count
        ));
    }
    
    /**
     * Update index when course is completed
     */
    public function update_course_completion_index($data) {
        if (isset($data['user']) && isset($data['user']->ID)) {
            $this->index->index_user($data['user']->ID);
            wp_cache_delete('total_completed_courses', $this->cache_group);
            wp_cache_delete('course_analytics', $this->cache_group);
        }
    }
    
    /**
     * Update index when lesson is completed
     */
    public function update_lesson_completion_index($data) {
        if (isset($data['user']) && isset($data['user']->ID)) {
            $this->index->index_user($data['user']->ID);
        }
    }
    
    /**
     * Update index when topic is completed
     */
    public function update_topic_completion_index($data) {
        if (isset($data['user']) && isset($data['user']->ID)) {
            $this->index->index_user($data['user']->ID);
        }
    }
    
    /**
     * Update index when quiz is completed
     */
    public function update_quiz_completion_index($data, $user) {
        if ($user && isset($user->ID)) {
            $this->index->index_user($user->ID);
        }
    }
    
    /**
     * Update index when course enrollment changes
     */
    public function update_enrollment_index($user_id, $course_id) {
        $this->index->index_user($user_id);
        wp_cache_delete('active_learners', $this->cache_group);
        wp_cache_delete('course_analytics', $this->cache_group);
    }
}

// Initialize LearnDash reports
new Wbcom_Reports_LearnDash();