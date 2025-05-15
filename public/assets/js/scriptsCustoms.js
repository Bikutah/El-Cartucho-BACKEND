
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

let selectedFiles = [];

function previewMultipleImages(event, previewId) {
    const container = document.getElementById(previewId);
    const newFiles = Array.from(event.target.files);

    // Agregar las nuevas imágenes a la lista temporal (sin sobreescribir)
    selectedFiles = [...selectedFiles, ...newFiles];

    // Limitar a 5 imágenes (esto es solo visual)
    if (selectedFiles.length > 5) {
        selectedFiles = selectedFiles.slice(0, 5);
        alert('Solo puedes subir un máximo de 5 imágenes.');
    }

    // Limpiar el contenedor
    container.innerHTML = '';

    // Renderizar todas las previews acumuladas
    selectedFiles.forEach(file => {
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.maxWidth = '100px';
            img.style.maxHeight = '100px';
            img.classList.add('rounded', 'border', 'me-2', 'mb-2');
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

