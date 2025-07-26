<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php'; // Incluir funciones compartidas

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'agregar':
                $nombre = trim($_POST['nombre'] ?? '');
                $pesoinicial = floatval($_POST['pesoinicial'] ?? 0);
                $preciocompra = floatval($_POST['preciocompra'] ?? 0);
                $fechaingreso = $_POST['fechaingreso'] ?? date('Y-m-d');
                $observaciones = trim($_POST['observaciones'] ?? '');
                
                if (!empty($nombre) && $pesoinicial > 0 && $preciocompra > 0) {
                    $sql = "INSERT INTO cerdo (nombre, pesoinicial, preciocompra, fechaingreso, observaciones) VALUES ($1, $2, $3, $4, $5)";
                    $result = pg_query_params($connection, $sql, [$nombre, $pesoinicial, $preciocompra, $fechaingreso, $observaciones]);
                    
                    if ($result) {
                        $mensaje = "Cerdo agregado correctamente por " . formatearPesos($preciocompra);
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar el cerdo";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id'] ?? 0);
                $nombre = trim($_POST['nombre'] ?? '');
                $pesoinicial = floatval($_POST['pesoinicial'] ?? 0);
                $preciocompra = floatval($_POST['preciocompra'] ?? 0);
                $fechaingreso = $_POST['fechaingreso'] ?? date('Y-m-d');
                $observaciones = trim($_POST['observaciones'] ?? '');
                
                if ($id > 0 && !empty($nombre) && $pesoinicial > 0 && $preciocompra > 0) {
                    $sql = "UPDATE cerdo SET nombre = $1, pesoinicial = $2, preciocompra = $3, fechaingreso = $4, observaciones = $5 WHERE id = $6";
                    $result = pg_query_params($connection, $sql, [$nombre, $pesoinicial, $preciocompra, $fechaingreso, $observaciones, $id]);
                    
                    if ($result) {
                        $mensaje = "Cerdo actualizado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el cerdo";
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
                    $sql = "DELETE FROM cerdo WHERE id = $1";
                    $result = pg_query_params($connection, $sql, [$id]);
                    
                    if ($result) {
                        $mensaje = "Cerdo eliminado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar el cerdo";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "ID de cerdo inválido";
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
$redirect_url = '../cerdos/index3.php';
if ($mensaje) {
    $redirect_url .= '?mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo_mensaje);
}
header('Location: ' . $redirect_url);
exit();
?>