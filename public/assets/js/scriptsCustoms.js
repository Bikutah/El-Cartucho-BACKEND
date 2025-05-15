


document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
    });
});


    document.addEventListener('DOMContentLoaded', function () {
        const input = document.querySelector('input[type="file"][name="imagenes[]"]');
        const preview = document.createElement('div');
        preview.id = 'preview-imagenes';
        preview.className = 'd-flex flex-wrap gap-2 mt-2';
        input.parentNode.appendChild(preview);

        input.addEventListener('change', function () {
            preview.innerHTML = '';
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'border rounded';
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    });