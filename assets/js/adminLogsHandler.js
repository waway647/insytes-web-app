// assets/js/adminLogsHandler.js - Professional System Logs Management
document.addEventListener("DOMContentLoaded", function () {
    const logsList = document.querySelector(".logs-list");
    const loadingState = document.getElementById("loadingState");
    const logsTable = document.getElementById("logsTable");
    const emptyState = document.getElementById("emptyState");
    const pagination = document.getElementById("pagination");
    const searchInput = document.getElementById("searchInput");
    
    // Modals
    const filtersModal = document.getElementById("filtersModal");
    const logDetailsModal = document.getElementById("logDetailsModal");

    // API URL
    const API_URL = "/github/insytes-web-app/index.php/Admin/LogsController";

    // Current state
    let currentFilters = {
        search: '',
        category: '',
        role: '',
        date_from: '',
        date_to: '',
        page: 1,
        per_page: 25
    };

    // Category names mapping for display
    const categoryNames = {
        '': 'All Events',
        'authentication': 'Authentication',
        'user_management': 'User Management', 
        'team_operations': 'Team Operations',
        'match_activities': 'Match Activities',
        'system_events': 'System Events',
        'security_events': 'Security Events',
        'data_operations': 'Data Operations'
    };

    // Category icons mapping
    const categoryIcons = {
        authentication: "🔐",
        user_management: "👤", 
        team_operations: "👥",
        match_activities: "⚽",
        system_events: "⚙️",
        security_events: "🚨",
        data_operations: "📊"
    };

    // Get filter elements
    const activeFilterIndicator = document.getElementById('activeFilterIndicator');
    const activeFilterText = document.getElementById('activeFilterText');
    const clearActiveFilter = document.getElementById('clearActiveFilter');

    // Initialize
    loadLogs();
    setupEventListeners();

    // Load logs with current filters
    function loadLogs() {
        showLoading();
        
        const params = new URLSearchParams(currentFilters);
        
        fetch(`${API_URL}/getLogs?${params}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    renderLogs(data.data.logs);
                    renderPagination(data.data.pagination);
                    updateUserFilter(data.data.users);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error loading logs:', error);
                showError('Failed to load logs');
            });
    }

    // Render logs table
    function renderLogs(logs) {
        if (!logsList) return;

        if (logs.length === 0) {
            showEmptyState();
            return;
        }

        showTable();
        
        logsList.innerHTML = logs.map(log => {
            const categoryIcon = categoryIcons[log.category.toLowerCase().replace(' ', '_')] || '📝';
            
            return `
                <div class="px-6 py-4 hover:bg-[#242424] transition-colors duration-150 cursor-pointer log-row border-t border-[#2a2a2a] first:border-t-0" data-log='${JSON.stringify(log)}'>
                    <div class="grid grid-cols-12 gap-4 items-center">
                        <div class="col-span-2">
                            <div class="text-xs font-mono text-gray-400">${log.formatted_time}</div>
                        </div>
                        
                        <div class="col-span-2">
                            <div class="flex items-center">
                                <span class="mr-2 text-sm">${categoryIcon}</span>
                                <span class="text-sm text-gray-300 font-medium">${log.category}</span>
                            </div>
                        </div>
                        
                        <div class="col-span-2">
                            <span class="text-sm font-semibold text-white bg-indigo-900/20 px-2.5 py-1 rounded-md inline-block">${log.action}</span>
                        </div>
                        
                        <div class="col-span-3">
                            <div class="text-sm text-gray-300 truncate" title="${log.message}">
                                ${log.message}
                            </div>
                        </div>
                        
                        <div class="col-span-3">
                            <div class="text-sm text-gray-300">${log.user_name}</div>
                            <div class="text-xs text-gray-500">${log.user_email}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Add click handlers for log details
        document.querySelectorAll('.log-row').forEach(row => {
            row.addEventListener('click', function() {
                const logData = JSON.parse(this.dataset.log);
                showLogDetails(logData);
            });
        });
    }

    // Show log details modal
    function showLogDetails(log) {
        const content = document.getElementById('logDetailsContent');
        if (!content) return;

        const severityClass = severityColors[log.severity] || severityColors.info;
        const categoryIcon = categoryIcons[log.category.toLowerCase().replace(' ', '_')] || '📝';

        content.innerHTML = `
            <div class="text-white">
                <h3 class="text-lg font-medium mb-4 flex items-center">
                    <span class="mr-2">${categoryIcon}</span>
                    Log Details
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">Category</label>
                        <div class="mt-1 text-sm text-gray-300">${log.category}</div>
                    </div>
                    
                    <div>
                        <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">Action</label>
                        <div class="mt-1 text-sm font-medium text-white">${log.action}</div>
                    </div>
                    
                    <div>
                        <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">Message</label>
                        <div class="mt-1 text-sm text-gray-300">${log.message}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">User</label>
                            <div class="mt-1">
                                <div class="text-sm text-gray-300">${log.user_name}</div>
                                <div class="text-xs text-gray-500">${log.user_email}</div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">IP Address</label>
                            <div class="mt-1 text-sm font-mono text-gray-300">${log.ip_address || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">Timestamp</label>
                        <div class="mt-1 text-sm font-mono text-gray-300">${log.created_at}</div>
                    </div>
                    
                    ${log.metadata ? `
                    <div>
                        <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">Metadata</label>
                        <div class="mt-1 bg-gray-900 rounded-md p-3">
                            <pre class="text-xs text-gray-300 overflow-x-auto">${JSON.stringify(log.metadata, null, 2)}</pre>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${log.user_agent ? `
                    <div>
                        <label class="text-xs font-medium text-gray-400 uppercase tracking-wider">User Agent</label>
                        <div class="mt-1 text-xs text-gray-400 break-all">${log.user_agent}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;

        logDetailsModal.classList.remove('hidden');
    }

    // Setup event listeners
    function setupEventListeners() {
        // Search
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilters.search = this.value.trim();
                    currentFilters.page = 1;
                    loadLogs();
                }, 500);
            });
        }

        document.querySelectorAll('.category-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                const inactiveClass = 'category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 border-gray-600 bg-gray-900/20 text-gray-300 hover:bg-gray-800 hover:text-gray-200';
                const activeClass = 'category-chip group inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 ring-2 ring-indigo-500 bg-indigo-600 border-indigo-500 text-white';
                
                // Check if this button is already active
                const isActive = this.classList.contains('ring-2');
                
                if (isActive) {
                    // Toggle OFF: reset all buttons to gray, clear filter
                    document.querySelectorAll('.category-chip').forEach(btn => {
                        btn.className = inactiveClass;
                    });
                    currentFilters.category = '';
                } else {
                    // Toggle ON: set this button as active, others inactive
                    const selectedCategory = this.dataset.category;
                    document.querySelectorAll('.category-chip').forEach(btn => {
                        if (btn === this) {
                            btn.className = activeClass;
                        } else {
                            btn.className = inactiveClass;
                        }
                    });
                    currentFilters.category = selectedCategory;
                }
                
                currentFilters.page = 1;
                loadLogs();
                
                // Add subtle animation feedback
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });


        // Filters modal
        document.getElementById('filtersBtn')?.addEventListener('click', () => {
            filtersModal.classList.remove('hidden');
        });

        document.getElementById('cancelFilters')?.addEventListener('click', () => {
            filtersModal.classList.add('hidden');
        });

        document.getElementById('applyFilters')?.addEventListener('click', () => {
            currentFilters.role = document.getElementById('roleFilter')?.value || '';
            currentFilters.date_from = document.getElementById('dateFrom')?.value || '';
            currentFilters.date_to = document.getElementById('dateTo')?.value || '';
            currentFilters.page = 1;
            
            filtersModal.classList.add('hidden');
            loadLogs();
        });

        document.getElementById('clearFilters')?.addEventListener('click', () => {
            if (document.getElementById('roleFilter')) document.getElementById('roleFilter').value = '';
            if (document.getElementById('dateFrom')) document.getElementById('dateFrom').value = '';
            if (document.getElementById('dateTo')) document.getElementById('dateTo').value = '';
            
            currentFilters = {
                search: currentFilters.search, // Keep search
                category: currentFilters.category, // Keep category
                role: '',
                date_from: '',
                date_to: '',
                page: 1,
                per_page: currentFilters.per_page
            };
            
            loadLogs();
        });

        // Log details modal
        document.getElementById('closeLogDetails')?.addEventListener('click', () => {
            logDetailsModal.classList.add('hidden');
        });

        // Export
        document.getElementById('exportBtn')?.addEventListener('click', () => {
            const params = new URLSearchParams({
                search: currentFilters.search,
                category: currentFilters.category,
                role: currentFilters.role,
                date_from: currentFilters.date_from,
                date_to: currentFilters.date_to
            });
            
            window.open(`${API_URL}/exportLogs?${params}`, '_blank');
        });
    }

    // Render pagination
    function renderPagination(paginationData) {
        if (!pagination) return;

        const { total, per_page, current_page, total_pages, showing_from, showing_to } = paginationData;

        // Update showing text
        document.getElementById('showingFrom').textContent = showing_from;
        document.getElementById('showingTo').textContent = showing_to;
        document.getElementById('totalLogs').textContent = total;

        // Show/hide pagination
        if (total_pages <= 1) {
            pagination.classList.add('hidden');
            return;
        }

        pagination.classList.remove('hidden');

        // Update pagination buttons
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageNumbers = document.getElementById('pageNumbers');

        prevBtn.disabled = current_page <= 1;
        nextBtn.disabled = current_page >= total_pages;

        // Remove old event listeners and add new ones
        prevBtn.onclick = () => {
            if (current_page > 1) {
                currentFilters.page = current_page - 1;
                loadLogs();
            }
        };

        nextBtn.onclick = () => {
            if (current_page < total_pages) {
                currentFilters.page = current_page + 1;
                loadLogs();
            }
        };

        // Generate page numbers (show 5 pages max)
        let startPage = Math.max(1, current_page - 2);
        let endPage = Math.min(total_pages, startPage + 4);
        
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }

        pageNumbers.innerHTML = '';
        for (let i = startPage; i <= endPage; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = `px-3 py-2 text-sm rounded-md ${
                i === current_page 
                    ? 'bg-indigo-600 text-white' 
                    : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
            }`;
            
            button.onclick = () => {
                currentFilters.page = i;
                loadLogs();
            };
            
            pageNumbers.appendChild(button);
        }
    }

    // Update user filter dropdown
    function updateUserFilter(users) {
        const userFilter = document.getElementById('userFilter');
        if (!userFilter || !users) return;

        // Clear existing options (except first)
        while (userFilter.children.length > 1) {
            userFilter.removeChild(userFilter.lastChild);
        }

        // Add user options
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.name} (${user.email})`;
            userFilter.appendChild(option);
        });
    }

    // Update active filter indicator
    function updateActiveFilterIndicator(indicator, textElement, category, categoryNames) {
        if (category === '') {
            indicator.classList.add('hidden');
        } else {
            indicator.classList.remove('hidden');
            textElement.textContent = categoryNames[category] || category;
        }
    }

    // UI State functions
    function showLoading() {
        loadingState?.classList.remove('hidden');
        logsTable?.classList.add('hidden');
        emptyState?.classList.add('hidden');
    }

    function hideLoading() {
        loadingState?.classList.add('hidden');
    }

    function showTable() {
        if (logsTable) {
            logsTable.classList.remove('hidden');
            logsTable.style.display = 'flex';
        }
        emptyState?.classList.add('hidden');
    }

    function showEmptyState() {
        if (logsTable) {
            logsTable.classList.add('hidden');
            logsTable.style.display = 'none';
        }
        emptyState?.classList.remove('hidden');
        pagination?.classList.add('hidden');
    }

    function showError(message) {
        console.error(message);
        showEmptyState();
        
        const emptyStateContent = emptyState?.querySelector('h3');
        if (emptyStateContent) {
            emptyStateContent.textContent = 'Error loading logs';
        }
        
        const emptyStateDesc = emptyState?.querySelector('p');
        if (emptyStateDesc) {
            emptyStateDesc.textContent = message;
        }
    }
});