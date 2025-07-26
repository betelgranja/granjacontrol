<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener el último peso de un cerdo
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_ultimo_peso' && isset($_GET['idcerdo'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $idcerdo = intval($_GET['idcerdo']);
    
    // Obtener el último peso registrado
    $sql = "SELECT pesoactual FROM controlpeso 
            WHERE idcerdo = $1 
            ORDER BY fechacontrol DESC, id DESC 
            LIMIT 1";
    $result = pg_query_params($connection, $sql, [$idcerdo]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'ultimo_peso' => $row['pesoactual']]);
    } else {
        // Si no hay controles previos, obtener el peso inicial del cerdo
        $sql_cerdo = "SELECT pesoinicial FROM cerdo WHERE id = $1";
        $result_cerdo = pg_query_params($connection, $sql_cerdo, [$idcerdo]);
        if ($row_cerdo = pg_fetch_assoc($result_cerdo)) {
            echo json_encode(['success' => true, 'ultimo_peso' => $row_cerdo['pesoinicial']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cerdo no encontrado']);
        }
    }
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $accion = $_POST['accion'] ?? '';
    
    try {
        switch ($accion) {
            case 'agregar':
                $idcerdo = intval($_POST['idcerdo'] ?? 0);
                $fechacontrol = $_POST['fechacontrol'] ?? date('Y-m-d');
                $pesoanterior = !empty($_POST['pesoanterior']) ? floatval($_POST['pesoanterior']) : null;
                $pesoactual = floatval($_POST['pesoactual'] ?? 0);
                $diferencia = !empty($_POST['diferencia']) ? floatval($_POST['diferencia']) : null;
                
                // Obtener nombre del cerdo
                $sql_cerdo = "SELECT nombre FROM cerdo WHERE id = $1";
                $result_cerdo = pg_query_params($connection, $sql_cerdo, [$idcerdo]);
                $cerdo = pg_fetch_assoc($result_cerdo);
                
                if ($idcerdo > 0 && $pesoactual > 0) {
                    $sql = "INSERT INTO controlpeso (idcerdo, nombre, fechacontrol, pesoanterior, pesoactual, diferencia) 
                            VALUES ($1, $2, $3, $4, $5, $6)";
                    $result = pg_query_params($connection, $sql, [
                        $idcerdo, 
                        $cerdo['nombre'], 
                        $fechacontrol, 
                        $pesoanterior, 
                        $pesoactual, 
                        $diferencia
                    ]);
                    
                    if ($result) {
                        $mensaje = "Control de peso agregado correctamente. Peso actual: " . number_format($pesoactual, 2) . " kg";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al agregar el control de peso";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar':
                $id = intval($_POST['id'] ?? 0);
                $idcerdo = intval($_POST['idcerdo'] ?? 0);
                $fechacontrol = $_POST['fechacontrol'] ?? date('Y-m-d');
                $pesoanterior = !empty($_POST['pesoanterior']) ? floatval($_POST['pesoanterior']) : null;
                $pesoactual = floatval($_POST['pesoactual'] ?? 0);
                $diferencia = !empty($_POST['diferencia']) ? floatval($_POST['diferencia']) : null;
                
                // Obtener nombre del cerdo
                $sql_cerdo = "SELECT nombre FROM cerdo WHERE id = $1";
                $result_cerdo = pg_query_params($connection, $sql_cerdo, [$idcerdo]);
                $cerdo = pg_fetch_assoc($result_cerdo);
                
                if ($id > 0 && $idcerdo > 0 && $pesoactual > 0) {
                    $sql = "UPDATE controlpeso SET idcerdo = $1, nombre = $2, fechacontrol = $3, 
                            pesoanterior = $4, pesoactual = $5, diferencia = $6 
                            WHERE id = $7";
                    $result = pg_query_params($connection, $sql, [
                        $idcerdo, 
                        $cerdo['nombre'], 
                        $fechacontrol, 
                        $pesoanterior, 
                        $pesoactual, 
                        $diferencia,
                        $id
                    ]);
                    
                    if ($result) {
                        $mensaje = "Control de peso actualizado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al actualizar el control de peso";
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
                    $sql = "DELETE FROM controlpeso WHERE id = $1";
                    $result = pg_query_params($connection, $sql, [$id]);
                    
                    if ($result) {
                        $mensaje = "Control de peso eliminado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar el control de peso";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "ID de control inválido";
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
$redirect_url = 'controlpeso.php';
// Mantener los parámetros de filtro
if (isset($_GET['idcerdo']) || isset($_GET['busqueda'])) {
    $params = [];
    if (isset($_GET['idcerdo'])) $params[] = 'idcerdo=' . $_GET['idcerdo'];
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