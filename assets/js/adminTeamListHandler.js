// assets/js/adminTeamListHandler.js - Teams Management Handler
document.addEventListener("DOMContentLoaded", function () {
    const teamsList = document.querySelector(".teams-list");
    const countBadge = document.getElementById("teamCount");
    const searchInput = document.getElementById("searchInput");
    const locationFilter = document.getElementById("locationFilter");
    const managerFilter = document.getElementById("managerFilter");
    const teamManagerSelect = document.getElementById("teamManager");

    // Modals
    const filtersModal = document.getElementById("filtersModal");
    const addTeamModal = document.getElementById("addTeamModal");

    // API URL - Updated to match your CodeIgniter setup
    const API_URL = "/github/insytes-web-app/index.php/Admin/TeamController";

    let allTeams = []; // Store all teams for client-side filtering
    let totalTeamCount = 0; // Store the total count from server
    let allManagers = []; // Store available managers

    // Render teams
    function render(teams) {
        if (!teamsList) return;

        if (teams.length === 0) {
            teamsList.innerHTML = `
                <div class="px-8 py-20 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-gray-400 text-lg">No teams found</p>
                </div>`;
            if (countBadge) countBadge.textContent = "0";
            return;
        }

        teamsList.innerHTML = teams.map(team => {
            return `
                <div class="px-8 py-6 hover:bg-gray-800/30 transition duration-200 border-b border-[#2a2a2a]">
                    <div class="grid grid-cols-12 gap-4 text-sm items-center">
                        <div class="col-span-3 flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 mr-3">
                                <img class="h-12 w-12 rounded-full object-contain" 
                                     src="${team.logo_url || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iMTYiIGZpbGw9IiM0Qjc0ODAiLz4KPHN2ZyB4PSI4IiB5PSI4IiB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIxLjUiPgo8cGF0aCBkPSJtNiA4IDEuNSAxLjUgMyAzIi8+CjxjaXJjbGUgY3g9IjgiIGN5PSI4IiByPSI3Ii8+Cjwvc3ZnPgo8L3N2Zz4K'}" 
                                     alt="${team.team_name}">
                            </div>
                            <div>
                                <div class="text-white font-medium">${team.team_name}</div>
                            </div>
                        </div>
                        <div class="col-span-1 text-gray-400 flex justify-center">${team.abbreviation}</div>
                        <div class="col-span-2 text-gray-400 flex justify-center">${team.location}</div>
                        <div class="col-span-2 text-white flex justify-center">${team.manager}</div>
                        <div class="col-span-2 text-gray-400 flex justify-center">${team.total_users}</div>
                        <div class="col-span-1 text-gray-500 text-center">${team.last_updated}</div>
                        <div class="col-span-1 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick="editTeam(${team.id})" class="text-blue-400 hover:text-blue-300 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="deleteTeam(${team.id})" class="text-red-400 hover:text-red-300 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2.375 2.375 0 0115.963 21H8.037a2.375 2.375 0 01-2.17-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join("");

        // Don't update count during filtering - only update when explicitly told to
    }

    // Load teams from server
    function loadTeams() {
        console.log("Loading teams from:", API_URL + "/getAllTeams");
        fetch(API_URL + "/getAllTeams")
            .then(r => {
                console.log("Response status:", r.status);
                if (!r.ok) {
                    throw new Error(`HTTP error! status: ${r.status}`);
                }
                return r.json();
            })
            .then(data => {
                console.log("Teams loaded:", data);
                if (data.success && Array.isArray(data.teams)) {
                    allTeams = data.teams;
                    totalTeamCount = data.total_count || data.teams.length;
                    render(allTeams);
                    loadManagersFilter(); // Load managers into filter
                    // Update count badge with total count (not filtered)
                    if (countBadge) countBadge.textContent = totalTeamCount;
                } else {
                    throw new Error(data.message || 'Invalid response format');
                }
            })
            .catch(err => {
                console.error("Failed to load teams:", err);
                teamsList.innerHTML = `<div class="text-red-400 text-center py-10">Failed to load teams: ${err.message}</div>`;
            });
    }

    // Load available managers into dropdown
    function loadManagers() {
        console.log('Loading managers from:', API_URL + "/getAvailableManagers");
        fetch(API_URL + "/getAvailableManagers")
            .then(r => {
                console.log('Managers API response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('Managers API response:', data);
                if (data.success && Array.isArray(data.managers)) {
                    allManagers = data.managers;
                    console.log('Loaded managers:', allManagers);
                    populateManagerDropdown();
                } else {
                    console.error('Failed to load managers:', data.message || 'Invalid response format');
                    // Show error in dropdown
                    if (teamManagerSelect) {
                        teamManagerSelect.innerHTML = '<option value="">Error loading managers</option>';
                    }
                }
            })
            .catch(err => {
                console.error('Error loading managers:', err);
                // Show error in dropdown
                if (teamManagerSelect) {
                    teamManagerSelect.innerHTML = '<option value="">Error loading managers</option>';
                }
            });
    }

    // Populate manager dropdown
    function populateManagerDropdown() {
        console.log('Populating dropdown with managers:', allManagers);
        if (!teamManagerSelect) {
            console.error('teamManagerSelect element not found!');
            return;
        }
        
        teamManagerSelect.innerHTML = '<option value="">Select a manager...</option>';
        
        if (!allManagers || allManagers.length === 0) {
            console.warn('No managers available');
            teamManagerSelect.innerHTML = '<option value="">No managers available</option>';
            return;
        }
        
        // Sort managers alphabetically by name
        const sortedManagers = allManagers.sort((a, b) => a.name.localeCompare(b.name));
        console.log('Sorted managers:', sortedManagers);
        
        sortedManagers.forEach(manager => {
            const option = document.createElement('option');
            option.value = manager.id;
            option.textContent = manager.name;
            teamManagerSelect.appendChild(option);
            console.log('Added manager option:', manager.name);
        });
    }

    // Load managers into filter dropdown
    function loadManagersFilter() {
        if (!Array.isArray(allTeams) || allTeams.length === 0) {
            managerFilter.innerHTML = '<option value="">All Managers</option>';
            return;
        }
        
        const uniqueManagers = [...new Set(allTeams.map(team => team.manager))];
        managerFilter.innerHTML = '<option value="">All Managers</option>';
        uniqueManagers.forEach(manager => {
            managerFilter.innerHTML += `<option value="${manager}">${manager}</option>`;
        });
    }

    // Client-side search + filter
    function applyFilters() {
        let filtered = allTeams;

        const query = searchInput.value.trim().toLowerCase();
        const location = locationFilter.value.toLowerCase();
        const manager = managerFilter.value;

        if (query) {
            filtered = filtered.filter(team =>
                team.team_name.toLowerCase().includes(query) ||
                team.abbreviation.toLowerCase().includes(query) ||
                team.location.toLowerCase().includes(query) ||
                team.manager.toLowerCase().includes(query)
            );
        }

        if (location) {
            filtered = filtered.filter(team => team.location.toLowerCase().includes(location));
        }

        if (manager) {
            filtered = filtered.filter(team => team.manager === manager);
        }

        render(filtered);
    }

    // Search real-time
    searchInput?.addEventListener("input", applyFilters);

    // Apply filters button
    document.getElementById("applyFilters")?.addEventListener("click", () => {
        applyFilters();
        filtersModal.classList.add("hidden");
    });

    // Add/Edit Team
    document.getElementById("saveTeam")?.addEventListener("click", () => {
        const form = document.getElementById("addTeamForm");
        const saveButton = document.getElementById("saveTeam");
        const teamId = saveButton.getAttribute("data-team-id");
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const selectedManagerId = document.getElementById("teamManager").value;
        console.log('Selected manager ID:', selectedManagerId);
        console.log('Team ID (for edit):', teamId);

        const formData = new FormData();
        formData.append("team_name", document.getElementById("teamName").value);
        formData.append("abbreviation", document.getElementById("teamAbbreviation").value);
        formData.append("location", document.getElementById("teamLocation").value);
        formData.append("manager_id", selectedManagerId);
        
        // Debug form data
        console.log('Form data being sent:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Handle file upload
        const logoFile = document.getElementById("teamLogo").files[0];
        if (logoFile) {
            formData.append("logo", logoFile);
        }

        // Determine if we're adding or editing
        if (teamId) {
            // Edit mode
            formData.append("team_id", teamId);
            
            console.log('Updating team with ID:', teamId);
            
            fetch(API_URL + "/updateTeam", {
                method: "POST",
                body: formData
            })
            .then(r => {
                console.log('Update response status:', r.status);
                return r.json();
            })
            .then(res => {
                console.log('Update response:', res);
                if (res.success) {
                    addTeamModal.classList.add("hidden");
                    form.reset();
                    resetModalToAddMode();
                    loadTeams();
                    alert("Team updated successfully!");
                } else {
                    console.error('Update failed:', res);
                    alert("Error: " + res.message);
                }
            })
            .catch(err => {
                console.error('Update error:', err);
                alert("Failed to update team");
            });
        } else {
            // Add mode
            console.log('Adding new team');
            
            fetch(API_URL + "/addTeam", {
                method: "POST",
                body: formData
            })
            .then(r => {
                console.log('Add response status:', r.status);
                return r.json();
            })
            .then(res => {
                console.log('Add response:', res);
                if (res.success) {
                    addTeamModal.classList.add("hidden");
                    form.reset();
                    loadTeams();
                    alert("Team added successfully!");
                } else {
                    console.error('Add failed:', res);
                    alert("Error: " + res.message);
                }
            })
            .catch(err => {
                console.error('Add error:', err);
                alert("Failed to add team");
            });
        }
    });

    // Reset modal to add mode
    function resetModalToAddMode() {
        const modalTitle = addTeamModal.querySelector("h3");
        const saveButton = document.getElementById("saveTeam");
        
        modalTitle.textContent = "Add New Team";
        saveButton.textContent = "Add Team";
        saveButton.removeAttribute("data-team-id");
    }

    // Reset modal when opening for add
    document.getElementById("addTeamBtn")?.addEventListener("click", () => {
        resetModalToAddMode();
        loadManagers(); // Load fresh manager data
        addTeamModal.classList.remove("hidden");
    });

    // Reset modal when canceling
    document.getElementById("cancelAddTeam")?.addEventListener("click", () => {
        resetModalToAddMode();
        addTeamModal.classList.add("hidden");
    });

    // Initialize
    loadTeams();
    loadManagers();

    // Modal event listeners
    document.getElementById("filtersBtn")?.addEventListener("click", () => {
        filtersModal.classList.remove("hidden");
    });

    document.getElementById("cancelFilters")?.addEventListener("click", () => {
        filtersModal.classList.add("hidden");
    });

    // Close modals when clicking outside
    window.addEventListener("click", (e) => {
        if (e.target === filtersModal) {
            filtersModal.classList.add("hidden");
        }
        if (e.target === addTeamModal) {
            addTeamModal.classList.add("hidden");
        }
    });

    // Edit team function (global)
    window.editTeam = function(id) {
        const team = allTeams.find(t => t.id == id);
        if (!team) {
            alert("Team not found");
            return;
        }

        console.log('Editing team:', team);
        console.log('Current managers:', allManagers);

        // Load managers first, then populate form
        loadManagers();
        
        // Use setTimeout to ensure managers are loaded before setting values
        setTimeout(() => {
            // Pre-populate the add team form with existing data
            document.getElementById("teamName").value = team.team_name;
            document.getElementById("teamAbbreviation").value = team.abbreviation;
            document.getElementById("teamLocation").value = team.location;
            
            // Set the current manager using manager_id directly
            console.log('Setting manager with ID:', team.manager_id);
            if (team.manager_id && teamManagerSelect) {
                teamManagerSelect.value = team.manager_id;
                console.log('Set dropdown to manager ID:', team.manager_id);
            } else {
                console.warn('No manager ID found for team:', team);
            }

            // Show the modal
            addTeamModal.classList.remove("hidden");

            // Change the modal title and button text
            const modalTitle = addTeamModal.querySelector("h3");
            const saveButton = document.getElementById("saveTeam");
            
            modalTitle.textContent = "Edit Team";
            saveButton.textContent = "Update Team";
            
            // Store the team ID for updating
            saveButton.setAttribute("data-team-id", id);
        }, 500); // Increased timeout to ensure managers are loaded
    };

    // Delete team function (global)
    window.deleteTeam = function(id) {
        const team = allTeams.find(t => t.id == id);
        if (!team) {
            alert("Team not found");
            return;
        }

        if (confirm(`Are you sure you want to delete team "${team.team_name}"? This action cannot be undone.`)) {
            const formData = new FormData();
            formData.append("team_id", id);

            fetch(API_URL + "/deleteTeam", {
                method: "POST",
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    loadTeams();
                    alert("Team deleted successfully!");
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(() => alert("Failed to delete team"));
        }
    };

    // Optional: Allow pressing Enter in search
    searchInput?.addEventListener("keypress", e => {
        if (e.key === "Enter") applyFilters();
    });
});