document.addEventListener("DOMContentLoaded", function () {
    const inviteLinkInput = document.getElementById("invite_link");
    const inviteLinkForm = document.getElementById("invite-link-form");

    inviteLinkInput.addEventListener("paste", function (e) {
        setTimeout(function () {
            if (inviteLinkInput.value.trim() !== '') {
                inviteLinkForm.submit();
            }
        }, 100);
    });

    inviteLinkInput.addEventListener("input", function () {
        if (inviteLinkInput.value.trim() !== '') {
            inviteLinkForm.submit();
        }
    });
});