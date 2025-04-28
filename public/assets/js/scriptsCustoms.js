document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('accordionSidebar');
    const toggleBtn = document.getElementById('sidebarToggleTop');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('show');
        });
    }
});

    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });