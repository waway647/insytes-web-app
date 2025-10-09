document.addEventListener("DOMContentLoaded", function () {
    const inviteLinkButton = document.querySelectorAll(".invite-link");
    const inviteLinkInput = document.getElementById("inviteLink");
    const copyLinkButton = document.getElementById("copyLinkButton");

    inviteLinkButton.forEach(button => {
        button.addEventListener("click", function () {

            fetch(`http://localhost/github/insytes-web-app/index.php/Team/InvitationController/get_invite_link?user_id=${USER_ID}&role=${ROLE}`)
                .then(response => response.json())
                .then(data => { 
                    if (data.success) {
                        inviteLinkInput.value = data.invite_link;
                    } else {
                        alert("Failed to generate invite link.");
                    }
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
