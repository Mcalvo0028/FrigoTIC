<?php
/**
 * FrigoTIC - Helper para generación de PDFs
 * MJCRSoftware
 * 
 * Genera PDFs usando HTML y el navegador para imprimir
 */

namespace App\Helpers;

class PdfHelper
{
    private $title;
    private $content;
    private $filename;
    private $orientation = 'portrait';
    
    public function __construct(string $title = 'FrigoTIC')
    {
        $this->title = $title;
        $this->filename = 'frigotic_' . date('Y-m-d_His') . '.pdf';
    }
    
    /**
     * Establecer título del documento
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Establecer nombre del archivo
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }
    
    /**
     * Establecer orientación (portrait/landscape)
     */
    public function setOrientation(string $orientation): self
    {
        $this->orientation = $orientation;
        return $this;
    }
    
    /**
     * Generar PDF de usuario eliminado con todos sus datos
     */
    public static function generateUserDeletionReport(array $usuario, array $movimientos): string
    {
        $html = self::getHtmlHeader('Informe de Usuario Eliminado - ' . $usuario['nombre_usuario']);
        
        $html .= '<div class="header">';
        $html .= '<h1>INFORME DE USUARIO ELIMINADO</h1>';
        $html .= '<p class="date">Fecha de eliminación: ' . date('d/m/Y H:i:s') . '</p>';
        $html .= '</div>';
        
        // Datos del usuario
        $html .= '<div class="section">';
        $html .= '<h2>Datos del Usuario</h2>';
        $html .= '<table class="info-table">';
        $html .= '<tr><td class="label">ID:</td><td>' . $usuario['id'] . '</td></tr>';
        $html .= '<tr><td class="label">Usuario:</td><td>' . htmlspecialchars($usuario['nombre_usuario']) . '</td></tr>';
        $html .= '<tr><td class="label">Email:</td><td>' . htmlspecialchars($usuario['email'] ?? '-') . '</td></tr>';
        $html .= '<tr><td class="label">Teléfono:</td><td>' . htmlspecialchars($usuario['telefono'] ?? '-') . '</td></tr>';
        $html .= '<tr><td class="label">Nombre completo:</td><td>' . htmlspecialchars($usuario['nombre_completo'] ?? '-') . '</td></tr>';
        $html .= '<tr><td class="label">Fecha registro:</td><td>' . ($usuario['fecha_registro'] ?? '-') . '</td></tr>';
        $html .= '<tr><td class="label">Último acceso:</td><td>' . ($usuario['ultimo_acceso'] ?? 'Nunca') . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Resumen de movimientos
        $totalConsumos = 0;
        $totalPagos = 0;
        foreach ($movimientos as $m) {
            if ($m['tipo'] === 'consumo') {
                $totalConsumos += abs($m['total']);
            } elseif ($m['tipo'] === 'pago') {
                $totalPagos += abs($m['total']);
            }
        }
        
        $html .= '<div class="section">';
        $html .= '<h2>Resumen Económico</h2>';
        $html .= '<table class="info-table">';
        $html .= '<tr><td class="label">Total consumido:</td><td class="amount">' . number_format($totalConsumos, 2, ',', '.') . ' €</td></tr>';
        $html .= '<tr><td class="label">Total pagado:</td><td class="amount">' . number_format($totalPagos, 2, ',', '.') . ' €</td></tr>';
        $saldoFinal = $totalConsumos - $totalPagos;
        $saldoTexto = $saldoFinal > 0 ? '-' . number_format($saldoFinal, 2, ',', '.') . ' €' : number_format(abs($saldoFinal), 2, ',', '.') . ' €';
        $html .= '<tr><td class="label">Saldo final:</td><td class="amount ' . ($saldoFinal > 0 ? 'debt' : 'credit') . '">' . $saldoTexto . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        
        // Historial de movimientos
        $html .= '<div class="section">';
        $html .= '<h2>Historial Completo de Movimientos (' . count($movimientos) . ')</h2>';
        
        if (empty($movimientos)) {
            $html .= '<p class="empty">No hay movimientos registrados.</p>';
        } else {
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th style="width:120px;">Fecha</th><th style="width:80px;">Tipo</th><th>Producto</th><th style="width:60px;text-align:center;">Cant.</th><th style="width:70px;text-align:right;">Precio</th><th style="width:80px;text-align:right;">Total</th></tr></thead>';
            $html .= '<tbody>';
            foreach ($movimientos as $m) {
                $tipoClass = $m['tipo'] === 'consumo' ? 'consumo' : ($m['tipo'] === 'pago' ? 'pago' : ($m['tipo'] === 'reposicion' ? 'reposicion' : 'ajuste'));
                $html .= '<tr class="' . $tipoClass . '">';
                $html .= '<td style="width:120px;">' . date('d/m/Y H:i', strtotime($m['fecha_hora'])) . '</td>';
                $html .= '<td style="width:80px;">' . ucfirst($m['tipo']) . '</td>';
                $html .= '<td>' . htmlspecialchars($m['producto_nombre'] ?? '-') . '</td>';
                $html .= '<td style="width:60px;text-align:center;">' . ($m['cantidad'] ?? '-') . '</td>';
                $html .= '<td style="width:70px;text-align:right;">' . ($m['precio_unitario'] ? number_format($m['precio_unitario'], 2, ',', '.') . ' €' : '-') . '</td>';
                $html .= '<td style="width:80px;text-align:right;">' . number_format(abs($m['total']), 2, ',', '.') . ' €</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        }
        $html .= '</div>';
        
        $html .= self::getHtmlFooter();
        
        return $html;
    }
    
    /**
     * Generar PDF de listado genérico
     */
    public static function generateListReport(string $title, array $columns, array $data, array $filters = [], array $summary = []): string
    {
        $html = self::getHtmlHeader($title);
        
        $html .= '<div class="header">';
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p class="date">Generado: ' . date('d/m/Y H:i:s') . '</p>';
        $html .= '</div>';
        
        // Filtros aplicados
        if (!empty($filters)) {
            $html .= '<div class="filters-info">';
            $html .= '<strong>Filtros aplicados:</strong> ';
            $filterTexts = [];
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $filterTexts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . htmlspecialchars($value);
                }
            }
            $html .= implode(' | ', $filterTexts);
            $html .= '</div>';
        }
        
        $html .= '<div class="section">';
        $html .= '<p class="total-records">Total de registros: ' . count($data) . '</p>';
        
        if (empty($data)) {
            $html .= '<p class="empty">No hay datos para mostrar.</p>';
        } else {
            $html .= '<table class="data-table">';
            $html .= '<thead><tr>';
            foreach ($columns as $col) {
                $html .= '<th>' . htmlspecialchars($col['label']) . '</th>';
            }
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($columns as $col) {
                    $value = $row[$col['field']] ?? '-';
                    $class = $col['class'] ?? '';
                    
                    // Formatear según tipo
                    if (isset($col['type'])) {
                        switch ($col['type']) {
                            case 'money':
                                $value = number_format((float)$value, 2, ',', '.') . ' €';
                                break;
                            case 'date':
                                $value = $value ? date('d/m/Y', strtotime($value)) : '-';
                                break;
                            case 'datetime':
                                $value = $value ? date('d/m/Y H:i', strtotime($value)) : '-';
                                break;
                            case 'badge':
                                $value = '<span class="badge-' . ($value ?: 'default') . '">' . ucfirst($value) . '</span>';
                                break;
                            case 'image':
                                if (!empty($value)) {
                                    $imgPath = $_SERVER['DOCUMENT_ROOT'] . $value;
                                    if (file_exists($imgPath)) {
                                        $imgData = base64_encode(file_get_contents($imgPath));
                                        $imgType = pathinfo($imgPath, PATHINFO_EXTENSION);
                                        $value = '<img src="data:image/' . $imgType . ';base64,' . $imgData . '" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">';
                                    } else {
                                        $value = '<span style="color:#999;">Sin imagen</span>';
                                    }
                                } else {
                                    $value = '<span style="color:#999;">Sin imagen</span>';
                                }
                                break;
                        }
                    }
                    
                    $html .= '<td class="' . $class . '">' . $value . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        }
        $html .= '</div>';
        
        // Agregar resumen si existe
        if (!empty($summary)) {
            $html .= '<div class="summary-box">';
            $html .= '<h3>Resumen</h3>';
            $html .= '<div class="summary-grid">';
            foreach ($summary as $label => $value) {
                $html .= '<div class="summary-item">';
                $html .= '<span class="summary-label">' . htmlspecialchars($label) . '</span>';
                $html .= '<span class="summary-value">' . htmlspecialchars($value) . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= self::getHtmlFooter();
        
        return $html;
    }
    
    /**
     * Generar informe de gráficos/estadísticas
     */
    public static function generateGraficosReport(array $consumosPorMes, array $consumosPorProducto, array $consumosPorUsuario, array $pagosVsConsumos): string
    {
        $html = self::getHtmlHeader('Informe de Estadísticas - FrigoTIC');
        
        $html .= '<div class="header">';
        $html .= '<h1>📊 INFORME DE ESTADÍSTICAS</h1>';
        $html .= '<p class="date">Generado: ' . date('d/m/Y H:i:s') . '</p>';
        $html .= '</div>';
        
        // Resumen general de pagos vs consumos (sumando todos los meses)
        $html .= '<div class="section stats-section">';
        $html .= '<h2>💰 Balance General (últimos 12 meses)</h2>';
        $html .= '<div class="stats-grid">';
        
        $totalConsumos = 0;
        $totalPagos = 0;
        // pagosVsConsumos tiene: mes, consumos, pagos
        foreach ($pagosVsConsumos as $item) {
            $totalConsumos += (float)($item['consumos'] ?? 0);
            $totalPagos += (float)($item['pagos'] ?? 0);
        }
        
        $html .= '<div class="stat-card consumo">';
        $html .= '<div class="stat-icon">🛒</div>';
        $html .= '<div class="stat-info">';
        $html .= '<span class="stat-value">' . number_format($totalConsumos, 2, ',', '.') . ' €</span>';
        $html .= '<span class="stat-label">Total Consumos</span>';
        $html .= '</div></div>';
        
        $html .= '<div class="stat-card pago">';
        $html .= '<div class="stat-icon">💵</div>';
        $html .= '<div class="stat-info">';
        $html .= '<span class="stat-value">' . number_format($totalPagos, 2, ',', '.') . ' €</span>';
        $html .= '<span class="stat-label">Total Pagos</span>';
        $html .= '</div></div>';
        
        $saldo = $totalConsumos - $totalPagos;
        $html .= '<div class="stat-card ' . ($saldo > 0 ? 'deuda' : 'favor') . '">';
        $html .= '<div class="stat-icon">' . ($saldo > 0 ? '⚠️' : '✅') . '</div>';
        $html .= '<div class="stat-info">';
        $html .= '<span class="stat-value">' . number_format(abs($saldo), 2, ',', '.') . ' €</span>';
        $html .= '<span class="stat-label">' . ($saldo > 0 ? 'Pendiente de Cobro' : 'A Favor') . '</span>';
        $html .= '</div></div>';
        
        $html .= '</div></div>';
        
        // Consumos por mes (usa: mes, total_dinero)
        $html .= '<div class="section stats-section">';
        $html .= '<h2>📅 Consumos por Mes</h2>';
        if (empty($consumosPorMes)) {
            $html .= '<p class="empty">No hay datos de consumos por mes.</p>';
        } else {
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>Mes</th><th class="right">Total</th><th>Gráfico</th></tr></thead>';
            $html .= '<tbody>';
            $totales = array_column($consumosPorMes, 'total_dinero');
            $maxMes = !empty($totales) ? max($totales) : 1;
            foreach ($consumosPorMes as $item) {
                $total = (float)($item['total_dinero'] ?? 0);
                $porcentaje = $maxMes > 0 ? ($total / $maxMes) * 100 : 0;
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['mes'] ?? '-') . '</td>';
                $html .= '<td class="right">' . number_format($total, 2, ',', '.') . ' €</td>';
                $html .= '<td><div class="bar-chart"><div class="bar" style="width: ' . $porcentaje . '%"></div></div></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        $html .= '</div>';
        
        // Consumos por producto (Top 10) (usa: producto, total_cantidad, total_dinero)
        $html .= '<div class="section stats-section">';
        $html .= '<h2>🥤 Top 10 Productos Más Consumidos</h2>';
        if (empty($consumosPorProducto)) {
            $html .= '<p class="empty">No hay datos de consumos por producto.</p>';
        } else {
            $top10 = array_slice($consumosPorProducto, 0, 10);
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>#</th><th>Producto</th><th class="center">Cantidad</th><th class="right">Total</th><th>Gráfico</th></tr></thead>';
            $html .= '<tbody>';
            $totales = array_column($top10, 'total_dinero');
            $maxProd = !empty($totales) ? max($totales) : 1;
            $i = 1;
            foreach ($top10 as $item) {
                $total = (float)($item['total_dinero'] ?? 0);
                $porcentaje = $maxProd > 0 ? ($total / $maxProd) * 100 : 0;
                $medal = $i === 1 ? '🥇' : ($i === 2 ? '🥈' : ($i === 3 ? '🥉' : $i));
                $html .= '<tr>';
                $html .= '<td class="center">' . $medal . '</td>';
                $html .= '<td>' . htmlspecialchars($item['producto'] ?? '-') . '</td>';
                $html .= '<td class="center">' . ($item['total_cantidad'] ?? '-') . '</td>';
                $html .= '<td class="right">' . number_format($total, 2, ',', '.') . ' €</td>';
                $html .= '<td><div class="bar-chart product"><div class="bar" style="width: ' . $porcentaje . '%"></div></div></td>';
                $html .= '</tr>';
                $i++;
            }
            $html .= '</tbody></table>';
        }
        $html .= '</div>';
        
        // Consumos por usuario (Top 10) (usa: usuario, total_cantidad, total_dinero)
        $html .= '<div class="section stats-section">';
        $html .= '<h2>👥 Top 10 Usuarios por Consumo</h2>';
        if (empty($consumosPorUsuario)) {
            $html .= '<p class="empty">No hay datos de consumos por usuario.</p>';
        } else {
            $top10Users = array_slice($consumosPorUsuario, 0, 10);
            $html .= '<table class="data-table">';
            $html .= '<thead><tr><th>#</th><th>Usuario</th><th class="right">Total</th><th>Gráfico</th></tr></thead>';
            $html .= '<tbody>';
            $totales = array_column($top10Users, 'total_dinero');
            $maxUser = !empty($totales) ? max($totales) : 1;
            $i = 1;
            foreach ($top10Users as $item) {
                $total = (float)($item['total_dinero'] ?? 0);
                $porcentaje = $maxUser > 0 ? ($total / $maxUser) * 100 : 0;
                $medal = $i === 1 ? '🥇' : ($i === 2 ? '🥈' : ($i === 3 ? '🥉' : $i));
                $html .= '<tr>';
                $html .= '<td class="center">' . $medal . '</td>';
                $html .= '<td>' . htmlspecialchars($item['usuario'] ?? '-') . '</td>';
                $html .= '<td class="right">' . number_format($total, 2, ',', '.') . ' €</td>';
                $html .= '<td><div class="bar-chart user"><div class="bar" style="width: ' . $porcentaje . '%"></div></div></td>';
                $html .= '</tr>';
                $i++;
            }
            $html .= '</tbody></table>';
        }
        $html .= '</div>';
        
        $html .= self::getHtmlFooter();
        
        return $html;
    }
    
    /**
     * Generar informe de gráficos con Chart.js (últimos 30 días)
     */
    public static function generateGraficosReportWithCharts(array $consumosPorDia, array $consumosPorProducto, array $consumosPorUsuario, array $pagosVsConsumos): string
    {
        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Estadísticas - FrigoTIC</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #c0392b; }
        .header h1 { color: #c0392b; font-size: 24px; margin-bottom: 10px; }
        .header .subtitle { color: #666; font-size: 14px; }
        .stats-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { padding: 20px; border-radius: 10px; color: white; text-align: center; }
        .stat-card.consumo { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .stat-card.pago { background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%); }
        .stat-card.saldo { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); }
        .stat-card .value { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .stat-card .label { font-size: 12px; opacity: 0.9; }
        .chart-section { margin-bottom: 30px; page-break-inside: avoid; }
        .chart-section h2 { color: #c0392b; font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #eee; }
        .chart-container { height: 300px; margin-bottom: 20px; }
        .chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #eee; text-align: center; font-size: 10px; color: #999; }
        @media print { 
            body { padding: 0; background: white; } 
            .container { box-shadow: none; padding: 10px; }
            .chart-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 INFORME DE ESTADÍSTICAS</h1>
        <p class="subtitle">Últimos 30 días | Generado: ' . date('d/m/Y H:i:s') . '</p>
    </div>';
        
        // Calcular totales
        $totalConsumos = 0;
        $totalPagos = 0;
        foreach ($pagosVsConsumos as $item) {
            $totalConsumos += (float)($item['consumos'] ?? 0);
            $totalPagos += (float)($item['pagos'] ?? 0);
        }
        $saldo = $totalConsumos - $totalPagos;
        
        // Tarjetas de resumen
        $html .= '<div class="stats-cards">
            <div class="stat-card consumo">
                <div class="value">' . number_format($totalConsumos, 2, ',', '.') . ' €</div>
                <div class="label">Total Consumos</div>
            </div>
            <div class="stat-card pago">
                <div class="value">' . number_format($totalPagos, 2, ',', '.') . ' €</div>
                <div class="label">Total Pagos</div>
            </div>
            <div class="stat-card saldo">
                <div class="value">' . number_format(abs($saldo), 2, ',', '.') . ' €</div>
                <div class="label">' . ($saldo > 0 ? 'Pendiente de Cobro' : 'A Favor') . '</div>
            </div>
        </div>';
        
        // Gráfico 1: Consumos por día (línea)
        $html .= '<div class="chart-section">
            <h2>📈 Evolución de Consumos (Últimos 30 días)</h2>
            <div class="chart-container"><canvas id="chartConsumosDia"></canvas></div>
        </div>';
        
        // Grid de 2 gráficos
        $html .= '<div class="chart-grid">';
        
        // Gráfico 2: Consumos por producto (pie)
        $html .= '<div class="chart-section">
            <h2>🥤 Top Productos</h2>
            <div class="chart-container"><canvas id="chartProductos"></canvas></div>
        </div>';
        
        // Gráfico 3: Consumos por usuario (barras horizontales)
        $html .= '<div class="chart-section">
            <h2>👥 Top Usuarios</h2>
            <div class="chart-container"><canvas id="chartUsuarios"></canvas></div>
        </div>';
        
        $html .= '</div>';
        
        // Gráfico 4: Pagos vs Consumos (barras)
        $html .= '<div class="chart-section">
            <h2>💰 Pagos vs Consumos</h2>
            <div class="chart-container"><canvas id="chartPagosConsumos"></canvas></div>
        </div>';
        
        // Preparar datos para JS
        $diasLabels = array_map(function($d) { 
            return date('d/m', strtotime($d['dia'])); 
        }, $consumosPorDia);
        $diasData = array_column($consumosPorDia, 'total_dinero');
        
        $productosLabels = array_column($consumosPorProducto, 'producto');
        $productosData = array_column($consumosPorProducto, 'total_dinero');
        
        $usuariosLabels = array_column($consumosPorUsuario, 'usuario');
        $usuariosData = array_column($consumosPorUsuario, 'total_dinero');
        
        $pagosLabels = array_map(function($d) { 
            return date('d/m', strtotime($d['dia'])); 
        }, $pagosVsConsumos);
        $pagosConsumos = array_column($pagosVsConsumos, 'consumos');
        $pagosPagos = array_column($pagosVsConsumos, 'pagos');
        
        $html .= '<div class="footer">
            <p>FrigoTIC - MJCRSoftware | Informe generado automáticamente</p>
        </div>
</div>

<script>
// Colores para gráficos
const coloresPie = [
    "#e74c3c", "#3498db", "#27ae60", "#f39c12", "#9b59b6",
    "#1abc9c", "#e67e22", "#34495e", "#95a5a6", "#d35400"
];

// Gráfico 1: Consumos por día
new Chart(document.getElementById("chartConsumosDia"), {
    type: "line",
    data: {
        labels: ' . json_encode($diasLabels) . ',
        datasets: [{
            label: "Consumos (€)",
            data: ' . json_encode($diasData) . ',
            borderColor: "#c0392b",
            backgroundColor: "rgba(192, 57, 43, 0.1)",
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "#c0392b",
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => v + " €" } }
        }
    }
});

// Gráfico 2: Productos (Pie)
new Chart(document.getElementById("chartProductos"), {
    type: "doughnut",
    data: {
        labels: ' . json_encode($productosLabels) . ',
        datasets: [{
            data: ' . json_encode($productosData) . ',
            backgroundColor: coloresPie
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 10 } } }
        }
    }
});

// Gráfico 3: Usuarios (Barras horizontales)
new Chart(document.getElementById("chartUsuarios"), {
    type: "bar",
    data: {
        labels: ' . json_encode($usuariosLabels) . ',
        datasets: [{
            label: "Total (€)",
            data: ' . json_encode($usuariosData) . ',
            backgroundColor: coloresPie
        }]
    },
    options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, ticks: { callback: v => v + " €" } }
        }
    }
});

// Gráfico 4: Pagos vs Consumos
new Chart(document.getElementById("chartPagosConsumos"), {
    type: "bar",
    data: {
        labels: ' . json_encode($pagosLabels) . ',
        datasets: [
            {
                label: "Consumos",
                data: ' . json_encode($pagosConsumos) . ',
                backgroundColor: "rgba(231, 76, 60, 0.8)"
            },
            {
                label: "Pagos",
                data: ' . json_encode($pagosPagos) . ',
                backgroundColor: "rgba(39, 174, 96, 0.8)"
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "top" } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => v + " €" } }
        }
    }
});

// Esperar a que los gráficos se renderizen antes de imprimir
setTimeout(function() {
    window.print();
    window.onafterprint = function() { window.close(); };
}, 1000);
</script>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Obtener cabecera HTML para PDF
     */
    private static function getHtmlHeader(string $title): string
    {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #c0392b;
        }
        .header h1 { 
            color: #c0392b; 
            font-size: 18px;
            margin-bottom: 5px;
        }
        .date { color: #666; font-size: 10px; }
        .section { margin-bottom: 20px; }
        .section h2 { 
            color: #c0392b; 
            font-size: 14px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { padding: 5px 10px; border-bottom: 1px solid #eee; }
        .info-table .label { font-weight: bold; width: 150px; background: #f9f9f9; }
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 10px;
        }
        .data-table th { 
            background: #c0392b; 
            color: white; 
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
        }
        .data-table td { 
            padding: 6px 5px; 
            border-bottom: 1px solid #ddd;
        }
        .data-table tr:nth-child(even) { background: #f9f9f9; }
        .data-table tr.consumo { background: #fff5f5; }
        .data-table tr.pago { background: #f0fff0; }
        .data-table tr.reposicion { background: #f0f7ff; }
        .data-table tr.ajuste { background: #fffbf0; }
        .right { text-align: right; }
        .center { text-align: center; }
        .amount { font-weight: bold; }
        .debt { color: #c0392b; }
        .credit { color: #27ae60; }
        .empty { color: #999; font-style: italic; padding: 20px; text-align: center; }
        .filters-info { 
            background: #f5f5f5; 
            padding: 10px; 
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 10px;
        }
        .total-records { margin-bottom: 10px; color: #666; }
        .footer { 
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #999;
        }
        .badge-consumo { color: #c0392b; font-weight: bold; }
        .badge-pago { color: #27ae60; font-weight: bold; }
        .badge-ajuste { color: #f39c12; font-weight: bold; }
        .badge-reposicion { color: #3498db; font-weight: bold; }
        
        /* Estilos para resumen */
        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: white;
        }
        .summary-box h3 {
            margin-bottom: 10px;
            font-size: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 8px;
        }
        .summary-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .summary-label {
            font-size: 10px;
            opacity: 0.9;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }
        
        /* Estilos para gráficos/estadísticas */
        .stats-section { page-break-inside: avoid; }
        .stats-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .stat-card {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card.consumo {
            background: linear-gradient(135deg, #ff6b6b 0%, #c0392b 100%);
            color: white;
        }
        .stat-card.pago {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        .stat-card.deuda {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        .stat-card.favor {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        .stat-icon {
            font-size: 28px;
        }
        .stat-info {
            display: flex;
            flex-direction: column;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 10px;
            opacity: 0.9;
        }
        
        /* Barras de gráfico */
        .bar-chart {
            width: 150px;
            height: 18px;
            background: #eee;
            border-radius: 9px;
            overflow: hidden;
        }
        .bar-chart .bar {
            height: 100%;
            background: linear-gradient(90deg, #c0392b 0%, #e74c3c 100%);
            border-radius: 9px;
            transition: width 0.3s;
        }
        .bar-chart.product .bar {
            background: linear-gradient(90deg, #3498db 0%, #5dade2 100%);
        }
        .bar-chart.user .bar {
            background: linear-gradient(90deg, #9b59b6 0%, #bb8fce 100%);
        }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .stat-card { break-inside: avoid; }
        }
    </style>
</head>
<body>';
    }
    
    /**
     * Obtener pie de página HTML para PDF
     */
    private static function getHtmlFooter(): string
    {
        return '
    <div class="footer">
        <p>FrigoTIC - MJCRSoftware | Documento generado automáticamente</p>
    </div>
</body>
</html>';
    }
    
    /**
     * Enviar HTML como descarga de PDF (usando navegador)
     */
    public static function outputForPrint(string $html): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        echo '<script>window.print(); window.onafterprint = function() { window.close(); };</script>';
        exit;
    }
    
    /**
     * Guardar HTML en archivo para descargar (carpeta segura)
     */
    public static function saveHtmlFile(string $html, string $filename): string
    {
        // Guardar en carpeta segura fuera de public
        $path = dirname(dirname(__DIR__)) . '/storage/reports/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        $filepath = $path . $filename . '.html';
        file_put_contents($filepath, $html);
        
        // Devolver URL a través del controlador de descargas seguras
        return '/download.php?type=report&file=' . $filename . '.html';
    }
}
