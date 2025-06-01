document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipSelectors = '[data-bs-toggle="tooltip"], button[title], a[title], span[title]';
    const tooltipTriggerList = [].slice.call(document.querySelectorAll(tooltipSelectors));

    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Marcar el enlace activo en la navegación
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar .nav-link, .offcanvas-sidebar-custom .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || 
            (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
        }
    });
    
    // Cerrar automáticamente las alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
});
