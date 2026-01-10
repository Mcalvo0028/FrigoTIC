<?php
/**
 * FrigoTIC - Funciones Auxiliares
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

/**
 * Escapar HTML para prevenir XSS
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Obtener la URL base de la aplicación
 */
function baseUrl(string $path = ''): string
{
    $config = require dirname(__DIR__) . '/config/app.php';
    $base = rtrim($config['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Obtener URL de assets (CSS, JS, imágenes)
 */
function asset(string $path): string
{
    return baseUrl($path);
}

/**
 * Redirigir a una URL
 */
function redirect(string $path): void
{
    header('Location: ' . baseUrl($path));
    exit;
}

/**
 * Verificar si la solicitud es AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Responder con JSON
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Obtener parámetro GET
 */
function getParam(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * Obtener parámetro POST
 */
function postParam(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Formatear fecha para mostrar
 */
function formatDate(string $date, string $format = 'd/m/Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Formatear fecha y hora para mostrar
 */
function formatDateTime(string $datetime, string $format = 'd/m/Y H:i'): string
{
    return date($format, strtotime($datetime));
}

/**
 * Formatear moneda
 */
function formatMoney(float $amount, string $symbol = '€'): string
{
    return number_format($amount, 2, ',', '.') . ' ' . $symbol;
}

/**
 * Generar token CSRF
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Campo oculto con token CSRF
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verificar token CSRF
 */
function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Mostrar mensaje flash
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtener y limpiar mensaje flash
 */
function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Verificar si hay mensaje flash
 */
function hasFlash(): bool
{
    return isset($_SESSION['flash']);
}

/**
 * Generar paginación HTML
 */
function pagination(int $currentPage, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="pagination-nav"><ul class="pagination">';

    // Botón anterior
    if ($currentPage > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="pagination-link"><i class="fas fa-chevron-left"></i></a></li>';
    }

    // Números de página
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=1" class="pagination-link">1</a></li>';
        if ($start > 2) {
            $html .= '<li><span class="pagination-ellipsis">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li><a href="' . $baseUrl . '?page=' . $i . '" class="pagination-link' . $active . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li><span class="pagination-ellipsis">...</span></li>';
        }
        $html .= '<li><a href="' . $baseUrl . '?page=' . $totalPages . '" class="pagination-link">' . $totalPages . '</a></li>';
    }

    // Botón siguiente
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="pagination-link"><i class="fas fa-chevron-right"></i></a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Selector de elementos por página
 */
function perPageSelector(int $current, array $options, string $baseUrl): string
{
    $html = '<div class="per-page-selector">';
    $html .= '<label>Mostrar:</label>';
    $html .= '<select onchange="window.location.href=\'' . $baseUrl . '?perPage=\'+this.value">';
    
    foreach ($options as $option) {
        $selected = $option === $current ? ' selected' : '';
        $html .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
    }
    
    $html .= '</select>';
    $html .= '<span>elementos</span>';
    $html .= '</div>';

    return $html;
}

/**
 * Obtener versión de la aplicación
 */
function getAppVersion(): string
{
    $versionFile = dirname(__DIR__, 2) . '/version_info.txt';
    return file_exists($versionFile) ? trim(file_get_contents($versionFile)) : '1.0.0';
}

/**
 * Truncar texto
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Obtener clase CSS según el tipo de movimiento
 */
function getMovimientoClass(string $tipo): string
{
    return match($tipo) {
        'consumo' => 'badge-danger',
        'pago' => 'badge-success',
        'reposicion' => 'badge-info',
        'ajuste' => 'badge-warning',
        default => 'badge-secondary'
    };
}

/**
 * Obtener icono según el tipo de movimiento
 */
function getMovimientoIcon(string $tipo): string
{
    return match($tipo) {
        'consumo' => 'fa-shopping-cart',
        'pago' => 'fa-money-bill-wave',
        'reposicion' => 'fa-boxes',
        'ajuste' => 'fa-sliders-h',
        default => 'fa-exchange-alt'
    };
}

/**
 * Verificar si es la página actual
 */
function isCurrentPage(string $page): bool
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return strpos($uri, $page) !== false;
}

/**
 * Generar clase activa para menú
 */
function activeClass(string $page): string
{
    return isCurrentPage($page) ? 'active' : '';
}
