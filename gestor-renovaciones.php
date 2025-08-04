<?php
/**
 * Plugin Name: Gestor de Renovaciones
 */

if (!defined('ABSPATH')) exit; // Salir si se accede directamente

define('GESTOR_RENOVACIONES_FILE', __FILE__);
define('GESTOR_RENOVACIONES_URL', plugin_dir_url(GESTOR_RENOVACIONES_FILE));

require_once __DIR__ . '/vendor/autoload.php';

use GestorRenovaciones\Controller\AdminController;
use GestorRenovaciones\Controller\CronController;

// Crear la tabla en la base de datos al activar el plugin
register_activation_hook(GESTOR_RENOVACIONES_FILE, ['GestorRenovaciones\Model\LicenseModel', 'createTable']);

// Inicializar los controladores
add_action('plugins_loaded', function() {
    new AdminController();
    new CronController();
});