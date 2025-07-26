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
                $idecerdo = intval($_POST['idecerdo'] ?? 0);
                $fechaaplicacion = $_POST['fechaaplicacion'] ?? date('Y-m-d');
                $nombrevacuna = trim($_POST['nombrevacuna'] ?? '');
                $dosis = trim($_POST['dosis'] ?? '');
                $valorvacuna = floatval($_POST['valorvacuna'] ?? 0);
                $proximaaplicacion = !empty($_POST['proximaaplicacion']) ? $_POST['proximaaplicacion'] : null;
                
                // Obtener nombre del cerdo
                $sql_cerdo = "SELECT nombre FROM cerdo WHERE id = $1";
                $result_cerdo = pg_query_params($connection, $sql_cerdo, [$idecerdo]);
                $cerdo = pg_fetch_assoc($result_cerdo);
                
                if ($idecerdo > 0 && !empty($nombrevacuna) && $valorvacuna >= 0) {
                    $sql = "INSERT INTO vacunas (idecerdo, nombrecerdo, fechaaplicacion, nombrevacuna, dosis, valorvacuna, proximaaplicacion) 
                            VALUES ($1, $2, $3, $4, $5, $6, $7)";
                    $result = pg_query_params($connection, $sql, [
                        $idecerdo, 
                        $cerdo['nombre'], 
                        $fechaaplicacion, 
                        $nombrevacuna, 
                        $dosis, 
                        $valorvacuna, 
                        $proximaaplicacion
                    ]);
                    
                    if ($result) {
                        $mensaje = "Vacuna agregada correctamente por " . formatearPesos($valorvacuna);
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar la vacuna";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id'] ?? 0);
                $idecerdo = intval($_POST['idecerdo'] ?? 0);
                $fechaaplicacion = $_POST['fechaaplicacion'] ?? date('Y-m-d');
                $nombrevacuna = trim($_POST['nombrevacuna'] ?? '');
                $dosis = trim($_POST['dosis'] ?? '');
                $valorvacuna = floatval($_POST['valorvacuna'] ?? 0);
                $proximaaplicacion = !empty($_POST['proximaaplicacion']) ? $_POST['proximaaplicacion'] : null;
                
                // Obtener nombre del cerdo
                $sql_cerdo = "SELECT nombre FROM cerdo WHERE id = $1";
                $result_cerdo = pg_query_params($connection, $sql_cerdo, [$idecerdo]);
                $cerdo = pg_fetch_assoc($result_cerdo);
                
                if ($id > 0 && $idecerdo > 0 && !empty($nombrevacuna) && $valorvacuna >= 0) {
                    $sql = "UPDATE vacunas SET idecerdo = $1, nombrecerdo = $2, fechaaplicacion = $3, 
                            nombrevacuna = $4, dosis = $5, valorvacuna = $6, proximaaplicacion = $7 
                            WHERE id = $8";
                    $result = pg_query_params($connection, $sql, [
                        $idecerdo, 
                        $cerdo['nombre'], 
                        $fechaaplicacion, 
                        $nombrevacuna, 
                        $dosis, 
                        $valorvacuna, 
                        $proximaaplicacion,
                        $id
                    ]);
                    
                    if ($result) {
                        $mensaje = "Vacuna actualizada correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar la vacuna";
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
                    $sql = "DELETE FROM vacunas WHERE id = $1";
                    $result = pg_query_params($connection, $sql, [$id]);
                    
                    if ($result) {
                        $mensaje = "Vacuna eliminada correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar la vacuna";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "ID de vacuna inválido";
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
$redirect_url = 'vacuna.php';
if ($mensaje) {
    $redirect_url .= '?mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo_mensaje);
}
header('Location: ' . $redirect_url);
exit();
?>