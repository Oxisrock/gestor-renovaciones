<?php
namespace GestorRenovaciones\Service;

if (!defined('ABSPATH')) exit;

class EmailNotifier implements NotifierInterface {
    public function send(string $to, object $licenseData): bool {
        $subject = "[{$licenseData->site_name}] ⚠️ Alerta de Renovación: {$licenseData->nombre_software}";
        
        $message = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2 style='color: #d9534f;'>🚨 Alerta de Renovación Inminente</h2>
                <p>Hola,</p>
                <p>Este es un recordatorio automático de que la licencia para el software <strong>{$licenseData->nombre_software}</strong> está próxima a vencer.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='border-bottom: 1px solid #ddd;'>
                        <td style='padding: 8px;'>🗓️ <strong>Fecha de Renovación:</strong></td>
                        <td style='padding: 8px;'>{$licenseData->fecha_renovacion}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #ddd;'>
                        <td style='padding: 8px;'>⏳ <strong>Días Restantes:</strong></td>
                        <td style='padding: 8px;'><strong>{$licenseData->days_left}</strong></td>
                    </tr>
                    <tr style='border-bottom: 1px solid #ddd;'>
                        <td style='padding: 8px;'>💰 <strong>Monto a Pagar:</strong></td>
                        <td style='padding: 8px;'>{$licenseData->monto_pagar}</td>
                    </tr>
                </table>
                <p>Por favor, asegúrate de realizar la renovación a tiempo para evitar interrupciones en el servicio.</p>
                <p>Gracias,<br><em>Sistema de Notificaciones de {$licenseData->site_name}</em></p>
            </div>
        ";
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        return wp_mail($to, $subject, $message, $headers);
    }
}