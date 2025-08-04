<?php
// src/View/settings-page.php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1>Ajustes de Notificaciones</h1>
    <p>Configura las credenciales para integrar el sistema de alertas con servicios externos.</p>

    <form action="options.php" method="post">
        <?php
        // Funciones de seguridad y para imprimir los campos registrados
        settings_fields('gestor_renovaciones_settings_group');
        do_settings_sections('gestor-renovaciones-settings');
        
        // Botón de guardado
        submit_button('Guardar Cambios');
        ?>
    </form>
</div>