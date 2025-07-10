/**
 * Enhanced LearnDash Reports JavaScript with Indexing Support
 * 
 * @package Wbcom_Reports
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // State management
    let currentTab = 'user-progress';
    let currentFilter = 'all';
    let currentCourseFilter = 'all';
    let currentSearch = '';
    let currentSortBy = 'enrolled_courses';
    let currentSortOrder = 'desc';
    let completionChart = null;
    let isIndexing = false;
    
    // Initialize LearnDash reports
    initLearnDashReports();
    
    function initLearnDashReports() {
        loadLearnDashStats();
        bindEvents();
        initTabs();
        checkIndexStatus();
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
        
        // Rebuild index button
        $('#rebuild-ld-index').on('click', function() {
            rebuildLearnDashIndex();
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
        
        // Search functionality for user progress
        $('#user-search').on('input', debounce(function() {
            currentSearch = $(this).val().trim();
            if (currentTab === 'user-progress') {
                loadLearnDashStats();
            }
        }, 500));
        
        // Clear search button
        $('#clear-search').on('click', function() {
            $('#user-search').val('');
            currentSearch = '';
            if (currentTab === 'user-progress') {
                loadLearnDashStats();
            }
        });
        
        // Sort functionality for user progress
        $('.sortable-header').on('click', function() {
            if (currentTab !== 'user-progress') return;
            
            const sortBy = $(this).data('sort');
            
            if (currentSortBy === sortBy) {
                // Toggle sort order
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                // New sort column
                currentSortBy = sortBy;
                currentSortOrder = 'desc';
            }
            
            // Update UI indicators
            updateSortIndicators();
            
            // Reload data
            loadLearnDashStats();
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
    
    function checkIndexStatus() {
        // Check if index needs rebuilding based on UI indicators
        const $rebuildButton = $('#rebuild-ld-index');
        if ($rebuildButton.hasClass('needs-rebuild')) {
            showIndexWarning();
        }
    }
    
    function showIndexWarning() {
        const warningHtml = `
            <div class="notice notice-warning is-dismissible index-warning">
                <p>
                    <strong>Learning Performance Notice:</strong> 
                    The learning index needs rebuilding for optimal performance. 
                    <a href="#" id="rebuild-ld-index-link">Rebuild now</a> to improve loading times.
                </p>
            </div>
        `;
        
        $('.wbcom-stats-container').prepend(warningHtml);
        
        $('#rebuild-ld-index-link').on('click', function(e) {
            e.preventDefault();
            rebuildLearnDashIndex();
        });
    }
    
    function rebuildLearnDashIndex() {
        if (isIndexing) {
            return;
        }
        
        const $button = $('#rebuild-ld-index');
        const originalText = $button.text();
        
        isIndexing = true;
        $button.prop('disabled', true).text('Rebuilding Learning Index...');
        
        // Show progress indicator
        showIndexProgress();
        
        $.ajax({
            url: wbcomReports.ajaxurl,
            type: 'POST',
            data: {
                action: 'rebuild_ld_index',
                nonce: wbcomReports.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message);
                    
                    // Update index status display
                    updateIndexStatus(response.data.indexed_count);
                    
                    // Remove warning if present
                    $('.index-warning').fadeOut();
                    
                    // Change button styling back to normal
                    $button.removeClass('needs-rebuild');
                    
                    // Refresh the data to show improved performance
                    setTimeout(() => {
                        loadLearnDashStats();
                    }, 1000);
                    
                } else {
                    showErrorMessage('Failed to rebuild learning index: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage('Error rebuilding learning index: ' + error);
            },
            complete: function() {
                isIndexing = false;
                $button.prop('disabled', false).text(originalText);
                hideIndexProgress();
            }
        });
    }
    
    function showIndexProgress() {
        const progressHtml = `
            <div id="ld-index-progress" class="wbcom-index-progress">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p>Rebuilding learning index... This may take a few moments for large sites with many learners.</p>
            </div>
        `;
        
        $('.wbcom-stats-actions').after(progressHtml);
        
        // Animate progress bar
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 90) progress = 90;
            
            $('#ld-index-progress .progress-fill').css('width', progress + '%');
        }, 500);
        
        // Store interval for cleanup
        $('#ld-index-progress').data('interval', interval);
    }
    
    function hideIndexProgress() {
        const $progress = $('#ld-index-progress');
        const interval = $progress.data('interval');
        
        if (interval) {
            clearInterval(interval);
        }
        
        // Complete the progress bar
        $progress.find('.progress-fill').css('width', '100%');
        
        setTimeout(() => {
            $progress.fadeOut(() => {
                $progress.remove();
            });
        }, 500);
    }
    
    function updateIndexStatus(indexedCount) {
        $('.index-details p').first().html(function(i, html) {
            return html.replace(/\d+ of \d+ users indexed/, 
                indexedCount + ' of ' + indexedCount + ' users indexed (100% coverage)');
        });
    }
    
    function initTabs() {
        // Set initial active tab
        switchTab('user-progress');
        updateSortIndicators();
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
        
        // Show/hide search controls based on tab
        if (tabId === 'user-progress') {
            $('.search-controls').show();
            updateSortIndicators();
        } else {
            $('.search-controls').hide();
        }
        
        // Load data for the new tab
        loadLearnDashStats();
    }
    
    function updateSortIndicators() {
        if (currentTab !== 'user-progress') return;
        
        // Remove all sort indicators
        $('.sortable-header .sort-indicator').remove();
        
        // Add indicator to current sort column
        const $currentHeader = $(`.sortable-header[data-sort="${currentSortBy}"]`);
        const indicator = currentSortOrder === 'asc' ? '↑' : '↓';
        $currentHeader.append(`<span class="sort-indicator">${indicator}</span>`);
        
        // Update header classes
        $('.sortable-header').removeClass('sorted-asc sorted-desc');
        $currentHeader.addClass(`sorted-${currentSortOrder}`);
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
                course_id: currentCourseFilter,
                search: currentSearch,
                sort_by: currentSortBy,
                sort_order: currentSortOrder
            },
            success: function(response) {
                if (response.success) {
                    updateLearnDashDisplay(response.data);
                    
                    // Show performance indicator if using indexed data
                    if (response.data.using_index) {
                        showPerformanceIndicator();
                    }
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
    
    function showPerformanceIndicator() {
        if (!$('.performance-indicator').length) {
            const indicatorHtml = `
                <div class="performance-indicator">
                    <span class="dashicons dashicons-performance"></span>
                    <small>Using indexed data for fast performance</small>
                </div>
            `;
            $('.wbcom-stats-actions').append(indicatorHtml);
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                $('.performance-indicator').fadeOut();
            }, 3000);
        }
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
                updateSearchInfo(data.search_info);
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
                const editUrl = wbcomReports.adminUrl + 'user-edit.php?user_id=' + user.user_id;
                
                const row = `
                    <tr class="fade-in" style="animation-delay: ${index * 0.1}s">
                        <td>
                            <strong>
                                <a href="${editUrl}" class="user-edit-link" target="_blank">
                                    ${escapeHtml(user.display_name)}
                                    <span class="dashicons dashicons-external" style="font-size: 12px; margin-left: 4px;"></span>
                                </a>
                            </strong>
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
                            <div class="avg-progress">
                                <span class="progress-percentage ${progressClass}">
                                    ${user.avg_progress}
                                </span>
                                <div class="progress-bar">
                                    <div class="progress-fill ${progressClass}" 
                                         style="width: ${user.avg_progress}">
                                    </div>
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
            const noDataMessage = currentSearch ? 
                `No learners found matching "${currentSearch}". Try adjusting your search or filters.` :
                'No learning data found with the current filters. Users need to be enrolled in courses and have progress data.';
                
            $tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="no-data-message">
                            <p><em>${noDataMessage}</em></p>
                            ${currentSearch ? '<p><small>Clear the search to see all learners.</small></p>' : 
                              '<p><small>Try switching to "All Learners" filter or create test data using LearnDash Testing Toolkit.</small></p>'}
                        </div>
                    </td>
                </tr>
            `);
        }
    }
    
    function updateSearchInfo(searchInfo) {
        if (!searchInfo || currentTab !== 'user-progress') return;
        
        const $searchInfo = $('#search-info');
        if (searchInfo.active_search) {
            $searchInfo.html(`
                <div class="search-results-info">
                    <strong>Search Results:</strong> Found ${formatNumber(searchInfo.filtered_count)} 
                    of ${formatNumber(searchInfo.total_count)} learners
                    ${searchInfo.search_term ? ` matching "${escapeHtml(searchInfo.search_term)}"` : ''}
                </div>
            `).show();
        } else {
            $searchInfo.hide();
        }
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
        form.append($('<input>', { type: 'hidden', name: 'search', value: currentSearch }));
        form.append($('<input>', { type: 'hidden', name: 'sort_by', value: currentSortBy }));
        form.append($('<input>', { type: 'hidden', name: 'sort_order', value: currentSortOrder }));
        
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
            $('#search-info').hide();
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
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Handle notice dismissal
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Add dynamic styles for LearnDash
    if (!$('#learndash-dynamic-styles').length) {
        $('head').append(`
            <style id="learndash-dynamic-styles">
                .avg-progress {
                    max-width: 150px;
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
                
                /* Index Status Styles */
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
                
                /* Progress Bar Styles */
                .wbcom-index-progress {
                    background: #fff;
                    border: 1px solid #0073aa;
                    border-radius: 4px;
                    padding: 15px;
                    margin: 15px 0;
                    text-align: center;
                }
                .wbcom-index-progress .progress-bar {
                    width: 100%;
                    height: 20px;
                    background: #f0f0f0;
                    border-radius: 10px;
                    overflow: hidden;
                    margin-bottom: 10px;
                }
                .wbcom-index-progress .progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #0073aa, #00a0d2);
                    width: 0%;
                    transition: width 0.3s ease;
                    border-radius: 10px;
                }
                
                /* Performance Indicator */
                .performance-indicator {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    background: #d4edda;
                    color: #155724;
                    padding: 5px 10px;
                    border-radius: 3px;
                    font-size: 12px;
                    margin-left: 10px;
                }
                .performance-indicator .dashicons {
                    font-size: 14px;
                    width: 14px;
                    height: 14px;
                }
                
                /* Search and Sort Styles */
                .search-controls {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 15px;
                    flex-wrap: wrap;
                }
                .search-input-wrapper {
                    position: relative;
                    flex: 1;
                    min-width: 250px;
                }
                .search-input-wrapper input {
                    padding-right: 30px;
                }
                .search-clear {
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    color: #666;
                    cursor: pointer;
                    font-size: 14px;
                }
                .search-clear:hover {
                    color: #dc3232;
                }
                .search-results-info {
                    background: #e7f3ff;
                    border: 1px solid #0073aa;
                    padding: 8px 12px;
                    border-radius: 3px;
                    margin-bottom: 15px;
                    font-size: 13px;
                }
                
                /* Sortable headers */
                .sortable-header {
                    cursor: pointer;
                    user-select: none;
                    position: relative;
                    transition: background-color 0.2s ease;
                }
                .sortable-header:hover {
                    background-color: #f0f0f0;
                }
                .sort-indicator {
                    margin-left: 5px;
                    font-weight: bold;
                    color: #0073aa;
                }
                .sortable-header.sorted-asc,
                .sortable-header.sorted-desc {
                    background-color: #f8f9fa;
                }
                
                /* User edit link styles */
                .user-edit-link {
                    text-decoration: none;
                    color: #0073aa;
                    transition: color 0.2s ease;
                }
                .user-edit-link:hover {
                    color: #005a87;
                    text-decoration: underline;
                }
                .user-edit-link .dashicons {
                    opacity: 0.7;
                    transition: opacity 0.2s ease;
                }
                .user-edit-link:hover .dashicons {
                    opacity: 1;
                }
            </style>
        `);
    }
});