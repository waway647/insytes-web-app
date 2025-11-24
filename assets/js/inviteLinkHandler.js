document.addEventListener("DOMContentLoaded", function () {
    const inviteLinkButton = document.querySelectorAll(".invite-link");
    const inviteLinkInput = document.getElementById("inviteLink");
    const copyLinkButton = document.getElementById("copyLinkButton");

    inviteLinkButton.forEach(button => {
        button.addEventListener("click", function () {
            console.log('Invite button clicked, USER_ID:', USER_ID, 'ROLE:', ROLE);
            
            fetch(`http://localhost/GitHub/insytes-web-app/index.php/Team/InvitationController/get_invite_link?user_id=${USER_ID}&role=${ROLE}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text(); // Get as text first to see raw response
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed response data:', data);
                        if (data.success) {
                            inviteLinkInput.value = data.invite_link;
                            console.log('Invite link generated:', data.invite_link);
                        } else {
                            console.error('API Error:', data.error);
                            alert("Failed to generate invite link: " + (data.error || "Unknown error"));
                        }
                    } catch (jsonError) {
                        console.error('JSON parsing error:', jsonError);
                        console.error('Response was not valid JSON:', text);
                        alert("Server error: Invalid response format");
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert("Network error: " + error.message);
                });
        });
    }); 

    copyLinkButton.addEventListener("click", async () => {
            try {
                await navigator.clipboard.writeText(inviteLinkInput.value);
                alert("Invite link copied to clipboard!");
            } catch (err) {
                console.error("Failed to copy text:", err);
            }
        });
});
