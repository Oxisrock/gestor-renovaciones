<?php
namespace GestorRenovaciones\Service;

if (!defined('ABSPATH')) exit;

/**
 * Interface NotifierInterface
 * * Defines a contract for all notification classes.
 * Any class that implements this interface MUST have a 'send' method.
 * This allows the system to treat all notifiers (Email, Slack, Telegram, etc.)
 * in the same way, without needing to know how they work internally.
 */
interface NotifierInterface {
    /**
     * Envía una notificación basada en los datos de una licencia.
     *
     * @param string $to La dirección del destinatario (email, webhook, ID de lista).
     * @param object $licenseData Un objeto con toda la información de la licencia.
     * @return bool True si el envío fue exitoso.
     */
    public function send(string $to, object $licenseData): bool;
}