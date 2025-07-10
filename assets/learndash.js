/**
 * Updated LearnDash Reports JavaScript - Removed fake metrics (Avg Time & Rating)
 * 
 * @package Wbcom_Reports
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // State management
    let currentTab = 'user-progress';
    let currentFilter = 'all';
    let currentCourseFilter = 'all';
    let completionChart = null;
    
    // Initialize LearnDash reports
    initLearnDashReports();
    
    function initLearnDashReports() {
        loadLearnDashStats();
        bindEvents();
        initTabs();
    }
    
    function bindEvents() {
        // Refresh stats button
        $('#refresh-learndash-stats').on('click', function() {
            $(this).prop('disabled', true).text('Refreshing...');
            loadLearnDashStats();
        });
        
        // Export CSV button
        $('#export-learndash-stats').on('click', function() {
            exportLearnDashData();
        });
        
        // Tab switching
        $('.tab-button').on('click', function() {
            const tabId = $(this).data('tab');
            switchTab(tabId);
        });
        
        // Apply learning filters
        $('#apply-learning-filters').on('click', function() {
            applyLearningFilters();
        });
        
        // Filter change events
        $('#progress-filter').on('change', function() {
            currentFilter = $(this).val();
        });
        
        $('#course-filter').on('change', function() {
            currentCourseFilter = $(this).val();
        });
        
        $('#group-filter').on('change', function() {
            currentFilter = $(this).val();
        });
        
        // Apply group filters
        $('#apply-group-filters').on('click', function() {
            applyGroupFilters();
        });
    }
    
    function initTabs() {
        // Set initial active tab
        switchTab('user-progress');
    }
    
    function switchTab(tabId) {
        // Update tab buttons
        $('.tab-button').removeClass('active');
        $(`.tab-button[data-tab="${tabId}"]`).addClass('active');
        
        // Update tab content
        $('.tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');
        
        // Update current tab
        currentTab = tabId;
        
        // Load data for the new tab
        loadLearnDashStats();
    }
    
    function loadLearnDashStats() {
        showLoadingState();
        
        $.ajax({
            url: wbcomReports.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_learndash_stats',
                nonce: wbcomReports.nonce,
                tab: currentTab,
                filter: currentFilter,
                course_id: currentCourseFilter
            },
            success: function(response) {
                if (response.success) {
                    updateLearnDashDisplay(response.data);
                } else {
                    // Handle LearnDash not active error gracefully
                    if (response.data && response.data.includes('LearnDash not active')) {
                        showLearnDashNotActiveMessage();
                    } else {
                        showErrorMessage('Failed to load LearnDash stats: ' + (response.data || 'Unknown error'));
                    }
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage('Error loading LearnDash stats: ' + error);
            },
            complete: function() {
                hideLoadingState();
            }
        });
    }
    
    function updateLearnDashDisplay(data) {
        // Update stat boxes with null safety
        updateStatBox('#total-courses', data.total_courses || 0);
        updateStatBox('#total-lessons', data.total_lessons || 0);
        updateStatBox('#active-learners', data.active_learners || 0);
        updateStatBox('#completed-courses', data.completed_courses || 0);
        
        // Update course filter dropdown
        updateCourseFilter(data.courses_list || []);
        
        // Update content based on active tab
        switch (currentTab) {
            case 'user-progress':
                updateUserProgressTable(data.user_stats || []);
                break;
            case 'course-analytics':
                updateCourseAnalytics(data.course_analytics || []);
                break;
            case 'group-reports':
                updateGroupAnalytics(data.group_analytics || []);
                break;
        }
    }
    
    function updateCourseFilter(coursesList) {
        const $courseFilter = $('#course-filter');
        const currentValue = $courseFilter.val();
        
        $courseFilter.empty().append('<option value="all">All Courses</option>');
        
        if (coursesList && coursesList.length > 0) {
            coursesList.forEach(function(course) {
                $courseFilter.append(`<option value="${course.id}">${escapeHtml(course.title)}</option>`);
            });
        }
        
        // Restore previous selection if it still exists
        if (currentValue && $courseFilter.find(`option[value="${currentValue}"]`).length) {
            $courseFilter.val(currentValue);
        }
    }
    
    function updateUserProgressTable(userStats) {
        const $tableBody = $('#user-learning-stats-table tbody');
        $tableBody.empty();
        
        if (userStats && userStats.length > 0) {
            userStats.forEach(function(user, index) {
                const progressClass = getProgressClass(user.avg_progress);
                const progressDetail = formatCourseProgress(user.course_progress);
                
                const row = `
                    <tr class="fade-in" style="animation-delay: ${index * 0.1}s">
                        <td>
                            <strong>${escapeHtml(user.display_name)}</strong>
                            <br><small class="text-muted">${escapeHtml(user.user_login)}</small>
                        </td>
                        <td><code>${escapeHtml(user.user_login)}</code></td>
                        <td>
                            <span class="course-count font-bold">
                                ${formatNumber(user.enrolled_courses)}
                            </span>
                        </td>
                        <td>
                            <span class="completion-count text-success font-bold">
                                ${formatNumber(user.completed_courses)}
                            </span>
                        </td>
                        <td>
                            <span class="progress-count text-warning">
                                ${formatNumber(user.in_progress)}
                            </span>
                        </td>
                        <td>
                            <div class="course-progress-container">
                                <div class="avg-progress">
                                    <span class="progress-percentage ${progressClass}">
                                        Avg: ${user.avg_progress}
                                    </span>
                                    <div class="progress-bar">
                                        <div class="progress-fill ${progressClass}" 
                                             style="width: ${user.avg_progress}">
                                        </div>
                                    </div>
                                </div>
                                <div class="course-details">
                                    ${progressDetail}
                                </div>
                            </div>
                        </td>
                        <td>
                            <small class="${getActivityClass(user.last_activity)}">
                                ${formatDate(user.last_activity)}
                            </small>
                        </td>
                    </tr>
                `;
                $tableBody.append(row);
            });
            
        } else {
            $tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="no-data-message">
                            <p><em>No learning data found with the current filters.</em></p>
                            <p><small>Users need to be enrolled in courses and have progress data.</small></p>
                            <p><small>Try switching to "All Learners" filter or create test data using LearnDash Testing Toolkit.</small></p>
                        </div>
                    </td>
                </tr>
            `);
        }
    }
    
    function formatCourseProgress(courseProgress) {
        if (!courseProgress || !Array.isArray(courseProgress) || courseProgress.length === 0) {
            return '<small class="text-muted">No progress data</small>';
        }
        
        let progressHtml = '<div class="course-progress-list">';
        
        // Show first 3 courses, then summarize if more
        const showCount = Math.min(3, courseProgress.length);
        
        for (let i = 0; i < showCount; i++) {
            const course = courseProgress[i];
            const progressClass = getProgressClass(course.progress + '%');
            
            progressHtml += `
                <div class="course-progress-item">
                    <small class="course-name">${escapeHtml(course.course_title)}</small>
                    <span class="progress-badge ${progressClass}">${course.progress}%</span>
                </div>
            `;
        }
        
        if (courseProgress.length > 3) {
            progressHtml += `<small class="text-muted">+${courseProgress.length - 3} more courses</small>`;
        }
        
        progressHtml += '</div>';
        
        return progressHtml;
    }
    
    function updateCourseAnalytics(courseAnalytics) {
        // Update course analytics table
        const $tableBody = $('#course-analytics-table tbody');
        $tableBody.empty();
        
        if (courseAnalytics && courseAnalytics.length > 0) {
            courseAnalytics.forEach(function(course, index) {
                const row = `
                    <tr class="fade-in" style="animation-delay: ${index * 0.1}s">
                        <td>
                            <strong>${escapeHtml(course.course_name)}</strong>
                        </td>
                        <td>${formatNumber(course.enrolled_users)}</td>
                        <td>
                            <span class="text-success font-bold">
                                ${formatNumber(course.completed)}
                            </span>
                        </td>
                        <td>
                            <span class="text-warning">
                                ${formatNumber(course.in_progress)}
                            </span>
                        </td>
                        <td>
                            <span class="${getProgressClass(course.completion_rate)}">
                                ${course.completion_rate}
                            </span>
                        </td>
                    </tr>
                `;
                $tableBody.append(row);
            });
            
            // Create course completion chart
            createCourseCompletionChart(courseAnalytics);
            
        } else {
            $tableBody.append(`
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="no-data-message">
                            <p><em>No course analytics data available.</em></p>
                            <p><small>Make sure LearnDash courses exist and users are enrolled.</small></p>
                            <p><small>Create test data using LearnDash Testing Toolkit for demo purposes.</small></p>
                        </div>
                    </td>
                </tr>
            `);
        }
    }
    
    function updateGroupAnalytics(groupAnalytics) {
        // Update group analytics table
        const $tableBody = $('#group-analytics-table tbody');
        $tableBody.empty();
        
        if (groupAnalytics && groupAnalytics.length > 0) {
            groupAnalytics.forEach(function(group, index) {
                const statusClass = getGroupStatusClass(group.status);
                
                const row = `
                    <tr class="fade-in" style="animation-delay: ${index * 0.1}s">
                        <td>
                            <strong>${escapeHtml(group.group_name)}</strong>
                        </td>
                        <td>
                            <small>${escapeHtml(group.group_leaders)}</small>
                        </td>
                        <td>
                            <span class="user-count font-bold text-primary">
                                ${formatNumber(group.total_users)}
                            </span>
                        </td>
                        <td>
                            <span class="course-count text-success">
                                ${formatNumber(group.associated_courses)}
                            </span>
                        </td>
                        <td>
                            <span class="${getProgressClass(group.avg_progress)}">
                                ${group.avg_progress}
                            </span>
                        </td>
                        <td>
                            <span class="completed-count text-success font-bold">
                                ${formatNumber(group.completed_users)}
                            </span>
                        </td>
                        <td>
                            <small>${formatDate(group.created_date)}</small>
                        </td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${escapeHtml(group.status)}
                            </span>
                        </td>
                    </tr>
                `;
                $tableBody.append(row);
            });
            
            // Create group enrollment chart
            createGroupEnrollmentChart(groupAnalytics);
            
        } else {
            $tableBody.append(`
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="no-data-message">
                            <p><em>No group analytics data available.</em></p>
                            <p><small>Make sure LearnDash groups exist and users are enrolled.</small></p>
                            <p><small>Create test groups using LearnDash Testing Toolkit for demo purposes.</small></p>
                        </div>
                    </td>
                </tr>
            `);
        }
    }
    
    function createCourseCompletionChart(data) {
        const ctx = document.getElementById('course-completion-chart');
        if (!ctx) return;
        
        // Destroy existing chart
        if (completionChart) {
            completionChart.destroy();
        }
        
        // Handle empty data
        if (!data || data.length === 0) {
            $(ctx).closest('.wbcom-chart-container').html(`
                <div class="text-center no-data-message" style="padding: 100px;">
                    <h3>No Course Data Available</h3>
                    <p>Create courses and enroll users to see analytics here.</p>
                    <p><small>Use LearnDash Testing Toolkit to generate test data.</small></p>
                </div>
            `);
            return;
        }
        
        const chartData = {
            labels: data.map(course => course.course_name.substring(0, 20) + (course.course_name.length > 20 ? '...' : '')),
            datasets: [{
                label: 'Enrolled',
                data: data.map(course => course.enrolled_users || 0),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }, {
                label: 'Completed',
                data: data.map(course => course.completed || 0),
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2
            }, {
                label: 'In Progress',
                data: data.map(course => course.in_progress || 0),
                backgroundColor: 'rgba(255, 206, 86, 0.5)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 2
            }]
        };
        
        completionChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Course Enrollment vs Completion'
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    function createGroupEnrollmentChart(data) {
        const ctx = document.getElementById('group-enrollment-chart');
        if (!ctx) return;
        
        // Destroy existing chart
        if (window.groupChart) {
            window.groupChart.destroy();
        }
        
        // Handle empty data
        if (!data || data.length === 0) {
            $(ctx).closest('.wbcom-chart-container').html(`
                <div class="text-center no-data-message" style="padding: 100px;">
                    <h3>No Group Data Available</h3>
                    <p>Create LearnDash groups and enroll users to see analytics here.</p>
                    <p><small>Use LearnDash Testing Toolkit to generate test groups and enrollments.</small></p>
                </div>
            `);
            return;
        }
        
        const chartData = {
            labels: data.map(group => group.group_name.substring(0, 20) + (group.group_name.length > 20 ? '...' : '')),
            datasets: [{
                label: 'Total Users',
                data: data.map(group => group.total_users || 0),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }, {
                label: 'Completed Users',
                data: data.map(group => group.completed_users || 0),
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2
            }, {
                label: 'Associated Courses',
                data: data.map(group => group.associated_courses || 0),
                backgroundColor: 'rgba(255, 206, 86, 0.5)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 2
            }]
        };
        
        window.groupChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'LearnDash Group Enrollment Overview'
                    },
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    
    function applyLearningFilters() {
        currentFilter = $('#progress-filter').val();
        currentCourseFilter = $('#course-filter').val();
        
        loadLearnDashStats();
        showSuccessMessage('Learning filters applied successfully!');
    }
    
    function applyGroupFilters() {
        currentFilter = $('#group-filter').val();
        loadLearnDashStats();
        showSuccessMessage('Group filters applied successfully!');
    }
    
    function exportLearnDashData() {
        const $button = $('#export-learndash-stats');
        $button.prop('disabled', true).text('Exporting...');
        
        // Create download form
        const form = $('<form>', {
            method: 'POST',
            action: wbcomReports.ajaxurl
        });
        
        form.append($('<input>', { type: 'hidden', name: 'action', value: 'export_learndash_stats' }));
        form.append($('<input>', { type: 'hidden', name: 'nonce', value: wbcomReports.nonce }));
        form.append($('<input>', { type: 'hidden', name: 'tab', value: currentTab }));
        form.append($('<input>', { type: 'hidden', name: 'filter', value: currentFilter }));
        form.append($('<input>', { type: 'hidden', name: 'course_id', value: currentCourseFilter }));
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        setTimeout(function() {
            $button.prop('disabled', false).text('Export CSV');
        }, 2000);
        
        showSuccessMessage('Export started! Your download should begin shortly.');
    }
    
    function showLearnDashNotActiveMessage() {
        const $container = $('.wbcom-stats-container');
        const messageHtml = `
            <div class="notice notice-warning">
                <h3>LearnDash Not Active</h3>
                <p>LearnDash plugin is not active or not detected. To view learning reports:</p>
                <ul>
                    <li>Make sure LearnDash is installed and activated</li>
                    <li>Check if the LearnDash Testing Toolkit has created test data</li>
                    <li>Verify that courses and users exist in your system</li>
                </ul>
                <p><strong>For testing:</strong> Use the LearnDash Testing Toolkit to create sample courses, users, and progress data.</p>
            </div>
        `;
        
        $container.html(messageHtml);
    }
    
    function getProgressClass(progressRate) {
        const rate = parseFloat(progressRate);
        if (rate >= 80) return 'progress-excellent';
        if (rate >= 60) return 'progress-good';
        if (rate >= 40) return 'progress-average';
        return 'progress-poor';
    }
    
    function getGroupStatusClass(status) {
        switch (status.toLowerCase()) {
            case 'active': return 'status-active';
            case 'empty': return 'status-empty';
            case 'no courses': return 'status-no-courses';
            default: return 'status-default';
        }
    }
    
    function getActivityClass(lastActivity) {
        if (lastActivity === 'Never') return 'text-danger';
        
        const activityDate = new Date(lastActivity);
        const now = new Date();
        const daysDiff = (now - activityDate) / (1000 * 60 * 60 * 24);
        
        if (daysDiff <= 7) return 'text-success';
        if (daysDiff <= 30) return 'text-warning';
        return 'text-danger';
    }
    
    function showLoadingState() {
        $('.stat-number').text('Loading...');
        
        if (currentTab === 'user-progress') {
            $('#user-learning-stats-table tbody').html(`
                <tr><td colspan="7" class="text-center wbcom-loading">Loading learning progress...</td></tr>
            `);
        } else if (currentTab === 'course-analytics') {
            $('#course-analytics-table tbody').html(`
                <tr><td colspan="5" class="text-center wbcom-loading">Loading course analytics...</td></tr>
            `);
        } else if (currentTab === 'group-reports') {
            $('#group-analytics-table tbody').html(`
                <tr><td colspan="8" class="text-center wbcom-loading">Loading group analytics...</td></tr>
            `);
        }
    }
    
    function hideLoadingState() {
        $('#refresh-learndash-stats').prop('disabled', false).text('Refresh Stats');
    }
    
    function updateStatBox(selector, value) {
        const $element = $(selector);
        $element.fadeOut(200, function() {
            $element.text(formatNumber(value)).fadeIn(200);
        });
    }
    
    function showErrorMessage(message) {
        showMessage(message, 'error');
    }
    
    function showSuccessMessage(message) {
        showMessage(message, 'success');
    }
    
    function showMessage(message, type) {
        const $container = $('.wbcom-stats-container');
        const messageHtml = `
            <div class="notice notice-${type} is-dismissible fade-in">
                <p>${escapeHtml(message)}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        $container.prepend(messageHtml);
        
        setTimeout(function() {
            $(`.notice-${type}`).fadeOut();
        }, 5000);
    }
    
    // Utility functions
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return parseInt(num).toLocaleString();
    }
    
    function formatDate(dateString) {
        if (!dateString || dateString === 'Never') return 'Never';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        } catch (e) {
            return dateString;
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Handle notice dismissal
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Add dynamic styles for LearnDash
    if (!$('#learndash-dynamic-styles').length) {
        $('head').append(`
            <style id="learndash-dynamic-styles">
                .course-progress-container {
                    max-width: 250px;
                }
                .avg-progress {
                    margin-bottom: 8px;
                }
                .progress-bar {
                    width: 100%;
                    height: 8px;
                    background: #f0f0f0;
                    border-radius: 4px;
                    overflow: hidden;
                    margin-top: 4px;
                }
                .progress-fill {
                    height: 100%;
                    transition: width 0.3s ease;
                }
                .progress-excellent { color: #46b450; }
                .progress-excellent.progress-fill { background: #46b450; }
                .progress-good { color: #00a32a; }
                .progress-good.progress-fill { background: #00a32a; }
                .progress-average { color: #ffb900; }
                .progress-average.progress-fill { background: #ffb900; }
                .progress-poor { color: #dc3232; }
                .progress-poor.progress-fill { background: #dc3232; }
                
                .course-progress-list {
                    max-height: 120px;
                    overflow-y: auto;
                }
                .course-progress-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 4px;
                    padding: 2px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .course-progress-item:last-child {
                    border-bottom: none;
                }
                .course-name {
                    flex: 1;
                    margin-right: 8px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                .progress-badge {
                    font-weight: bold;
                    font-size: 11px;
                    padding: 2px 6px;
                    border-radius: 3px;
                    background: #f0f0f0;
                }
                
                .course-count, .completion-count { font-size: 16px; }
                .no-data-message {
                    padding: 40px 20px;
                    color: #666;
                    line-height: 1.6;
                }
                .text-muted { color: #666; }
                
                /* Group status badges */
                .status-active { background: #46b450; color: white; }
                .status-empty { background: #dc3232; color: white; }
                .status-no-courses { background: #ffb900; color: white; }
                .status-default { background: #666; color: white; }
                .status-badge {
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
            </style>
        `);
    }
});