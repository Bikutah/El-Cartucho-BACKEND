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

    function previewImagen(event) {
        const input = event.target;
        const preview = document.getElementById('preview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

