<?php

namespace GestorRenovaciones\Controller;

use GestorRenovaciones\Model\LicenseModel;
use GestorRenovaciones\Service\EmailNotifier;
use GestorRenovaciones\Service\SlackNotifier;
use GestorRenovaciones\Service\TrelloNotifier;
use DateTime; // Asegúrate de tener el use para la clase DateTime

if (!defined('ABSPATH')) exit;

class CronController
{

    public function __construct()
    {
        // Registra el evento de cron si no existe
        if (!wp_next_scheduled('check_license_renewals')) {
            wp_schedule_event(time(), 'daily', 'check_license_renewals');
        }

        // Asocia la acción con nuestro método
        add_action('check_license_renewals', [$this, 'checkAndNotify']);
    }

    public function checkAndNotify()
    {
        // ===== INICIO DEL SEGURO ANTI-DUPLICADOS =====
        // Comprobamos si hay un proceso de notificación ya en marcha. Si es así, salimos.
        if (get_transient('gestor_renovaciones_cron_running')) {
            error_log('Cron de Licencias: Intento de ejecución duplicada. Omitiendo.');
            return;
        }
        // Si no, creamos un "seguro" que dura 1 minuto para bloquear otras ejecuciones.
        set_transient('gestor_renovaciones_cron_running', true, 60);
        // ===== FIN DEL SEGURO ANTI-DUPLICADOS =====

        $model = new LicenseModel();
        $notification_days = [30, 15, 7, 1];
        $upcomingLicenses = $model->getUpcomingRenewals($notification_days);

        if (empty($upcomingLicenses)) {
            error_log('Cron de Licencias: Ejecutado sin licencias por vencer en los umbrales definidos.');
            delete_transient('gestor_renovaciones_cron_running'); // Liberamos el seguro
            return;
        }

        $options = get_option('gestor_renovaciones_options');
        $site_name = get_bloginfo('name');

        $notifiers = [];

        $notifiers[] = new EmailNotifier();

        if (!empty($options['slack_webhook'])) {
            $notifiers[] = new SlackNotifier();
        }
        if (!empty($options['trello_apikey']) && !empty($options['trello_token']) && !empty($options['trello_listid'])) {
            $notifiers[] = new TrelloNotifier();
        }

        foreach ($upcomingLicenses as $license) {
            $today = new DateTime('today');
            $renewal_date = new DateTime($license->fecha_renovacion);
            if ($today > $renewal_date) continue;

            $days_left = $today->diff($renewal_date)->days;

            if ((int)$license->ultima_notificacion_dias === $days_left) {
                error_log("Cron de Licencias: Notificación para {$license->nombre_software} ({$days_left} días) ya fue enviada. Omitiendo.");
                continue;
            }


            $licenseData = (object)[
                'nombre_software'   => $license->nombre_software,
                'url_renovacion' => $license->url_renovacion,
                'fecha_renovacion'  => $license->fecha_renovacion,
                'days_left'         => $days_left,
                'monto_pagar'       => $license->monto_pagar,
                'site_name'         => $site_name
            ];

            $notification_sent_successfully = false;

            foreach ($notifiers as $notifier) {
                $recipient = '';
                if ($notifier instanceof \GestorRenovaciones\Service\EmailNotifier) $recipient = $license->responsable_email;
                if ($notifier instanceof \GestorRenovaciones\Service\TrelloNotifier) $recipient = $options['trello_listid'];
                if ($notifier instanceof \GestorRenovaciones\Service\SlackNotifier) $recipient = $options['slack_webhook'];

                if ($recipient) {
                    if ($notifier->send($recipient, $licenseData)) {
                        $notification_sent_successfully = true;
                    }
                }
                if ($notification_sent_successfully) {
                    $model->updateNotificationStatus($license->id, $days_left);
                    error_log("Cron de Licencias: Notificación enviada para {$license->nombre_software} y estado actualizado a {$days_left} días.");
                }
            }
        }

        // Al finalizar todo el proceso, eliminamos el seguro para la próxima ejecución.
        delete_transient('gestor_renovaciones_cron_running');
    }
}
