<?php

namespace GestorRenovaciones\Controller;

use GestorRenovaciones\Model\LicenseModel;

if (!defined('ABSPATH')) exit;

class AdminController
{
    private $model;

    public function __construct()
    {
        $this->model = new LicenseModel();
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_post_guardar_licencia', [$this, 'handleFormSubmission']);
        add_action('admin_post_delete_licencia', [$this, 'handleDeleteSubmission']);
        add_action('admin_notices', [$this, 'displayAdminNotices']);
        add_action('admin_init', [$this, 'setupSettingsPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
    }

    public function addAdminMenu()
    {
        add_menu_page('Gestor de Licencias', 'Gestor de Licencias', 'manage_options', 'gestor-renovaciones', [$this, 'renderAdminPage'], 'dashicons-backup', 20);
        add_submenu_page(
            'gestor-renovaciones',         // Slug del menú padre
            'Ajustes de Notificaciones',   // Título de la página
            'Ajustes',                     // Título del submenú
            'manage_options',              // Capacidad requerida
            'gestor-renovaciones-settings', // Slug de esta subpágina
            [$this, 'renderSettingsPage']  // Función que renderiza la vista
        );
    }

    public function setupSettingsPage()
    {
        // Registrar el grupo de ajustes
        register_setting('gestor_renovaciones_settings_group', 'gestor_renovaciones_options');

        // Sección para Slack
        add_settings_section('gr_slack_section', 'Ajustes de Slack', null, 'gestor-renovaciones-settings');
        add_settings_field('gr_slack_webhook', 'URL del Webhook', [$this, 'renderSlackWebhookField'], 'gestor-renovaciones-settings', 'gr_slack_section');

        // Sección para Trello
        add_settings_section('gr_trello_section', 'Ajustes de Trello', null, 'gestor-renovaciones-settings');
        add_settings_field('gr_trello_apikey', 'API Key', [$this, 'renderTrelloApiKeyField'], 'gestor-renovaciones-settings', 'gr_trello_section');
        add_settings_field('gr_trello_token', 'API Token', [$this, 'renderTrelloTokenField'], 'gestor-renovaciones-settings', 'gr_trello_section');
        add_settings_field('gr_trello_listid', 'ID de la Lista', [$this, 'renderTrelloListIdField'], 'gestor-renovaciones-settings', 'gr_trello_section');
    }

    public function enqueueAdminStyles($hook)
    {
        // Nos aseguramos de cargar el CSS solo en nuestra página del plugin
        if ($hook !== 'toplevel_page_gestor-renovaciones') {
            return;
        }
        // Asumimos que crearás un archivo css/admin-styles.css en tu plugin
        wp_enqueue_style(
            'gestor-renovaciones-admin-styles',
            GESTOR_RENOVACIONES_URL . 'css/admin-styles.css'
        );
    }

    public function renderSettingsPage()
    {
        require_once __DIR__ . '/../View/settings-page.php';
    }

    // Funciones para renderizar cada campo del formulario
    public function renderSlackWebhookField()
    {
        $options = get_option('gestor_renovaciones_options');
        echo '<input type="url" name="gestor_renovaciones_options[slack_webhook]" value="' . esc_attr($options['slack_webhook'] ?? '') . '" class="regular-text">';
    }
    public function renderTrelloApiKeyField()
    {
        $options = get_option('gestor_renovaciones_options');
        echo '<input type="text" name="gestor_renovaciones_options[trello_apikey]" value="' . esc_attr($options['trello_apikey'] ?? '') . '" class="regular-text">';
    }
    public function renderTrelloTokenField()
    {
        $options = get_option('gestor_renovaciones_options');
        echo '<input type="text" name="gestor_renovaciones_options[trello_token]" value="' . esc_attr($options['trello_token'] ?? '') . '" class="regular-text">';
    }
    public function renderTrelloListIdField()
    {
        $options = get_option('gestor_renovaciones_options');
        echo '<input type="text" name="gestor_renovaciones_options[trello_listid]" value="' . esc_attr($options['trello_listid'] ?? '') . '" class="regular-text">';
    }

    public function renderAdminPage()
    {
        $licenses = $this->model->getAll();
        $editable_license = null;

        // Si la acción es 'edit' y hay un ID, obtenemos los datos de esa licencia
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['license_id'])) {
            $editable_license = $this->model->getById((int)$_GET['license_id']);
        }

        require_once __DIR__ . '/../View/admin-page.php';
    }

    public function handleFormSubmission()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'guardar_licencia_nonce')) {
            wp_die('Error de seguridad.');
        }

        $data = [
            'nombre_software'   => sanitize_text_field($_POST['nombre_software']),
            'url_renovacion'       => esc_url_raw($_POST['url_renovacion']),
            'fecha_renovacion'  => sanitize_text_field($_POST['fecha_renovacion']),
            'monto_pagar'       => floatval($_POST['monto_pagar']),
            'responsable_email' => sanitize_email($_POST['responsable_email']),
        ];

        // Determinar si es una actualización o una nueva inserción
        $id = isset($_POST['license_id']) ? (int)$_POST['license_id'] : null;

        $success = $this->model->save($data, $id);

        $redirect_url = admin_url('admin.php?page=gestor-renovaciones');
        wp_redirect(add_query_arg('status', $success ? 'success' : 'error', $redirect_url));
        exit;
    }

    public function handleDeleteSubmission()
    {
        $id = (int)$_GET['license_id'];

        // Verificar el nonce para la eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_licencia_nonce_' . $id)) {
            wp_die('Error de seguridad.');
        }

        $success = $this->model->delete($id);

        $redirect_url = admin_url('admin.php?page=gestor-renovaciones');
        wp_redirect(add_query_arg('status', $success ? 'deleted' : 'error', $redirect_url));
        exit;
    }

    public function displayAdminNotices()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'gestor-renovaciones' || !isset($_GET['status'])) {
            return;
        }

        switch ($_GET['status']) {
            case 'success':
                echo '<div class="notice notice-success is-dismissible"><p>Licencia guardada correctamente.</p></div>';
                break;
            case 'deleted':
                echo '<div class="notice notice-success is-dismissible"><p>Licencia eliminada correctamente.</p></div>';
                break;
            case 'error':
                echo '<div class="notice notice-error is-dismissible"><p>Hubo un error al procesar la solicitud.</p></div>';
                break;
        }
    }
}
