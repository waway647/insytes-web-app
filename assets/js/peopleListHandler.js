document.addEventListener("DOMContentLoaded", function () {

    
    fetch(`http://localhost/github/insytes-web-app/index.php/Team/PeopleController/getTeamMembers?team_id=${TEAM_ID}`)
        .then(response => response.json())
        .then(data => {
            const peopleList = document.querySelector(".people-list");
            peopleList.innerHTML = ""; // optional: clear existing items
            data.forEach(member => {
                const listItem = document.createElement("div");
                const isCurrentUser = member.id === USER_ID;
                const displayName = isCurrentUser 
                    ? `${member.last_name}, ${member.first_name} <span class="text-gray-400 text-sm">(Me)</span>` 
                    : `${member.last_name}, ${member.first_name}`;

                listItem.className = "flex items-center justify-between bg-transparent hover:bg-[#1E1E1E] px-4 py-3 transition";
                listItem.innerHTML = `
                    <div>
                        <p class="font-medium text-white">${displayName}</p>
                        <p class="text-sm text-gray-400">${member.role}</p> 
                    </div>
                    <button class="p-1 hover:bg-[#2A2A2A] rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                        </svg>
                    </button>
                `;
                peopleList.appendChild(listItem);
            });
        })
        .catch(error => console.error("Error fetching team members:", error));

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

        fetch(`http://localhost/github/insytes-web-app/index.php/Team/PeopleController/getTotalTeamMembers?team_id=${TEAM_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.total_members) {
                document.querySelector(".team-member-count").textContent = data.total_members;
            } else {
            console.error("No team name found:", data);
            }
        })
        .catch(error => console.error("Error fetching team name:", error));

});
