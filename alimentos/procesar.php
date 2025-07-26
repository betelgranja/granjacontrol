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
                $nombrealimento = trim($_POST['nombrealimento'] ?? '');
                $fechacompra = $_POST['fechacompra'] ?? date('Y-m-d');
                $facturacompra = trim($_POST['facturacompra'] ?? '');
                $preciocompra = floatval($_POST['preciocompra'] ?? 0);
                
                if (!empty($nombrealimento) && $preciocompra > 0) {
                    $sql = "INSERT INTO alimentos (nombrealimento, fechacompra, facturacompra, preciocompra) 
                            VALUES ($1, $2, $3, $4)";
                    $result = pg_query_params($connection, $sql, [
                        $nombrealimento, 
                        $fechacompra, 
                        $facturacompra, 
                        $preciocompra
                    ]);
                    
                    if ($result) {
                        $mensaje = "Alimento agregado correctamente por " . formatearPesos($preciocompra);
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar el alimento";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id'] ?? 0);
                $nombrealimento = trim($_POST['nombrealimento'] ?? '');
                $fechacompra = $_POST['fechacompra'] ?? date('Y-m-d');
                $facturacompra = trim($_POST['facturacompra'] ?? '');
                $preciocompra = floatval($_POST['preciocompra'] ?? 0);
                
                if ($id > 0 && !empty($nombrealimento) && $preciocompra > 0) {
                    $sql = "UPDATE alimentos SET nombrealimento = $1, fechacompra = $2, 
                            facturacompra = $3, preciocompra = $4 
                            WHERE id = $5";
                    $result = pg_query_params($connection, $sql, [
                        $nombrealimento, 
                        $fechacompra, 
                        $facturacompra, 
                        $preciocompra,
                        $id
                    ]);
                    
                    if ($result) {
                        $mensaje = "Alimento actualizado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el alimento";
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
                    $sql = "DELETE FROM alimentos WHERE id = $1";
                    $result = pg_query_params($connection, $sql, [$id]);
                    
                    if ($result) {
                        $mensaje = "Alimento eliminado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar el alimento";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "ID de alimento inválido";
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
$redirect_url = 'alimentos.php';
if ($mensaje) {
    $redirect_url .= '?mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo_mensaje);
}
header('Location: ' . $redirect_url);
exit();
?>