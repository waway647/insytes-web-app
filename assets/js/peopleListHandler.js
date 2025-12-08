document.addEventListener("DOMContentLoaded", function () {
    let allMembers = []; // Store all members for filtering
    const peopleList = document.querySelector(".people-list");
    const searchInput = document.getElementById('searchInput');

    // <-- CONFIG: update this to your real endpoint if different -->
    const REMOVE_URL = 'http://localhost/github/insytes-web-app/index.php/Team/PeopleController/remove_from_team';
    // If you need to include a CSRF token header, set like:
    // const CSRF_TOKEN = window.CSRF_TOKEN || null;

    // Helper: tiny toast
    function showToast(msg, timeout = 2200) {
        const t = document.createElement('div');
        t.textContent = msg;
        t.className = 'fixed bottom-6 right-6 z-[9999] bg-[#111] text-white px-4 py-2 rounded shadow';
        document.body.appendChild(t);
        setTimeout(() => t.remove(), timeout);
    }

    // Function to render people list (robust + debug)
    function renderPeopleList(members) {
        peopleList.innerHTML = ""; // Clear existing items

        // safety
        if (!Array.isArray(members) || members.length === 0) {
            peopleList.innerHTML = `
                <div class="text-center py-8 text-gray-400">
                    <p>No team members found</p>
                </div>
            `;
            console.log('renderPeopleList: no members');
            return;
        }

        // Normalize & protect ROLE, handle null/undefined
        const roleRaw = (typeof ROLE !== 'undefined' && ROLE !== null) ? String(ROLE) : '';
        const roleNorm = roleRaw.trim().toLowerCase();
        const isCoach = roleNorm === 'coach';

        console.log('renderPeopleList: ROLE=', ROLE, '-> norm=', roleNorm, 'isCoach=', isCoach);
        console.log('renderPeopleList: USER_ID=', USER_ID, 'members.length=', members.length);

        members.forEach(member => {
            if (!member || typeof member.id === 'undefined') {
                console.warn('Skipping invalid member', member);
                return;
            }

            // Create container
            const listItem = document.createElement('div');
            listItem.className = "flex items-center justify-between bg-transparent hover:bg-[#1E1E1E] px-4 py-3 transition relative";

            // Determine current user (normalize types)
            const isCurrentUser = String(member.id) === String(USER_ID);

            // Left content
            const left = document.createElement('div');
            const nameP = document.createElement('p');
            nameP.className = 'font-medium text-white';
            // include (Me) span if current user
            if (isCurrentUser) {
                nameP.innerHTML = `${member.last_name || ''}, ${member.first_name || ''} <span class="text-gray-400 text-sm">(Me)</span>`;
            } else {
                nameP.textContent = `${member.last_name || ''}, ${member.first_name || ''}`;
            }
            const roleP = document.createElement('p');
            roleP.className = 'text-sm text-gray-400';
            roleP.textContent = member.role || '';
            left.appendChild(nameP);
            left.appendChild(roleP);

            listItem.appendChild(left);

            // Only create and append the option button when user is coach and not current user
            if (isCoach && !isCurrentUser) {
                const btn = document.createElement('button');
                btn.className = 'option-btn p-1 hover:bg-[#2A2A2A] rounded-full';
                btn.setAttribute('data-user-id', String(member.id));
                btn.setAttribute('aria-haspopup', 'true');
                btn.setAttribute('aria-expanded', 'false');
                // build svg icon
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                    </svg>
                `;
                listItem.appendChild(btn);
            }

            peopleList.appendChild(listItem);

            // Debug per-member
            console.log('renderPeopleList: appended member', member.id, 'isCurrentUser=', isCurrentUser, 'showButton=', (isCoach && !isCurrentUser));
        });
    }

    // Function to filter members based on search query
    function filterMembers(query) {
        if (!query || !query.trim()) {
            return allMembers; // Return all members if search is empty
        }

        const searchTerm = query.toLowerCase();
        return allMembers.filter(member => {
            const fullName = `${member.first_name} ${member.last_name}`.toLowerCase();
            const reverseName = `${member.last_name} ${member.first_name}`.toLowerCase();
            const role = (member.role || '').toLowerCase();
            
            return fullName.includes(searchTerm) || 
                   reverseName.includes(searchTerm) || 
                   role.includes(searchTerm);
        });
    }

    // Search input event listener
    searchInput.addEventListener('input', function(e) {
        const filteredMembers = filterMembers(e.target.value);
        renderPeopleList(filteredMembers);
    });

    // Clear search when input is cleared (Escape)
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Escape') {
            e.target.value = '';
            renderPeopleList(allMembers);
        }
    });

    // Fetch team members
    fetch(`http://localhost/github/insytes-web-app/index.php/Team/PeopleController/getTeamMembers?team_id=${TEAM_ID}`)
        .then(response => response.json())
        .then(data => {
            allMembers = data; // Store all members
            renderPeopleList(allMembers); // Initial render
        })
        .catch(error => console.error("Error fetching team members:", error));

    // Fetch team name
    fetch(`http://localhost/github/insytes-web-app/index.php/Team/PeopleController/getTeamName?team_id=${TEAM_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.team_name) {
                document.querySelectorAll(".team-name").forEach(el => el.textContent = data.team_name);
            } else {
                console.error("No team name found:", data);
            }
        })
        .catch(error => console.error("Error fetching team name:", error));

    // Fetch total team members
    fetch(`http://localhost/github/insytes-web-app/index.php/Team/PeopleController/getTotalTeamMembers?team_id=${TEAM_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.total_members) {
                document.querySelector(".team-member-count").textContent = data.total_members;
            } else {
                console.error("No member count found:", data);
            }
        })
        .catch(error => console.error("Error fetching member count:", error));


    /* -----------------------------
       Dropdown + Remove handling
       -----------------------------*/
    let openDropdown = null;
    let outsideClickListener = null;

    // When an option button is clicked, open a small dropdown menu
    peopleList.addEventListener('click', function(e) {
        const btn = e.target.closest('.option-btn');
        if (!btn) return;

        // close existing
        if (openDropdown) {
            openDropdown.remove();
            openDropdown = null;
        }

        const userId = btn.dataset.userId;
        // create dropdown element
        const dd = document.createElement('div');
        dd.className = 'absolute z-50 bg-[#0b0b0b] border border-white/6 rounded-md py-1 shadow-lg min-w-[180px]';
        dd.innerHTML = `
            <button class="w-full text-left px-4 py-2 text-sm text-white hover:bg-red-800 cursor-pointer transition-colors remove-member" data-user-id="${userId}" type="button">
                Remove from team
            </button>
        `;
        document.body.appendChild(dd);
        openDropdown = dd;

        // position near the button (simple placement)
        const rect = btn.getBoundingClientRect();
        const left = rect.right - dd.offsetWidth;
        const top = rect.bottom + 6;
        dd.style.left = Math.max(8, left + window.scrollX) + 'px';
        dd.style.top = Math.max(8, top + window.scrollY) + 'px';

        // close dropdown when clicking outside (set after a tick to avoid immediate close)
        outsideClickListener = function(ev) {
            if (!dd.contains(ev.target) && ev.target !== btn) {
                dd.remove();
                openDropdown = null;
                document.removeEventListener('click', outsideClickListener);
            }
        };
        setTimeout(() => document.addEventListener('click', outsideClickListener), 0);
    });

    // Handle the remove action from the dropdown
    document.body.addEventListener('click', async function(e) {
        const rm = e.target.closest('.remove-member');
        if (!rm) return;
        const uid = rm.dataset.userId;
        const member = allMembers.find(m => String(m.id) === String(uid));
        const name = member ? `${member.first_name} ${member.last_name}` : 'this user';

        // extra confirmation
        const ok = confirm(`Remove ${name} from this team?\n\nThis action will unassign the user from the team. Are you sure?`);
        if (!ok) {
            if (openDropdown) { openDropdown.remove(); openDropdown = null; }
            return;
        }

        try {
            // send POST to controller (we send JSON here; controller example below supports JSON)
            const resp = await fetch(REMOVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // 'X-CSRF-Token': CSRF_TOKEN // uncomment if you need to include a CSRF token header
                },
                body: JSON.stringify({ user_id: uid, team_id: TEAM_ID })
            });

            if (!resp.ok) throw new Error('Network response was not ok');

            const json = await resp.json();
            if (json.success) {
                // remove from local list + re-render filtered view
                allMembers = allMembers.filter(m => String(m.id) !== String(uid));
                renderPeopleList(filterMembers(searchInput.value));

                // update displayed member count if element exists
                const countEl = document.querySelector(".team-member-count");
                if (countEl) {
                    const cur = parseInt(countEl.textContent || '0', 10);
                    countEl.textContent = Math.max(0, cur - 1);
                }

                showToast('Member removed from team');
            } else {
                alert(json.message || 'Failed to remove member');
            }

        } catch (err) {
            console.error('Error removing member:', err);
            alert('There was a problem removing the member. See console for details.');
        } finally {
            if (openDropdown) { openDropdown.remove(); openDropdown = null; }
            if (outsideClickListener) { document.removeEventListener('click', outsideClickListener); outsideClickListener = null; }
        }
    });

});
