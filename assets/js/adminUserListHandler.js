// assets/js/adminUserListHandler.js - FULLY WORKING (Search + Filter + Add + Delete)
document.addEventListener("DOMContentLoaded", function () {
    const usersList = document.querySelector(".users-list");
    const countBadge = document.querySelector(".w-9.h-9");
    const searchInput = document.getElementById("searchInput");
    const roleFilter = document.getElementById("roleFilter");
    const teamFilter = document.getElementById("teamFilter");

    // Modals
    const filtersModal = document.getElementById("filtersModal");
    const addUserModal = document.getElementById("addUserModal");

    // API URL - Updated to match your CodeIgniter setup
    const API_URL = "/github/insytes-web-app/index.php/Admin/UserController";

    let allUsers = []; // Store all users for client-side filtering

    // Role color mapping
    const roleColors = {
        admin: "text-purple-400",
        coach: "text-green-400",
        player: "text-cyan-400"
    };

    // Render users
    function render(users) {
        if (!usersList) return;

        if (users.length === 0) {
            usersList.innerHTML = `
                <div class="px-8 py-20 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-gray-400 text-lg">No users found</p>
                </div>`;
            if (countBadge) countBadge.textContent = "0";
            return;
        }

        usersList.innerHTML = users.map(user => {
            const roleColor = roleColors[user.role.toLowerCase()] || "text-gray-400";
            
            // Format date properly using only updated_at
            let formattedDate = 'N/A';
            if (user.updated_at && user.updated_at !== '0000-00-00 00:00:00') {
                try {
                    // Parse the MySQL datetime string as local time (not UTC)
                    const dateStr = user.updated_at.replace(' ', 'T');
                    const date = new Date(dateStr);
                    
                    if (!isNaN(date.getTime())) {
                        const now = new Date();
                        
                        // Reset time components to midnight for accurate day comparison
                        const todayMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                        const updateMidnight = new Date(date.getFullYear(), date.getMonth(), date.getDate());
                        
                        // Calculate difference in days
                        const daysDiff = Math.floor((todayMidnight - updateMidnight) / (1000 * 60 * 60 * 24));
                        
                        if (daysDiff === 0) {
                            formattedDate = 'Today';
                        } else if (daysDiff === 1) {
                            formattedDate = 'Yesterday';
                        } else {
                            // Format as "Dec 6" for this year, "Dec 6, 2024" for other years
                            if (date.getFullYear() === now.getFullYear()) {
                                formattedDate = date.toLocaleDateString('en-US', { 
                                    month: 'short', 
                                    day: 'numeric'
                                });
                            } else {
                                formattedDate = date.toLocaleDateString('en-US', { 
                                    month: 'short', 
                                    day: 'numeric',
                                    year: 'numeric'
                                });
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Date parsing error for user:', user.id, user.updated_at);
                    formattedDate = 'N/A';
                }
            }
            
            // Get team name from database JOIN
            const teamName = user.team_name || 'No Team';

            return `
                <div class="px-8 py-5 hover:bg-gray-800/30 transition duration-200 border-b border-[#2a2a2a]">
                    <div class="grid grid-cols-12 gap-4 text-sm">
                        <div class="col-span-3 text-gray-400">${user.email}</div>
                        <div class="col-span-2 text-white text-center">${user.first_name}</div>
                        <div class="col-span-2 text-white text-center">${user.last_name}</div>
                        <div class="col-span-2 text-gray-400 text-center">${teamName}</div>
                        <div class="col-span-1 text-center">
                            <span class="${roleColor} font-medium">${user.role}</span>
                        </div>
                        <div class="col-span-1 text-gray-500 text-center">${formattedDate}</div>
                        <div class="col-span-1 text-center">
                            <div class="flex justify-center gap-2">
                                <button onclick="editUser(${user.id})" class="text-blue-400 hover:text-blue-300 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="deleteUser(${user.id})" class="text-red-400 hover:text-red-300 transition">
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

        if (countBadge) countBadge.textContent = users.length;
    }

    // Load users from server
    function loadUsers() {
        console.log("Loading users from:", API_URL + "/getAllUsers");
        fetch(API_URL + "/getAllUsers")
            .then(r => {
                console.log("Response status:", r.status);
                if (!r.ok) {
                    throw new Error(`HTTP error! status: ${r.status}`);
                }
                return r.json();
            })
            .then(data => {
                console.log("Users loaded:", data);
                allUsers = data;
                render(allUsers);
            })
            .catch(err => {
                console.error("Failed to load users:", err);
                usersList.innerHTML = `<div class="text-red-400 text-center py-10">Failed to load users: ${err.message}</div>`;
            });
    }

    // Load teams into filter dropdown and user form dropdown
    function loadTeams() {
        console.log("Loading teams from:", API_URL + "/getTeams");
        fetch(API_URL + "/getTeams")
            .then(r => {
                console.log("Teams response status:", r.status);
                if (!r.ok) {
                    throw new Error(`HTTP error! status: ${r.status}`);
                }
                return r.json();
            })
            .then(teams => {
                console.log("Teams loaded:", teams);
                
                // Populate filter dropdown
                teamFilter.innerHTML = '<option value="">All Teams</option>';
                
                // Populate user form dropdown
                const userTeamSelect = document.getElementById('userTeam');
                if (userTeamSelect) {
                    userTeamSelect.innerHTML = '<option value="">Select a team (optional)</option><option value="none">No Team (let user choose)</option>';
                }
                
                teams.forEach(t => {
                    teamFilter.innerHTML += `<option value="${t.team_name}">${t.team_name}</option>`;
                    if (userTeamSelect) {
                        userTeamSelect.innerHTML += `<option value="${t.id}">${t.team_name}</option>`;
                    }
                });
            })
            .catch(err => {
                console.error("Failed to load teams:", err);
            });
    }

    // Client-side search + filter
    function applyFilters() {
        let filtered = allUsers;

        const query = searchInput.value.trim().toLowerCase();
        const role = roleFilter.value.toLowerCase();
        const team = teamFilter.value;

        if (query) {
            filtered = filtered.filter(u =>
                u.email.toLowerCase().includes(query) ||
                u.first_name.toLowerCase().includes(query) ||
                u.last_name.toLowerCase().includes(query) ||
                (u.team_name && u.team_name.toLowerCase().includes(query)) ||
                u.role.toLowerCase().includes(query)
            );
        }

        if (role) {
            filtered = filtered.filter(u => u.role.toLowerCase() === role);
        }

        if (team) {
            filtered = filtered.filter(u => u.team === team);
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

    // Add/Edit User
    document.getElementById("saveUser")?.addEventListener("click", () => {
        const form = document.getElementById("addUserForm");
        const saveButton = document.getElementById("saveUser");
        const userId = saveButton.getAttribute("data-user-id");
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData();
        formData.append("first_name", document.getElementById("firstName").value);
        formData.append("last_name", document.getElementById("lastName").value);
        formData.append("email", document.getElementById("email").value);
        formData.append("role", document.getElementById("role").value.toLowerCase());
        
        // Add team_id if selected
        const teamId = document.getElementById("userTeam").value;
        if (teamId) {
            formData.append("team_id", teamId);
        }
        
        // Only append password if it's not empty (for edit mode)
        const password = document.getElementById("password").value;
        if (password.trim() !== "") {
            formData.append("password", password);
        }

        // Determine if we're adding or editing
        if (userId) {
            // Edit mode
            formData.append("user_id", userId);
            fetch(API_URL + "/updateUser", {
                method: "POST",
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    addUserModal.classList.add("hidden");
                    form.reset();
                    resetModalToAddMode();
                    loadUsers();
                    alert("User updated successfully!");
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(() => alert("Failed to update user"));
        } else {
            // Add mode - email and password are required
            if (!document.getElementById("email").value.trim()) {
                alert("Email is required for new users");
                return;
            }
            if (!document.getElementById("password").value.trim()) {
                alert("Password is required for new users");
                return;
            }
            
            // Show processing modal
            document.getElementById("processingModal").classList.remove("hidden");
            
            fetch(API_URL + "/addUser", {
                method: "POST",
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                // Hide processing modal
                document.getElementById("processingModal").classList.add("hidden");
                
                if (res.success) {
                    addUserModal.classList.add("hidden");
                    form.reset();
                    loadUsers();
                    
                    // Show success modal immediately after processing completes
                    const emailEl = document.getElementById("displayEmail");
                    const passwordEl = document.getElementById("displayPassword");
                    emailEl.textContent = res.data.email;
                    passwordEl.dataset.secret = res.data.password;
                    passwordEl.textContent = "••••••••";
                    document.getElementById("successModal").classList.remove("hidden");
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(err => {
                // Hide processing modal on error
                document.getElementById("processingModal").classList.add("hidden");
                alert("Failed to add user: " + err.message);
            });
        }
    });

    // Reset modal to add mode
    function resetModalToAddMode() {
        const modalTitle = addUserModal.querySelector("h3");
        const saveButton = document.getElementById("saveUser");
        const passwordField = document.getElementById("password");
        const passwordHelp = document.getElementById("passwordHelp");
        
        modalTitle.textContent = "Add New User";
        saveButton.textContent = "Add User";
        saveButton.removeAttribute("data-user-id");
        
        // Reset password field for add mode
        passwordField.placeholder = "Enter temporary password";
        passwordField.required = true;
        if (passwordHelp) {
            passwordHelp.textContent = "(user will be prompted to change on first login)";
        }
    }

    // Reset modal when opening for add
    document.getElementById("addUserBtn")?.addEventListener("click", () => {
        resetModalToAddMode();
        addUserModal.classList.remove("hidden");
    });

    // Reset modal when canceling
    document.getElementById("cancelAddUser")?.addEventListener("click", () => {
        resetModalToAddMode();
        addUserModal.classList.add("hidden");
    });

    // Delete User
    window.deleteUser = function(id) {
        if (!confirm("Delete this user permanently?")) return;

        const formData = new FormData();
        formData.append("user_id", id);

        fetch(API_URL + "/deleteUser", {
            method: "POST",
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                loadUsers();
                alert("User deleted");
            } else {
                alert("Error: " + res.message);
            }
        });
    };

    // Edit User
    window.editUser = function(id) {
        // Find the user in allUsers array
        const user = allUsers.find(u => u.id == id);
        if (!user) {
            alert("User not found");
            return;
        }

        // Pre-populate the add user form with existing data
        document.getElementById("firstName").value = user.first_name;
        document.getElementById("lastName").value = user.last_name;
        document.getElementById("email").value = user.email;
        document.getElementById("role").value = user.role;
        document.getElementById("password").value = ""; // Clear password for security
        
        // Set team if available (need to find team ID by name)
        const userTeamSelect = document.getElementById("userTeam");
        if (userTeamSelect && user.team_name && user.team_name !== 'No Team') {
            // Find the option with matching team name
            for (let option of userTeamSelect.options) {
                if (option.text === user.team_name) {
                    option.selected = true;
                    break;
                }
            }
        }
        
        // Update password field placeholder and help text
        const passwordField = document.getElementById("password");
        const passwordHelp = document.getElementById("passwordHelp");
        passwordField.placeholder = "Leave empty to keep current password";
        passwordField.required = false;
        if (passwordHelp) {
            passwordHelp.textContent = "(leave empty to keep current)";
        }

        // Show the modal
        addUserModal.classList.remove("hidden");

        // Change the modal title and button text
        const modalTitle = addUserModal.querySelector("h3");
        const saveButton = document.getElementById("saveUser");
        
        modalTitle.textContent = "Edit User";
        saveButton.textContent = "Update User";
        
        // Store the user ID for updating
        saveButton.setAttribute("data-user-id", id);
    };

    // Initialize
    loadUsers();
    loadTeams();

    // Modal event listeners
    document.getElementById("filtersBtn")?.addEventListener("click", () => {
        filtersModal.classList.remove("hidden");
    });

    document.getElementById("cancelFilters")?.addEventListener("click", () => {
        filtersModal.classList.add("hidden");
    });

    document.getElementById("cancelAddUser")?.addEventListener("click", () => {
        resetModalToAddMode();
        addUserModal.classList.add("hidden");
    });

    // Close modals when clicking outside
    window.addEventListener("click", (e) => {
        if (e.target === filtersModal) {
            filtersModal.classList.add("hidden");
        }
        if (e.target === addUserModal) {
            addUserModal.classList.add("hidden");
        }
    });

    // Optional: Allow pressing Enter in search
    searchInput?.addEventListener("keypress", e => {
        if (e.key === "Enter") applyFilters();
    });

    // Success Modal Handlers
    document.getElementById("closeSuccessModal")?.addEventListener("click", () => {
        document.getElementById("successModal").classList.add("hidden");
    });

    // Copy both credentials at once
    document.getElementById("copyCredentials")?.addEventListener("click", function() {
        const email = document.getElementById("displayEmail").textContent;
        const passwordEl = document.getElementById("displayPassword");
        const password = passwordEl.dataset.secret || passwordEl.textContent;
        const payload = `Email: ${email}\nPassword: ${password}`;

        navigator.clipboard.writeText(payload).then(() => {
            this.classList.add("ring-2", "ring-green-400");
            setTimeout(() => this.classList.remove("ring-2", "ring-green-400"), 1200);
        }).catch(() => {
            alert("Failed to copy credentials to clipboard");
        });
    });

    // Toggle password visibility in success modal
    document.getElementById("toggleSuccessPassword")?.addEventListener("click", function() {
        const passwordEl = document.getElementById("displayPassword");
        const eyeClosed = document.getElementById("success-eye-closed");
        const eyeOpen = document.getElementById("success-eye-open");
        const isHidden = passwordEl.textContent.startsWith("•");
        if (isHidden) {
            passwordEl.textContent = passwordEl.dataset.secret || "";
            eyeClosed.classList.add("hidden");
            eyeOpen.classList.remove("hidden");
        } else {
            passwordEl.textContent = "••••••••";
            eyeClosed.classList.remove("hidden");
            eyeOpen.classList.add("hidden");
        }
    });

    // Generate email button functionality
    document.getElementById("generateEmail")?.addEventListener("click", function() {
        const firstName = document.getElementById("firstName").value.trim().toLowerCase().replace(/\s+/g, "");
        const lastName = document.getElementById("lastName").value.trim().toLowerCase().replace(/\s+/g, "");
        
        if (!firstName || !lastName) {
            alert("Please enter first name and last name before generating email");
            return;
        }
        
        const generatedEmail = `${firstName}.${lastName}@temp.insytes.com`;
        document.getElementById("email").value = generatedEmail;
    });
    
    // Email visibility toggle functionality
    document.getElementById("toggle-email")?.addEventListener("click", function() {
        const emailInput = document.getElementById("email");
        const eyeClosed = document.getElementById("email-eye-closed");
        const eyeOpen = document.getElementById("email-eye-open");
        
        // Toggle email input type
        if (emailInput.type === "email") {
            emailInput.type = "text";
            eyeClosed.classList.add("hidden");
            eyeOpen.classList.remove("hidden");
        } else {
            emailInput.type = "email";
            eyeClosed.classList.remove("hidden");
            eyeOpen.classList.add("hidden");
        }
    });
    
    // Generate password button functionality
    document.getElementById("generatePassword")?.addEventListener("click", function() {
        const randomNumber = Math.floor(Math.random() * 9000) + 1000; // 4-digit number between 1000-9999
        const generatedPassword = `temp${randomNumber}`;
        document.getElementById("password").value = generatedPassword;
    });
    
    // Password visibility toggle functionality
    document.getElementById("toggle-password")?.addEventListener("click", function() {
        const passwordInput = document.getElementById("password");
        const eyeClosed = document.getElementById("eye-closed");
        const eyeOpen = document.getElementById("eye-open");
        
        // Toggle password input type
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeClosed.classList.add("hidden");
            eyeOpen.classList.remove("hidden");
        } else {
            passwordInput.type = "password";
            eyeClosed.classList.remove("hidden");
            eyeOpen.classList.add("hidden");
        }
    });

    // Close success modal when clicking outside
    window.addEventListener("click", (e) => {
        const successModal = document.getElementById("successModal");
        if (e.target === successModal) {
            successModal.classList.add("hidden");
        }
    });
});