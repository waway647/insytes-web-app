document.addEventListener("DOMContentLoaded", function () {
    const openModalButton = document.getElementById("openModal");
    const closeModalButton = document.getElementById("closeModal");
    const inviteModal = document.getElementById("inviteModal");

    openModalButton.addEventListener("click", function () {
        inviteModal.hidden = false;
    });

    closeModalButton.addEventListener("click", function () {
        inviteModal.hidden = true;
    });

});
