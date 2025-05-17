


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

