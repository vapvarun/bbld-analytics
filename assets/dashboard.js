/**
 * Dashboard JavaScript - Updated with LearnDash Groups
 * 
 * @package Wbcom_Reports
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize dashboard
    initDashboard();
    
    function initDashboard() {
        loadDashboardStats();
        bindEvents();
    }
    
    function bindEvents() {
        // Refresh stats button
        $('#refresh-dashboard-stats').on('click', function() {
            $(this).prop('disabled', true).text('Refreshing...');
            loadDashboardStats();
        });
        
        // Auto-refresh every 5 minutes
        setInterval(function() {
            loadDashboardStats();
        }, 300000); // 5 minutes
    }
    
    function loadDashboardStats() {
        showLoadingState();
        
        $.ajax({
            url: wbcomReports.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_dashboard_stats',
                nonce: wbcomReports.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDashboardDisplay(response.data);
                } else {
                    showErrorMessage('Failed to load dashboard stats: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage('Error loading dashboard stats: ' + error);
            },
            complete: function() {
                hideLoadingState();
            }
        });
    }
    
    function updateDashboardDisplay(data) {
        // Update stat boxes with animation
        updateStatBox('#total-users', data.total_users);
        updateStatBox('#total-activities', data.total_activities);
        updateStatBox('#total-courses', data.total_courses);
        updateStatBox('#total-groups', data.total_groups);
        
        // Update top users table
        updateTopUsersTable(data.top_users);
        
        // Update top LearnDash groups table
        updateTopGroupsTable(data.top_groups);
    }
    
    function updateStatBox(selector, value) {
        const $element = $(selector);
        $element.fadeOut(200, function() {
            $element.text(formatNumber(value)).fadeIn(200);
        });
    }
    
    function updateTopUsersTable(users) {
        const $tableBody = $('#top-users-table tbody');
        $tableBody.empty();
        
        if (users && users.length > 0) {
            users.forEach(function(user) {
                const row = `
                    <tr class="fade-in">
                        <td><strong class="text-primary">#${user.rank}</strong></td>
                        <td>
                            <strong>${escapeHtml(user.display_name)}</strong>
                            <br><small class="text-muted">${escapeHtml(user.user_login)}</small>
                        </td>
                        <td>${escapeHtml(user.user_login)}</td>
                        <td><span class="font-bold text-primary">${formatNumber(user.activity_count)}</span></td>
                        <td>${formatNumber(user.comment_count)}</td>
                        <td><small>${formatDate(user.last_activity)}</small></td>
                    </tr>
                `;
                $tableBody.append(row);
            });
        } else {
            $tableBody.append(`
                <tr>
                    <td colspan="6" class="text-center">
                        <em>No activity data found. Users need to post activities first.</em>
                    </td>
                </tr>
            `);
        }
    }
    
    function updateTopGroupsTable(groups) {
        const $tableBody = $('#top-groups-table tbody');
        $tableBody.empty();
        
        if (groups && groups.length > 0) {
            groups.forEach(function(group) {
                const row = `
                    <tr class="fade-in">
                        <td><strong class="text-primary">#${group.rank}</strong></td>
                        <td>
                            <strong>${escapeHtml(group.group_name)}</strong>
                        </td>
                        <td>
                            <span class="font-bold text-success">${formatNumber(group.members_count)}</span>
                        </td>
                        <td>
                            <small>${escapeHtml(group.group_leaders)}</small>
                        </td>
                        <td>
                            <span class="text-warning">${formatNumber(group.courses_count)}</span>
                        </td>
                        <td>
                            <small>${formatDate(group.created_date)}</small>
                        </td>
                    </tr>
                `;
                $tableBody.append(row);
            });
        } else {
            $tableBody.append(`
                <tr>
                    <td colspan="6" class="text-center">
                        <em>No LearnDash groups found. Create groups to see them here.</em>
                    </td>
                </tr>
            `);
        }
    }
    
    function showLoadingState() {
        // Show loading in stat boxes
        $('.stat-number').text('Loading...');
        
        // Show loading in tables
        $('#top-users-table tbody').html('<tr><td colspan="6" class="text-center wbcom-loading">Loading users...</td></tr>');
        $('#top-groups-table tbody').html('<tr><td colspan="6" class="text-center wbcom-loading">Loading groups...</td></tr>');
    }
    
    function hideLoadingState() {
        $('#refresh-dashboard-stats').prop('disabled', false).text('Refresh Stats');
    }
    
    function showErrorMessage(message) {
        const $container = $('.wbcom-stats-container');
        const errorHtml = `
            <div class="notice notice-error is-dismissible">
                <p><strong>Error:</strong> ${escapeHtml(message)}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        $container.prepend(errorHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.notice-error').fadeOut();
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
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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
    
    // Add status badge styles dynamically
    if (!$('#wbcom-dynamic-styles').length) {
        $('head').append(`
            <style id="wbcom-dynamic-styles">
                .text-muted { color: #666; }
            </style>
        `);
    }
});