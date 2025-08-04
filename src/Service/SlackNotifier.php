<?php

namespace GestorRenovaciones\Service;

if (!defined('ABSPATH')) exit;

class SlackNotifier implements NotifierInterface
{
    public function send(string $to, object $licenseData): bool
    {
        $webhook_url = $to;

        // Emoji de alerta según los días restantes
        $emoji = $licenseData->days_left <= 7 ? ':rotating_light:' : ':warning:';

        $message = "
        *[{$licenseData->site_name}] {$emoji} Alerta de Renovación: {$licenseData->nombre_software}*
        > Este es un recordatorio automático de que una licencia está próxima a vencer.

        • *Fecha de Renovación:* {$licenseData->fecha_renovacion}
        • *Días Restantes:* *{$licenseData->days_left}*
        • *Monto a Pagar:* {$licenseData->monto_pagar}

        Por favor, contactar al responsable para asegurar la renovación a tiempo.
        Sistema de Notificaciones de {$licenseData->site_name}
        ";

        $payload = ['text' => $message];
        $args = ['body' => json_encode($payload), 'headers' => ['Content-Type' => 'application/json']];
        $response = wp_remote_post($webhook_url, $args);

        // ... (código de depuración y retorno)
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}
