document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('team_logo');
    const labelText = document.getElementById('teamLogoText');
    const labelIcon = document.getElementById('uploadIcon');
    const originalIconSrc = labelIcon.src;

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        labelText.textContent = file ? file.name : 'Attach Logo';
        labelIcon.src = file ? URL.createObjectURL(file) : originalIconSrc;
    });
});