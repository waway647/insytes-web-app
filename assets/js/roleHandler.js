
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.role-link').forEach(link => {
    link.addEventListener('click', function(event) {

      const role = this.parentElement.querySelector('.role-text').textContent.trim();

      // Send role to backend via AJAX
      fetch("http://localhost/github/insytes-web-app/index.php/User/NewUserController/setUserRole", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "role=" + encodeURIComponent(role)
      })
      .catch(error => console.error("Error saving role:", error));
    });
  });
});

