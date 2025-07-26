<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'agregar':
                $nombregasto = trim($_POST['nombregasto'] ?? '');
                $tipogasto = trim($_POST['tipogasto'] ?? '');
                $fechagasto = $_POST['fechagasto'] ?? date('Y-m-d');
                $valorgasto = floatval($_POST['valorgasto'] ?? 0);
                
                if (!empty($nombregasto) && !empty($tipogasto) && $valorgasto > 0) {
                    $sql = "INSERT INTO gastos (nombregasto, tipogasto, fechagasto, valorgasto) 
                            VALUES ($1, $2, $3, $4)";
                    $result = pg_query_params($connection, $sql, [
                        $nombregasto, 
                        $tipogasto, 
                        $fechagasto, 
                        $valorgasto
                    ]);
                    
                    if ($result) {
                        $mensaje = "Gasto agregado correctamente por " . formatearPesos($valorgasto);
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar el gasto";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id'] ?? 0);
                $nombregasto = trim($_POST['nombregasto'] ?? '');
                $tipogasto = trim($_POST['tipogasto'] ?? '');
                $fechagasto = $_POST['fechagasto'] ?? date('Y-m-d');
                $valorgasto = floatval($_POST['valorgasto'] ?? 0);
                
                if ($id > 0 && !empty($nombregasto) && !empty($tipogasto) && $valorgasto > 0) {
                    $sql = "UPDATE gastos SET nombregasto = $1, tipogasto = $2, 
                            fechagasto = $3, valorgasto = $4 
                            WHERE id = $5";
                    $result = pg_query_params($connection, $sql, [
                        $nombregasto, 
                        $tipogasto, 
                        $fechagasto, 
                        $valorgasto,
                        $id
                    ]);
                    
                    if ($result) {
                        $mensaje = "Gasto actualizado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el gasto";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'eliminar':
                $id = intval($_POST['id'] ?? 0);
                
                if ($id > 0) {
                    $sql = "DELETE FROM gastos WHERE id = $1";
                    $result = pg_query_params($connection, $sql, [$id]);
                    
                    if ($result) {
                        $mensaje = "Gasto eliminado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar el gasto";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "ID de gasto inválido";
                    $tipo_mensaje = "error";
                }
                break;
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Redirigir de vuelta a la página principal
$redirect_url = 'gastos.php';
// Mantener los parámetros de filtro
if (isset($_GET['tipo']) || isset($_GET['busqueda'])) {
    $params = [];
    if (isset($_GET['tipo'])) $params[] = 'tipo=' . $_GET['tipo'];
    if (isset($_GET['busqueda'])) $params[] = 'busqueda=' . urlencode($_GET['busqueda']);
    if (!empty($params)) {
        $redirect_url .= '?' . implode('&', $params);
    }
}

if ($mensaje) {
    $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . 'mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo_mensaje);
}

header('Location: ' . $redirect_url);
exit();
?>