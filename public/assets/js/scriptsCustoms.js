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
        const previewWrapper = document.getElementById('preview-wrapper');
        const previewImg = document.getElementById('preview');
        const clearBtn = document.getElementById('clear-preview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewWrapper.style.display = 'block';
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearPreview() {
        const input = document.getElementById('imagen');
        const previewImg = document.getElementById('preview');
        const previewWrapper = document.getElementById('preview-wrapper');

        input.value = "";
        previewImg.src = "#";
        previewWrapper.style.display = 'none';
    }

