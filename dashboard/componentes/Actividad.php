<?php
// =====================================================
// Historial de actividad compartido (lo usa el dashboard).
// Guardar en: dashboard/componentes/actividad.php
// Crea la tabla sola la primera vez. Nunca rompe la página si algo falla.
// =====================================================

function registrar_actividad($conexion, $modulo, $detalle, $tipo) {
    try {
        $conexion->query("CREATE TABLE IF NOT EXISTS historial_actividad (
            id INT AUTO_INCREMENT PRIMARY KEY,
            modulo VARCHAR(30) NOT NULL,
            detalle VARCHAR(255) NOT NULL,
            tipo VARCHAR(20) NOT NULL,
            fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $stmt = $conexion->prepare("INSERT INTO historial_actividad (modulo, detalle, tipo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $modulo, $detalle, $tipo);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // se ignora
    }
}

function nombre_cliente_actividad($conexion, $cliente_id) {
    try {
        $stmt = $conexion->prepare("SELECT nombres FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $cliente_id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila['nombres'] ?? 'cliente';
    } catch (Throwable $e) {
        return 'cliente';
    }
}