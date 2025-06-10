@extends('layouts.app')
@section('title', 'El Cartucho - Dashboard')
@section('content')

<!-- Stats Cards Grid - Completamente Responsive -->
<div class="stats-grid">
    <div class="stat-card-wrapper">
        <div class="stat-card primary">
            <div class="stat-card-content">
                <div class="stat-info">
                    <div class="stat-label">Ventas Hoy</div>
                    <div class="stat-value" id="ventasHoy">
                        <div class="loading-skeleton"></div>
                    </div>
                    <!--<div class="stat-trend positive d-none d-sm-block">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12% vs ayer</span>
                    </div>-->
                </div>
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card-wrapper">
        <div class="stat-card secondary">
            <div class="stat-card-content">
                <div class="stat-info">
                    <div class="stat-label">Ventas Este Mes</div>
                    <div class="stat-value" id="ventasMes">
                        <div class="loading-skeleton"></div>
                    </div>
                    <!--<div class="stat-trend positive d-none d-sm-block">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8% vs mes anterior</span>
                    </div>-->
                </div>
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card-wrapper">
        <div class="stat-card tertiary">
            <div class="stat-card-content">
                <div class="stat-info">
                    <div class="stat-label">Total Productos</div>
                    <div class="stat-value" id="totalProductos">
                        <div class="loading-skeleton"></div>
                    </div>
                    <!--<div class="stat-trend neutral d-none d-sm-block">
                        <i class="fas fa-box"></i>
                        <span>En inventario</span>
                    </div>-->
                </div>
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card-wrapper">
        <div class="stat-card accent">
            <div class="stat-card-content">
                <div class="stat-info">
                    <div class="stat-label">Pedidos Pendientes</div>
                    <div class="stat-value" id="pedidosPendientes">
                        <div class="loading-skeleton"></div>
                    </div>
                    <div class="stat-trend warning d-none d-sm-block">
                        <i class="fas fa-clock"></i>
                        <span>Requieren atención</span>
                    </div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Grid - Responsive Layout -->
<div class="charts-container">
    <!-- Primera fila de gráficos -->
    <div class="charts-row">
        <!-- Area Chart -->
        <div class="chart-wrapper chart-large">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <div class="chart-icon primary">
                            <i class="fas fa-chart-area"></i>
                        </div>
                        <div class="chart-title-text">
                            <h6 class="chart-title">Ventas Mensuales</h6>
                            <p class="chart-subtitle d-none d-lg-block">Evolución de ventas por mes</p>
                        </div>
                    </div>
                    <div class="chart-actions">
                        <button class="btn-chart-action d-none d-md-inline-flex" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#"><i class="fas fa-download me-2"></i>Exportar</a>
                            <a class="dropdown-item" href="#"><i class="fas fa-print me-2"></i>Imprimir</a>
                            <a class="dropdown-item" href="#"><i class="fas fa-share me-2"></i>Compartir</a>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="myAreaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="chart-wrapper chart-medium">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <div class="chart-icon secondary">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="chart-title-text">
                            <h6 class="chart-title">Distribución</h6>
                            <p class="chart-subtitle d-none d-xl-block">Por categorías</p>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="myPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda fila de gráficos -->
    <div class="charts-row">
        <!-- Bar Chart -->
        <div class="chart-wrapper chart-medium">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <div class="chart-icon tertiary">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="chart-title-text">
                            <h6 class="chart-title">Comparativa Anual</h6>
                            <p class="chart-subtitle d-none d-lg-block">Ventas por año</p>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="myBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doughnut Chart -->
        <div class="chart-wrapper chart-medium">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <div class="chart-icon accent">
                            <i class="fas fa-chart-donut"></i>
                        </div>
                        <div class="chart-title-text">
                            <h6 class="chart-title">Por Categoría</h6>
                            <p class="chart-subtitle d-none d-lg-block">Ventas distribuidas</p>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container">
                        <canvas id="myDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tercera fila - Gráfico completo -->
    <div class="charts-row">
        <div class="chart-wrapper chart-full">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <div class="chart-icon highlight">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="chart-title-text">
                            <h6 class="chart-title">Top 10 Productos</h6>
                            <p class="chart-subtitle d-none d-md-block">Productos más vendidos del período</p>
                        </div>
                    </div>
                    <div class="chart-actions">
                        <button class="btn-chart-refresh d-none d-sm-inline-flex">
                            <i class="fas fa-sync-alt"></i>
                            <span class="d-none d-md-inline">Actualizar</span>
                        </button>
                        <div class="dropdown d-none d-md-inline-block">
                            <button class="btn-chart-action" data-bs-toggle="dropdown">
                                <i class="fas fa-filter"></i>
                                <span class="d-none d-lg-inline">Filtrar</span>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#">Última semana</a>
                                <a class="dropdown-item" href="#">Último mes</a>
                                <a class="dropdown-item" href="#">Último año</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container chart-container-large">
                        <canvas id="productosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button para móviles -->
<div class="fab-container d-md-none">
    <button class="fab-main" data-bs-toggle="offcanvas" data-bs-target="#mobileActions">
        <i class="fas fa-plus"></i>
    </button>
</div>

<!-- Offcanvas para acciones móviles -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="mobileActions">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Acciones Rápidas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mobile-actions-grid">
            <button class="mobile-action-btn">
                <i class="fas fa-sync-alt"></i>
                <span>Actualizar</span>
            </button>
            <button class="mobile-action-btn">
                <i class="fas fa-download"></i>
                <span>Exportar</span>
            </button>
            <button class="mobile-action-btn">
                <i class="fas fa-filter"></i>
                <span>Filtros</span>
            </button>
            <button class="mobile-action-btn">
                <i class="fas fa-share"></i>
                <span>Compartir</span>
            </button>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // Configuración responsive para Chart.js
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = window.innerWidth < 768 ? 10 : 12;
    Chart.defaults.color = '#6b7280';
    
    // Función para detectar el tamaño de pantalla
    function getScreenSize() {
        if (window.innerWidth < 576) return 'xs';
        if (window.innerWidth < 768) return 'sm';
        if (window.innerWidth < 992) return 'md';
        if (window.innerWidth < 1200) return 'lg';
        return 'xl';
    }
    
    // Configuración responsive para gráficos
    function getResponsiveConfig(screenSize) {
        const configs = {
            xs: {
                fontSize: 10,
                padding: 8,
                legendPosition: 'bottom',
                aspectRatio: 1.5
            },
            sm: {
                fontSize: 11,
                padding: 10,
                legendPosition: 'bottom',
                aspectRatio: 1.8
            },
            md: {
                fontSize: 12,
                padding: 12,
                legendPosition: 'right',
                aspectRatio: 2
            },
            lg: {
                fontSize: 12,
                padding: 15,
                legendPosition: 'right',
                aspectRatio: 2.2
            },
            xl: {
                fontSize: 13,
                padding: 20,
                legendPosition: 'right',
                aspectRatio: 2.5
            }
        };
        return configs[screenSize] || configs.md;
    }
    
    const currentScreen = getScreenSize();
    const config = getResponsiveConfig(currentScreen);
    
    // Función para remover skeleton y mostrar datos
    function updateStatValue(elementId, value, prefix = '') {
        const element = document.getElementById(elementId);
        const skeleton = element.querySelector('.loading-skeleton');
        if (skeleton) {
            skeleton.remove();
        }
        element.textContent = prefix + value;
    }
    
    // Función para actualizar timestamp
    function updateTimestamp() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('es-ES', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        const timestampElement = document.getElementById('lastUpdate');
        if (timestampElement) {
            timestampElement.textContent = timeString;
        }
    }
    
    // Cargar resumen general
    fetch('/estadisticas/resumen-general')
        .then(response => response.json())
        .then(data => {
            updateStatValue('ventasHoy', Number(data.ventas_hoy || 0).toLocaleString(), '$');
            updateStatValue('ventasMes', Number(data.ventas_mes || 0).toLocaleString(), '$');
            updateStatValue('totalProductos', data.total_productos || 0);
            updateStatValue('pedidosPendientes', data.pedidos_pendientes || 0);
            updateTimestamp();
        })
        .catch(error => {
            console.error('Error cargando resumen:', error);
            // Mostrar valores por defecto en caso de error
            updateStatValue('ventasHoy', '0', '$');
            updateStatValue('ventasMes', '0', '$');
            updateStatValue('totalProductos', '0');
            updateStatValue('pedidosPendientes', '0');
        });

    // Configuración común responsive para gráficos
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: currentScreen === 'xs' || currentScreen === 'sm' ? 'bottom' : 'right',
                labels: {
                    usePointStyle: true,
                    padding: config.padding,
                    font: {
                        size: config.fontSize,
                        weight: '500'
                    },
                    boxWidth: currentScreen === 'xs' ? 8 : 12
                }
            },
            tooltip: {
                backgroundColor: 'rgba(22, 20, 64, 0.95)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: '#232162',
                borderWidth: 1,
                cornerRadius: 8,
                padding: config.padding,
                displayColors: true,
                usePointStyle: true,
                titleFont: {
                    size: config.fontSize
                },
                bodyFont: {
                    size: config.fontSize
                }
            }
        }
    };

    // Area Chart - Ventas Mensuales
    fetch('/estadisticas/ventas-mensuales')
        .then(response => response.json())
        .then(data => {
            var ctxArea = document.getElementById("myAreaChart").getContext('2d');
            new Chart(ctxArea, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: "Ventas ($)",
                        data: data.data,
                        backgroundColor: "rgba(35, 33, 98, 0.1)",
                        borderColor: "#232162",
                        borderWidth: currentScreen === 'xs' ? 2 : 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: "#232162",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointRadius: currentScreen === 'xs' ? 3 : 5,
                        pointHoverRadius: currentScreen === 'xs' ? 5 : 7
                    }],
                },
                options: { 
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: config.fontSize
                                },
                                callback: function(value) {
                                    return currentScreen === 'xs' ? 
                                        '$' + (value / 1000).toFixed(0) + 'k' : 
                                        '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: config.fontSize
                                },
                                maxRotation: currentScreen === 'xs' ? 45 : 0
                            }
                        }
                    },
                    plugins: {
                        ...commonOptions.plugins,
                        tooltip: {
                            ...commonOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return 'Ventas: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error cargando ventas mensuales:', error));

    // Pie Chart - Distribución de Productos
    fetch('/estadisticas/distribucion-productos')
        .then(response => response.json())
        .then(data => {
            var ctxPie = document.getElementById("myPieChart").getContext('2d');
            new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: ['#232162', '#1b194f', '#161440', '#2a2875', '#222062', '#1d1a53'],
                        hoverBackgroundColor: ['#2a2875', '#222062', '#1d1a53', '#232162', '#1b194f', '#161440'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }],
                },
                options: { 
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            ...commonOptions.plugins.legend,
                            position: 'bottom'
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error cargando distribución de productos:', error));

    // Bar Chart - Comparativa Anual
    fetch('/estadisticas/comparativa-anual')
        .then(response => response.json())
        .then(data => {
            var ctxBar = document.getElementById("myBarChart").getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: "Ventas ($)",
                        backgroundColor: "#232162",
                        hoverBackgroundColor: "#2a2875",
                        data: data.data,
                        borderRadius: currentScreen === 'xs' ? 2 : 4,
                        borderSkipped: false,
                    }],
                },
                options: { 
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: config.fontSize
                                },
                                callback: function(value) {
                                    return currentScreen === 'xs' ? 
                                        '$' + (value / 1000).toFixed(0) + 'k' : 
                                        '$' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: config.fontSize
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error cargando comparativa anual:', error));

    // Doughnut Chart - Categorías
    fetch('/estadisticas/categorias')
        .then(response => response.json())
        .then(data => {
            var ctxDoughnut = document.getElementById("myDoughnutChart").getContext('2d');
            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: ['#232162', '#1b194f', '#161440', '#2a2875', '#222062', '#1d1a53'],
                        hoverBackgroundColor: ['#2a2875', '#222062', '#1d1a53', '#232162', '#1b194f', '#161440'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }],
                },
                options: { 
                    ...commonOptions,
                    cutout: currentScreen === 'xs' ? '50%' : '60%',
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            ...commonOptions.plugins.legend,
                            position: 'bottom'
                        },
                        tooltip: {
                            ...commonOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': $' + context.parsed.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error cargando categorías:', error));

    // Chart de Productos Más Vendidos
    fetch('/estadisticas/productos-mas-vendidos')
        .then(response => response.json())
        .then(data => {
            var ctxProductos = document.getElementById("productosChart").getContext('2d');
            new Chart(ctxProductos, {
                type: 'bar',
                data: {
                    labels: data.labels.map(label => 
                        currentScreen === 'xs' && label.length > 15 ? 
                        label.substring(0, 12) + '...' : label
                    ),
                    datasets: [{
                        label: "Cantidad Vendida",
                        backgroundColor: "#10b981",
                        hoverBackgroundColor: "#059669",
                        data: data.cantidades,
                        borderRadius: currentScreen === 'xs' ? 2 : 4,
                        borderSkipped: false,
                    }],
                },
                options: { 
                    ...commonOptions,
                    indexAxis: currentScreen === 'xs' ? 'x' : 'y',
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    size: config.fontSize
                                },
                                maxRotation: currentScreen === 'xs' ? 45 : 0
                            }
                        },
                        y: {
                            grid: {
                                display: currentScreen === 'xs'
                            },
                            ticks: {
                                font: {
                                    size: config.fontSize
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error cargando productos más vendidos:', error));

    // Actualización automática cada 5 minutos
    setInterval(() => {
        updateTimestamp();
    }, 300000);

    // Manejo de resize para reconfigurar gráficos
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            Chart.defaults.font.size = window.innerWidth < 768 ? 10 : 12;
            // Aquí podrías recargar los gráficos si es necesario
        }, 250);
    });

    // Manejo del FAB y acciones móviles
    const fabButtons = document.querySelectorAll('.mobile-action-btn');
    fabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.querySelector('span').textContent;
            console.log('Acción móvil:', action);
            // Aquí implementarías las acciones específicas
        });
    });
});
</script>
@endpush