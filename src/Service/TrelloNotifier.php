<?php

namespace GestorRenovaciones\Service;

if (!defined('ABSPATH')) exit;

class TrelloNotifier implements NotifierInterface
{
    public function send(string $to, object $licenseData): bool
    {
        $options = get_option('gestor_renovaciones_options');
        $apiKey = $options['trello_apikey'] ?? '';
        $token = $options['trello_token'] ?? '';
        $listId = $to;
        $cta_link = admin_url('admin.php?page=gestor-renovaciones');
        $cta_text = 'Gestionar Licencia';

        if (!empty($licenseData->url_renovacion)) {
            $cta_link = $licenseData->url_renovacion;
            $cta_text = 'Renovar Licencia Ahora';
        }
        if (empty($apiKey) || empty($token) || empty($listId)) return false;

        $card_title = "[{$licenseData->site_name}]🚨 Renovación: {$licenseData->nombre_software} (Vence en {$licenseData->days_left} días)";

        $raw_description = "
        **¡Atención!** La renovación de la licencia para **{$licenseData->nombre_software}** es inminente.

        ---
        ### Detalles:
        - **Fecha de Renovación:** {$licenseData->fecha_renovacion}
        - **Días Restantes:** {$licenseData->days_left}
        - **Monto a Pagar:** {$licenseData->monto_pagar}
        ---
        **Acción Requerida:** Contactar al responsable y proceder con el pago de la renovación.
        ";
        $raw_description .= "\n\n**[➡️ {$cta_text}]({$cta_link})**";
        $card_description = preg_replace('/^\s+/m', '', $raw_description);
        $url = 'https://api.trello.com/1/cards';
        $args = [
            'body' => [
                'key' => $apiKey,
                'token' => $token,
                'idList' => $listId,
                'name' => $card_title,
                'desc' => $card_description
            ]
        ];

        $response = wp_remote_post($url, $args);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}
