<?php
if (!defined('ABSPATH')) exit;

// Determina si estamos editando o añadiendo una nueva licencia
$is_editing = isset($editable_license) && $editable_license !== null;
?>
<div class="wrap">
    <h1>Gestor de Renovación de Licencias</h1>

    <h2><?php echo $is_editing ? 'Editar Licencia' : 'Añadir Nueva Licencia'; ?></h2>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="guardar_licencia">
        <?php if ($is_editing): ?>
            <input type="hidden" name="license_id" value="<?php echo esc_attr($editable_license->id); ?>">
        <?php endif; ?>
        <?php wp_nonce_field('guardar_licencia_nonce'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="nombre_software">Nombre del Software</label></th>
                    <td><input name="nombre_software" type="text" id="nombre_software" class="regular-text" value="<?php echo $is_editing ? esc_attr($editable_license->nombre_software) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="url_renovacion">URL de Renovación (Opcional)</label></th>
                    <td><input name="url_renovacion" type="url" id="url_renovacion" class="regular-text" value="<?php echo $is_editing ? esc_attr($editable_license->url_renovacion) : ''; ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="fecha_renovacion">Fecha de Renovación</label></th>
                    <td><input name="fecha_renovacion" type="date" id="fecha_renovacion" value="<?php echo $is_editing ? esc_attr($editable_license->fecha_renovacion) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="monto_pagar">Monto a Pagar</label></th>
                    <td><input name="monto_pagar" type="number" step="0.01" id="monto_pagar" class="regular-text" value="<?php echo $is_editing ? esc_attr($editable_license->monto_pagar) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="responsable_email">Email del Responsable</label></th>
                    <td><input name="responsable_email" type="email" id="responsable_email" class="regular-text" value="<?php echo $is_editing ? esc_attr($editable_license->responsable_email) : ''; ?>" required></td>
                </tr>
            </tbody>
        </table>
        <?php submit_button($is_editing ? 'Actualizar Licencia' : 'Guardar Licencia'); ?>
    </form>

    <hr>

    <h2>Licencias Registradas</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Software</th>
                <th>Próxima Renovación</th>
                <th>Días Faltantes</th>
                <th>Monto</th>
                <th>Responsable</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($licenses)): ?>
                <?php foreach ($licenses as $license):
                    // Crear URL para eliminar con nonce de seguridad
                    $delete_url = add_query_arg([
                        'action' => 'delete_licencia',
                        'license_id' => $license->id,
                        '_wpnonce' => wp_create_nonce('delete_licencia_nonce_' . $license->id)
                    ], admin_url('admin-post.php'));
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($license->nombre_software); ?></strong></td>
                        <td><?php echo esc_html($license->fecha_renovacion); ?></td>
                        <td class="status-<?php echo esc_attr($license->status); ?>">
                            <?php
                            if ($license->status === 'vencido') {
                                echo 'Vencido';
                            } else {
                                echo esc_html($license->dias_faltantes);
                            }
                            ?>
                        </td>
                        <td>$<?php echo esc_html($license->monto_pagar); ?></td>
                        <td><?php echo esc_html($license->responsable_email); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=gestor-renovaciones&action=edit&license_id=' . $license->id); ?>">Editar</a> |
                            <a href="<?php echo esc_url($delete_url); ?>" style="color: #a00;" onclick="return confirm('¿Estás seguro de que quieres eliminar esta licencia?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay licencias registradas todavía.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>