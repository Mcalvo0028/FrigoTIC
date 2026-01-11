<?php
/**
 * FrigoTIC - Helper para envío de correos SMTP
 * MJCRSoftware
 * 
 * Implementación de SMTP sin dependencias externas
 */

namespace App\Helpers;

class EmailHelper
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpFromName;
    private $smtpEncryption;
    private $debug = false;
    private $lastError = '';

    public function __construct()
    {
        // Cargar configuración SMTP
        EnvHelper::load();
        
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
        $this->smtpUser = getenv('SMTP_USER') ?: '';
        $this->smtpPass = getenv('SMTP_PASS') ?: '';
        $this->smtpFromName = getenv('SMTP_FROM_NAME') ?: 'FrigoTIC';
        $this->smtpEncryption = getenv('SMTP_ENCRYPTION') ?: 'tls';
        $this->debug = (getenv('APP_DEBUG') === 'true');
    }

    /**
     * Enviar correo usando SMTP
     */
    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            $this->lastError = 'Credenciales SMTP no configuradas';
            return false;
        }

        try {
            // Crear contexto SSL
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);

            // Conectar al servidor SMTP
            $socket = stream_socket_client(
                "tcp://{$this->smtpHost}:{$this->smtpPort}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$socket) {
                $this->lastError = "No se pudo conectar: $errstr ($errno)";
                return false;
            }

            // Leer respuesta de bienvenida
            $this->getResponse($socket);

            // EHLO
            $this->sendCommand($socket, "EHLO " . gethostname());
            $this->getResponse($socket);

            // STARTTLS para TLS
            if ($this->smtpEncryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS");
                $response = $this->getResponse($socket);
                
                if (strpos($response, '220') === false) {
                    $this->lastError = "STARTTLS falló: $response";
                    fclose($socket);
                    return false;
                }

                // Activar cifrado TLS
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

                // Nuevo EHLO después de TLS
                $this->sendCommand($socket, "EHLO " . gethostname());
                $this->getResponse($socket);
            }

            // AUTH LOGIN
            $this->sendCommand($socket, "AUTH LOGIN");
            $response = $this->getResponse($socket);
            
            if (strpos($response, '334') === false) {
                $this->lastError = "AUTH LOGIN falló: $response";
                fclose($socket);
                return false;
            }

            // Usuario
            $this->sendCommand($socket, base64_encode($this->smtpUser));
            $response = $this->getResponse($socket);
            
            if (strpos($response, '334') === false) {
                $this->lastError = "Usuario incorrecto: $response";
                fclose($socket);
                return false;
            }

            // Contraseña
            $this->sendCommand($socket, base64_encode($this->smtpPass));
            $response = $this->getResponse($socket);
            
            if (strpos($response, '235') === false) {
                $this->lastError = "Contraseña incorrecta: $response";
                fclose($socket);
                return false;
            }

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$this->smtpUser}>");
            $response = $this->getResponse($socket);
            
            if (strpos($response, '250') === false) {
                $this->lastError = "MAIL FROM falló: $response";
                fclose($socket);
                return false;
            }

            // RCPT TO
            $this->sendCommand($socket, "RCPT TO:<{$to}>");
            $response = $this->getResponse($socket);
            
            if (strpos($response, '250') === false) {
                $this->lastError = "RCPT TO falló: $response";
                fclose($socket);
                return false;
            }

            // DATA
            $this->sendCommand($socket, "DATA");
            $response = $this->getResponse($socket);
            
            if (strpos($response, '354') === false) {
                $this->lastError = "DATA falló: $response";
                fclose($socket);
                return false;
            }

            // Construir mensaje
            $headers = [
                "From: {$this->smtpFromName} <{$this->smtpUser}>",
                "To: {$to}",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "Content-Transfer-Encoding: base64",
                "Date: " . date('r'),
                "Message-ID: <" . md5(uniqid()) . "@frigotic>"
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";
            
            $this->sendCommand($socket, $message);
            $response = $this->getResponse($socket);
            
            if (strpos($response, '250') === false) {
                $this->lastError = "Envío falló: $response";
                fclose($socket);
                return false;
            }

            // QUIT
            $this->sendCommand($socket, "QUIT");
            fclose($socket);

            return true;

        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Enviar comando al servidor SMTP
     */
    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
        if ($this->debug) {
            error_log("SMTP >>> " . substr($command, 0, 50) . (strlen($command) > 50 ? '...' : ''));
        }
    }

    /**
     * Obtener respuesta del servidor SMTP
     */
    private function getResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Si el cuarto carácter es un espacio, es la última línea
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($this->debug) {
            error_log("SMTP <<< " . trim($response));
        }
        return $response;
    }

    /**
     * Obtener último error
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Enviar correo de bienvenida
     */
    public function sendWelcome(string $to, string $nombre, string $usuario, string $passwordTemporal): bool
    {
        // Obtener plantilla de la BD o usar default
        $plantilla = $this->getPlantilla('bienvenida');
        
        $asunto = $plantilla['asunto'];
        $cuerpo = $plantilla['cuerpo'];
        
        // Reemplazar variables (soportar todas las variantes de nombres de variables)
        $cuerpo = str_replace(
            [
                '{{nombre}}', '{{nombre_completo}}', 
                '{{usuario}}', '{{nombre_usuario}}', 
                '{{email}}', 
                '{{password_temporal}}', '{{password}}', '{{contraseña}}'
            ],
            [
                $nombre, $nombre, 
                $usuario, $usuario, 
                $to, 
                $passwordTemporal, $passwordTemporal, $passwordTemporal
            ],
            $cuerpo
        );
        
        return $this->send($to, $asunto, $cuerpo);
    }

    /**
     * Enviar confirmación de pago
     */
    public function sendPaymentConfirmation(string $to, string $nombre, float $cantidad, string $fecha, float $totalConsumos, string $productosHtml): bool
    {
        $plantilla = $this->getPlantilla('pago_confirmado');
        
        $asunto = $plantilla['asunto'];
        $cuerpo = $plantilla['cuerpo'];
        
        // Reemplazar variables (soportar variantes de nombres)
        $cuerpo = str_replace(
            [
                '{{nombre}}', '{{nombre_completo}}',
                '{{cantidad}}', '{{fecha}}', '{{total_consumos}}', '{{productos_consumidos}}'
            ],
            [
                $nombre, $nombre,
                number_format($cantidad, 2, ',', '.'), $fecha, number_format($totalConsumos, 2, ',', '.') . ' €', $productosHtml
            ],
            $cuerpo
        );
        
        return $this->send($to, $asunto, $cuerpo);
    }

    /**
     * Enviar recordatorio de pago
     */
    public function sendPaymentReminder(string $to, string $nombre, float $deuda, string $fechaDesde): bool
    {
        $plantilla = $this->getPlantilla('recordatorio_pago');
        
        $asunto = $plantilla['asunto'];
        $cuerpo = $plantilla['cuerpo'];
        
        // Reemplazar variables (soportar variantes de nombres)
        $cuerpo = str_replace(
            ['{{nombre}}', '{{nombre_completo}}', '{{deuda}}', '{{fecha_desde}}'],
            [$nombre, $nombre, number_format($deuda, 2, ',', '.'), $fechaDesde],
            $cuerpo
        );
        
        return $this->send($to, $asunto, $cuerpo);
    }

    /**
     * Obtener plantilla de la BD
     */
    private function getPlantilla(string $tipo): array
    {
        require_once APP_PATH . '/models/Database.php';
        $db = \App\Models\Database::getInstance();
        
        $plantilla = $db->fetch("SELECT * FROM plantillas_correo WHERE tipo = ?", [$tipo]);
        
        if ($plantilla) {
            return $plantilla;
        }
        
        // Plantillas por defecto
        $defaults = [
            'bienvenida' => [
                'asunto' => 'Bienvenido a FrigoTIC',
                'cuerpo' => '<h2>¡Hola {{nombre}}!</h2>
<p>Se ha creado tu cuenta en FrigoTIC.</p>
<p><strong>Usuario:</strong> {{usuario}}<br>
<strong>Contraseña temporal:</strong> {{password_temporal}}</p>
<p>Por favor, cambia tu contraseña la primera vez que accedas.</p>
<p>Saludos,<br>El equipo de FrigoTIC</p>'
            ],
            'pago_confirmado' => [
                'asunto' => 'Pago confirmado - FrigoTIC',
                'cuerpo' => '<h2>¡Hola {{nombre}}!</h2>
<p>Se ha registrado tu pago de <strong>{{cantidad}} €</strong>.</p>
<p><strong>Fecha:</strong> {{fecha}}</p>
<p>¡Gracias por usar FrigoTIC!</p>'
            ],
            'recordatorio_pago' => [
                'asunto' => 'Recordatorio de pago - FrigoTIC',
                'cuerpo' => '<h2>Hola {{nombre}},</h2>
<p>Te recordamos que tienes una deuda pendiente de <strong>{{deuda}} €</strong> en FrigoTIC.</p>
<p>Por favor, realiza el pago lo antes posible. Tienes un pago pendiente desde <strong>{{fecha_desde}}</strong>.</p>
<p>Ponte en contacto con el administrador para gestionarlo.</p>
<p>Saludos,<br>El administrador</p>'
            ]
        ];
        
        return $defaults[$tipo] ?? ['asunto' => 'FrigoTIC', 'cuerpo' => ''];
    }
}
