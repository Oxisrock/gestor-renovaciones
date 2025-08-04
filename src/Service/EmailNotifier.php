<?php

namespace GestorRenovaciones\Service;

if (!defined('ABSPATH')) exit;

class EmailNotifier implements NotifierInterface
{

    public function send(string $to, object $licenseData): bool
    {

        // --- Lógica de Contenido Dinámico ---
        $urgency_color = '#3498db'; // Azul por defecto
        $urgency_title = 'Recordatorio de Renovación';
        $emoji = '🔔';

        if ($licenseData->days_left <= 7) {
            $urgency_color = '#f39c12'; // Naranja para urgente
            $urgency_title = 'Alerta de Renovación';
            $emoji = '⚠️';
        }
        if ($licenseData->days_left <= 1) {
            $urgency_color = '#d9534f'; // Rojo para crítico
            $urgency_title = '¡Último Día para Renovar!';
            $emoji = '🚨';
        }

        $cta_link = admin_url('admin.php?page=gestor-renovaciones');
        $cta_text = 'Gestionar Licencia';

        if (!empty($licenseData->url_renovacion)) {
            $cta_link = $licenseData->url_renovacion;
            $cta_text = 'Renovar Licencia Ahora';
        }

        $subject = "[{$licenseData->site_name}] {$emoji} {$urgency_title}: {$licenseData->nombre_software}";

        // --- Estructura del Correo HTML ---
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f4f4f4;'>
            <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; margin: 20px auto; border: 1px solid #cccccc;'>
                <tr>
                    <td align='center' bgcolor='{$urgency_color}' style='padding: 20px 0; color: #ffffff; font-size: 24px; font-family: Arial, sans-serif;'>
                        <b>{$emoji} {$urgency_title}</b>
                    </td>
                </tr>
                <tr>
                    <td bgcolor='#ffffff' style='padding: 40px 30px; font-family: Arial, sans-serif; line-height: 1.6; font-size: 16px;'>
                        <p>Hola,</p>
                        <p>Este es un recordatorio automático de que la licencia para <strong>{$licenseData->nombre_software}</strong> está próxima a vencer.</p>
                        
                        <table border='0' cellpadding='10' cellspacing='0' width='100%' style='border: 1px solid #dddddd; margin: 20px 0;'>
                            <tr>
                                <td width='250' style='background-color: #f9f9f9;'>🗓️ <strong>Fecha de Renovación:</strong></td>
                                <td style='text-align: end;'>{$licenseData->fecha_renovacion}</td>
                            </tr>
                            <tr>
                                <td style='background-color: #f9f9f9;'>⏳ <strong>Días Restantes:</strong></td>
                                <td style='text-align: end;'><strong style='color: {$urgency_color};'>{$licenseData->days_left}</strong></td>
                            </tr>
                            <tr>
                                <td style='background-color: #f9f9f9;'>💰 <strong>Monto a Pagar:</strong></td>
                                <td style='text-align: end;'>{$licenseData->monto_pagar}</td>
                            </tr>
                        </table>
                        
                        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                            <tr>
                                <td align='center' style='padding: 20px 0;'>
                                    <a href='" . esc_url($cta_link) . "' style='background-color: #3498db; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                                        " . esc_html($cta_text) . "
                                    </a>
                                </td>
                            </tr>
                        </table>
                        
                        <p>Por favor, asegúrate de realizar la renovación a tiempo para evitar interrupciones en el servicio.</p>
                    </td>
                </tr>
                <tr>
                    <td bgcolor='#eeeeee' style='padding: 20px 30px; text-align: center; color: #666666; font-family: Arial, sans-serif; font-size: 12px;'>
                        <p>Este es un mensaje automático del Sistema de Notificaciones de <em>{$licenseData->site_name}</em>.</p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        return wp_mail($to, $subject, $message, $headers);
    }
}
