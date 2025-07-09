/**
 * BuddyPress Reports JavaScript
 * 
 * @package Wbcom_Reports
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // State management
    let currentPage = 1;
    let currentFilter = 'all';
    let currentDateFrom = '';
    let currentDateTo = '';
    
    // Initialize BuddyPress reports
    initBuddyPressReports();
    
    function initBuddyPressReports() {
        loadBuddyPressStats();
        bindEvents();
        initFilters();
    }
    
    function bindEvents() {
        // Refresh stats button
        $('#refresh-buddypress-stats').on('click', function() {
            $(this).prop('disabled', true).text('Refreshing...');
            loadBuddyPressStats();
        });
        
        // Export CSV button
        $('#export-buddypress-stats').on('click', function() {
            exportBuddyPressData();
        });
        
        // Apply filters button
        $('#apply-filters').on('click', function() {
            applyFilters();
        });
        
        // Pagination buttons
        $('#prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadBuddyPressStats();
            }
        });
        
        $('#next-page').on('click', function() {
            currentPage++;
            loadBuddyPressStats();
        });
        
        // Filter change events
        $('#activity-filter').on('change', function() {
            currentFilter = $(this).val();
            currentPage = 1;
            loadBuddyPressStats();
        });
        
        // Date filter events
        $('#date-from, #date-to').on('change', function() {
            currentDateFrom = $('#date-from').val();
            currentDateTo = $('#date-to').val();
            currentPage = 1;
        });
    }
    
    function initFilters() {
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        $('#date-to').val(formatDateInput(today));
        $('#date-from').val(formatDateInput(thirtyDaysAgo));
    }
    
    function loadBuddyPressStats() {
        showLoadingState();
        
        $.ajax({
            url: wbcomReports.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_buddypress_stats',
                nonce: wbcomReports.nonce,
                page: currentPage,
                filter: currentFilter,
                date_from: currentDateFrom,
                date_to: currentDateTo
            },
            success: function(response) {
                if (response.success) {
                    updateBuddyPressDisplay(response.data);
                } else {
                    showErrorMessage('Failed to load BuddyPress stats: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                showErrorMessage('Error loading BuddyPress stats: ' + error);
            },
            complete: function() {
                hideLoadingState();
            }
        });
    }
    
    function updateBuddyPressDisplay(data) {
        // Update stat boxes with animation
        updateStatBox('#total-users', data.total_users);
        updateStatBox('#total-activities', data.total_activities);
        updateStatBox('#total-comments', data.total_comments);
        updateStatBox('#total-likes', data.total_likes);
        
        // Update user statistics table
        updateUserStatsTable(data.user_stats);
        
        // Update pagination
        updatePagination(data.pagination);
    }
    
    function updateUserStatsTable(userStats) {
        const $tableBody = $('#user-activity-stats-table tbody');
        $tableBody.empty();
        
        if (userStats && userStats.length > 0) {
            userStats.forEach(function(user, index) {
                const row = `
                    <tr class="fade-in" style="animation-delay: ${index * 0.1}s">
                        <td>
                            <strong>${escapeHtml(user.display_name)}</strong>
                            <br><small class="text-muted">${escapeHtml(user.user_login)}</small>
                        </td>
                        <td><code>${escapeHtml(user.user_login)}</code></td>
                        <td><small>${formatDate(user.registration_date)}</small></td>
                        <td>
                            <small class="${getLastLoginClass(user.last_login)}">
                                ${formatDate(user.last_login)}
                            </small>
                        </td>
                        <td>
                            <span class="activity-count font-bold text-primary">
                                ${formatNumber(user.activity_count)}
                            </span>
                        </td>
                        <td>
                            <span class="comment-count">
                                ${formatNumber(user.comment_count)}
                            </span>
                        </td>
                        <td>
                            <span class="like-count text-success">
                                ${formatNumber(user.like_count)}
                            </span>
                        </td>
                        <td>
                            <span class="profile-views text-warning">
                                ${formatNumber(user.profile_views || 0)}
                            </span>
                        </td>
                    </tr>
                `;
                $tableBody.append(row);
            });
            
            // Add hover effects
            addTableHoverEffects();
            
        } else {
            $tableBody.append(`
                <tr>
                    <td colspan="8" class="text-center">
                        <div class="no-data-message">
                            <p><em>No user data found with the current filters.</em></p>
                            <p><small>Try adjusting your filter criteria or date range.</small></p>
                        </div>
                    </td>
                </tr>
            `);
        }
    }
    
    function updatePagination(pagination) {
        if (!pagination) return;
        
        const { current_page, total_pages, total_users } = pagination;
        
        // Update page info
        $('#page-info').text(`Page ${current_page} of ${total_pages} (${formatNumber(total_users)} total users)`);
        
        // Update button states
        $('#prev-page').prop('disabled', current_page <= 1);
        $('#next-page').prop('disabled', current_page >= total_pages);
        
        // Show/hide pagination if needed
        if (total_pages <= 1) {
            $('.wbcom-pagination').hide();
        } else {
            $('.wbcom-pagination').show();
        }
    }
    
    function applyFilters() {
        currentFilter = $('#activity-filter').val();
        currentDateFrom = $('#date-from').val();
        currentDateTo = $('#date-to').val();
        currentPage = 1;
        
        loadBuddyPressStats();
        
        // Show filter applied message
        showSuccessMessage('Filters applied successfully!');
    }
    
    function exportBuddyPressData() {
        const $button = $('#export-buddypress-stats');
        $button.prop('disabled', true).text('Exporting...');
        
        // Create a form and submit it to trigger download
        const form = $('<form>', {
            method: 'POST',
            action: wbcomReports.ajaxurl
        });
        
        form.append($('<input>', { type: 'hidden', name: 'action', value: 'export_buddypress_stats' }));
        form.append($('<input>', { type: 'hidden', name: 'nonce', value: wbcomReports.nonce }));
        form.append($('<input>', { type: 'hidden', name: 'filter', value: currentFilter }));
        form.append($('<input>', { type: 'hidden', name: 'date_from', value: currentDateFrom }));
        form.append($('<input>', { type: 'hidden', name: 'date_to', value: currentDateTo }));
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        // Reset button after delay
        setTimeout(function() {
            $button.prop('disabled', false).text('Export CSV');
        }, 2000);
        
        showSuccessMessage('Export started! Your download should begin shortly.');
    }
    
    function addTableHoverEffects() {
        $('#user-activity-stats-table tbody tr').hover(
            function() {
                $(this).addClass('table-row-hover');
            },
            function() {
                $(this).removeClass('table-row-hover');
            }
        );
    }
    
    function getLastLoginClass(lastLogin) {
        if (lastLogin === 'Never') return 'text-danger';
        
        const loginDate = new Date(lastLogin);
        const now = new Date();
        const daysDiff = (now - loginDate) / (1000 * 60 * 60 * 24);
        
        if (daysDiff <= 7) return 'text-success';
        if (daysDiff <= 30) return 'text-warning';
        return 'text-danger';
    }
    
    function showLoadingState() {
        // Show loading in stat boxes
        $('.stat-number').text('Loading...');
        
        // Show loading in table
        $('#user-activity-stats-table tbody').html(`
            <tr>
                <td colspan="8" class="text-center wbcom-loading">
                    Loading user activity data...
                </td>
            </tr>
        `);
        
        // Hide pagination during loading
        $('.wbcom-pagination').hide();
    }
    
    function hideLoadingState() {
        $('#refresh-buddypress-stats').prop('disabled', false).text('Refresh Stats');
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
        
        // Auto-dismiss after 5 seconds
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
    
    function formatDateInput(date) {
        return date.toISOString().split('T')[0];
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
    
    // Add dynamic styles
    if (!$('#buddypress-dynamic-styles').length) {
        $('head').append(`
            <style id="buddypress-dynamic-styles">
                .table-row-hover {
                    background-color: #e8f4f8 !important;
                    transform: scale(1.01);
                    transition: all 0.2s ease;
                }
                .activity-count {
                    font-size: 16px;
                }
                .no-data-message {
                    padding: 40px 20px;
                    color: #666;
                }
                .fade-in {
                    animation: fadeIn 0.5s ease-in forwards;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>
        `);
    }
});