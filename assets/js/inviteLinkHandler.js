document.addEventListener("DOMContentLoaded", function () {
    const generateLinkButton = document.getElementById("generate-link");
    const copyLinkButton = document.getElementById("copyLinkButton");
    const inviteLinkInput = document.getElementById("inviteLink").value;


    generateLinkButton.addEventListener("click", function () {
        // Simulate generating a unique invite link
        
    });

    copyLinkButton.addEventListener("click", async () => {
        try {
            await navigator.clipboard.writeText(inviteLinkInput);
            alert("Invite link copied to clipboard!");
        } catch (err) {
            console.error("Failed to copy text:", err);
        }
    });
});
