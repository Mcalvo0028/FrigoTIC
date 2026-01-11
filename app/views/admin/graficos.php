<?php
/**
 * FrigoTIC - Gráficos (Admin)
 */

$pageTitle = 'Estadísticas y Gráficos';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Movimiento.php';

use App\Models\Movimiento;

$movimientoModel = new Movimiento();

// Obtener datos para gráficos
$consumosPorMes = $movimientoModel->getEstadisticasGraficos('consumos_por_mes');
$consumosPorProducto = $movimientoModel->getEstadisticasGraficos('consumos_por_producto');
$consumosPorUsuario = $movimientoModel->getEstadisticasGraficos('consumos_por_usuario');
$pagosVsConsumos = $movimientoModel->getEstadisticasGraficos('pagos_vs_consumos');

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/admin-tabs.php';
?>

<h1 class="mb-4"><i class="fas fa-chart-bar"></i> Estadísticas y Gráficos</h1>

<!-- Selector de tipo de gráfico -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex gap-3 flex-wrap justify-content-between align-items-center">
            <div class="d-flex gap-3 flex-wrap">
                <button class="btn btn-primary active" onclick="showChart('consumosMes')" id="btn-consumosMes">
                    <i class="fas fa-chart-line"></i> Consumos por Mes
                </button>
                <button class="btn btn-secondary" onclick="showChart('consumosProducto')" id="btn-consumosProducto">
                    <i class="fas fa-chart-pie"></i> Por Producto
                </button>
                <button class="btn btn-secondary" onclick="showChart('consumosUsuario')" id="btn-consumosUsuario">
                    <i class="fas fa-chart-bar"></i> Por Usuario
                </button>
                <button class="btn btn-secondary" onclick="showChart('pagosConsumos')" id="btn-pagosConsumos">
                    <i class="fas fa-balance-scale"></i> Pagos vs Consumos
                </button>
            </div>
            <a href="/export?action=export&type=graficos" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </a>
        </div>
    </div>
</div>

<!-- Contenedor de gráficos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title" id="chart-title">
            <i class="fas fa-chart-line"></i> Consumos por Mes
        </h2>
    </div>
    <div class="card-body">
        <div style="height: 400px;">
            <canvas id="mainChart"></canvas>
        </div>
    </div>
</div>

<!-- Resumen de datos -->
<div class="stats-grid mt-4">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value" id="total-consumos">0</div>
        <div class="stat-label">Total consumos (12 meses)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-value" id="total-pagos">0</div>
        <div class="stat-label">Total pagos (12 meses)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-balance-scale"></i>
        </div>
        <div class="stat-value" id="balance">0</div>
        <div class="stat-label">Balance pendiente</div>
    </div>
</div>

<script>
// Datos para los gráficos
const datosConsumosMes = <?= json_encode($consumosPorMes) ?>;
const datosConsumosProd = <?= json_encode($consumosPorProducto) ?>;
const datosConsumosUser = <?= json_encode($consumosPorUsuario) ?>;
const datosPagosConsumos = <?= json_encode($pagosVsConsumos) ?>;

let chartInstance = null;

// Colores para gráficos
const colores = [
    '#dc2626', '#2563eb', '#10b981', '#f59e0b', '#8b5cf6',
    '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
];

function showChart(tipo) {
    // Actualizar botones activos
    document.querySelectorAll('.card-body .btn').forEach(btn => {
        btn.classList.remove('btn-primary', 'active');
        btn.classList.add('btn-secondary');
    });
    document.getElementById('btn-' + tipo).classList.remove('btn-secondary');
    document.getElementById('btn-' + tipo).classList.add('btn-primary', 'active');
    
    // Destruir gráfico anterior
    if (chartInstance) {
        chartInstance.destroy();
    }
    
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    switch(tipo) {
        case 'consumosMes':
            document.getElementById('chart-title').innerHTML = '<i class="fas fa-chart-line"></i> Consumos por Mes';
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datosConsumosMes.map(d => d.mes),
                    datasets: [{
                        label: 'Total (€)',
                        data: datosConsumosMes.map(d => d.total_dinero),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => value + ' €'
                            }
                        }
                    }
                }
            });
            break;
            
        case 'consumosProducto':
            document.getElementById('chart-title').innerHTML = '<i class="fas fa-chart-pie"></i> Consumos por Producto';
            chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: datosConsumosProd.map(d => d.producto),
                    datasets: [{
                        data: datosConsumosProd.map(d => d.total_cantidad),
                        backgroundColor: colores
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
            break;
            
        case 'consumosUsuario':
            document.getElementById('chart-title').innerHTML = '<i class="fas fa-chart-bar"></i> Consumos por Usuario';
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datosConsumosUser.map(d => d.usuario),
                    datasets: [{
                        label: 'Total (€)',
                        data: datosConsumosUser.map(d => d.total_dinero),
                        backgroundColor: colores
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => value + ' €'
                            }
                        }
                    }
                }
            });
            break;
            
        case 'pagosConsumos':
            document.getElementById('chart-title').innerHTML = '<i class="fas fa-balance-scale"></i> Pagos vs Consumos';
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datosPagosConsumos.map(d => d.mes),
                    datasets: [
                        {
                            label: 'Consumos (€)',
                            data: datosPagosConsumos.map(d => d.consumos),
                            backgroundColor: '#dc2626'
                        },
                        {
                            label: 'Pagos (€)',
                            data: datosPagosConsumos.map(d => d.pagos),
                            backgroundColor: '#10b981'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => value + ' €'
                            }
                        }
                    }
                }
            });
            break;
    }
}

// Calcular totales
let totalConsumos = datosPagosConsumos.reduce((sum, d) => sum + parseFloat(d.consumos || 0), 0);
let totalPagos = datosPagosConsumos.reduce((sum, d) => sum + parseFloat(d.pagos || 0), 0);
let balance = totalConsumos - totalPagos;

document.getElementById('total-consumos').textContent = totalConsumos.toFixed(2).replace('.', ',') + ' €';
document.getElementById('total-pagos').textContent = totalPagos.toFixed(2).replace('.', ',') + ' €';
document.getElementById('balance').textContent = balance.toFixed(2).replace('.', ',') + ' €';

// Mostrar gráfico inicial
showChart('consumosMes');
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
