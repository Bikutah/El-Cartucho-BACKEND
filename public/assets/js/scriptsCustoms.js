document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('accordionSidebar');
    const toggleBtn = document.getElementById('sidebarToggleTop');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('show');
        });
    }
});

