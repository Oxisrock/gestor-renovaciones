<?php

namespace GestorRenovaciones\Model;

class LicenseModel
{
    public static function getTableName()
    {
        global $wpdb;
        return $wpdb->prefix . 'licencias_software';
    }

    public static function createTable()
    {
        global $wpdb;
        $tableName = self::getTableName();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre_software varchar(255) NOT NULL,
            url_renovacion varchar(255) DEFAULT NULL,
            fecha_renovacion date NOT NULL,
            monto_pagar decimal(10, 2) NOT NULL,
            responsable_email varchar(255) NOT NULL,
            ultima_notificacion_dias INT DEFAULT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function getUpcomingRenewals(array $days)
    {
        global $wpdb;
        $tableName = self::getTableName();

        // 1. Asegurarnos de que todos los valores del array sean números enteros para seguridad.
        $safe_days = array_map('intval', $days);

        // 2. Crear la lista de números para la cláusula IN (ej: "30, 15, 7, 1")
        $days_list = implode(', ', $safe_days);

        // 3. Construir la consulta directamente (ya es segura porque solo usamos números)
        $sql = "SELECT * FROM $tableName WHERE DATEDIFF(fecha_renovacion, CURDATE()) IN ($days_list)";

        // 4. Ejecutar la consulta
        return $wpdb->get_results($sql);
    }

    /**
     * Obtiene todos los registros de licencias de la base de datos.
     */
    public function getAll()
    {
        global $wpdb;
        $tableName = self::getTableName();
        $sql = "SELECT *, DATEDIFF(fecha_renovacion, CURDATE()) AS dias_faltantes FROM $tableName ORDER BY fecha_renovacion ASC";
        $licenses = $wpdb->get_results($sql);

        // Iteramos sobre los resultados para enriquecer cada objeto con su estado
        foreach ($licenses as $license) {
            $this->addStatusToLicense($license);
        }

        return $licenses;
    }

    /**
     * Obtiene una única licencia por su ID.
     * @param int $id El ID de la licencia.
     * @return object|null El objeto de la licencia o null si no se encuentra.
     */
    public function getById(int $id)
    {
        global $wpdb;
        $tableName = self::getTableName();
        $sql = $wpdb->prepare(
            "SELECT *, DATEDIFF(fecha_renovacion, CURDATE()) AS dias_faltantes FROM $tableName WHERE id = %d",
            $id
        );
        $license = $wpdb->get_row($sql);

        // Si encontramos la licencia, le añadimos el estado antes de devolverla
        if ($license) {
            $this->addStatusToLicense($license);
        }

        return $license;
    }

    /**
     * Guarda o actualiza un registro de licencia.
     * @param array $data Los datos de la licencia a guardar.
     * @param int|null $id El ID de la licencia a actualizar. Si es null, se crea una nueva.
     * @return bool True si la operación fue exitosa, false en caso contrario.
     */
    public function save(array $data, ?int $id = null)
    {
        global $wpdb;
        $tableName = self::getTableName();

        if ($id) {
            // Actualizar un registro existente
            $result = $wpdb->update($tableName, $data, ['id' => $id]);
        } else {
            // Insertar un nuevo registro
            $result = $wpdb->insert($tableName, $data);
        }

        return $result !== false;
    }

    /**
     * Elimina una licencia de la base de datos.
     * @param int $id El ID de la licencia a eliminar.
     * @return bool True si la eliminación fue exitosa, false en caso contrario.
     */
    public function delete(int $id): bool
    {
        global $wpdb;
        $tableName = self::getTableName();
        $result = $wpdb->delete($tableName, ['id' => $id], ['%d']);
        return $result !== false;
    }

    /**
     * Actualiza el estado de la última notificación enviada para una licencia.
     * @param int $id El ID de la licencia.
     * @param int $days Los días para los que se envió la notificación.
     * @return bool
     */
    public function updateNotificationStatus(int $id, int $days): bool
    {
        global $wpdb;
        $tableName = self::getTableName();

        $result = $wpdb->update(
            $tableName,
            ['ultima_notificacion_dias' => $days], // Datos a actualizar
            ['id' => $id]                         // Condición WHERE
        );

        return $result !== false;
    }

    private function addStatusToLicense(object &$license)
    {
        $dias_faltantes = (int) $license->dias_faltantes;

        if ($dias_faltantes < 0) {
            $license->status = 'vencido';
        } elseif ($dias_faltantes <= 7) {
            $license->status = 'urgente';
        } else {
            $license->status = 'ok';
        }
    }
}
