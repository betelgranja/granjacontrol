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
            case 'guardar_configuracion':
                $id_config = intval($_POST['id_config'] ?? 0);
                $nitgranja = trim($_POST['nitgranja'] ?? '');
                $nombre = trim($_POST['nombre'] ?? '');
                $direccion = trim($_POST['direccion'] ?? '');
                $telefono = trim($_POST['telefono'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $logo = trim($_POST['logo'] ?? '');
                
                if (!empty($nombre)) {
                    if ($id_config > 0) {
                        // Actualizar configuración existente
                        $sql = "UPDATE configuracion SET nitgranja = $1, nombre = $2, direccion = $3, telefono = $4, email = $5, logo = $6 WHERE id = $7";
                        $result = pg_query_params($connection, $sql, [$nitgranja, $nombre, $direccion, $telefono, $email, $logo, $id_config]);
                    } else {
                        // Insertar nueva configuración
                        $sql = "INSERT INTO configuracion (nitgranja, nombre, direccion, telefono, email, logo) VALUES ($1, $2, $3, $4, $5, $6)";
                        $result = pg_query_params($connection, $sql, [$nitgranja, $nombre, $direccion, $telefono, $email, $logo]);
                    }
                    
                    if ($result) {
                        $mensaje = "Configuración guardada correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al guardar la configuración";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "El nombre de la granja es requerido";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'agregar_usuario':
                $usuario = trim($_POST['usuario'] ?? '');
                $contrasena = $_POST['contrasena'] ?? '';
                $contrasena_confirm = $_POST['contrasena_confirm'] ?? '';
                
                if (!empty($usuario) && !empty($contrasena)) {
                    if ($contrasena === $contrasena_confirm) {
                        // Verificar que el usuario no exista
                        $sql_check = "SELECT id FROM login WHERE usuario = $1";
                        $result_check = pg_query_params($connection, $sql_check, [$usuario]);
                        
                        if (pg_num_rows($result_check) == 0) {
                            $sql = "INSERT INTO login (usuario, contrasena) VALUES ($1, $2)";
                            $result = pg_query_params($connection, $sql, [$usuario, $contrasena]);
                            
                            if ($result) {
                                $mensaje = "Usuario agregado correctamente";
                                $tipo_mensaje = "success";
                            } else {
                                $mensaje = "Error al agregar el usuario";
                                $tipo_mensaje = "error";
                            }
                        } else {
                            $mensaje = "El nombre de usuario ya existe";
                            $tipo_mensaje = "error";
                        }
                    } else {
                        $mensaje = "Las contraseñas no coinciden";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'editar_usuario':
                $id = intval($_POST['id'] ?? 0);
                $usuario = trim($_POST['usuario'] ?? '');
                $contrasena = $_POST['contrasena'] ?? '';
                $contrasena_confirm = $_POST['contrasena_confirm'] ?? '';
                
                if ($id > 0 && !empty($usuario)) {
                    // Verificar que el usuario no exista (excepto el actual)
                    $sql_check = "SELECT id FROM login WHERE usuario = $1 AND id != $2";
                    $result_check = pg_query_params($connection, $sql_check, [$usuario, $id]);
                    
                    if (pg_num_rows($result_check) == 0) {
                        if (!empty($contrasena)) {
                            if ($contrasena === $contrasena_confirm) {
                                $sql = "UPDATE login SET usuario = $1, contrasena = $2 WHERE id = $3";
                                $params = [$usuario, $contrasena, $id];
                            } else {
                                $mensaje = "Las contraseñas no coinciden";
                                $tipo_mensaje = "error";
                                break;
                            }
                        } else {
                            $sql = "UPDATE login SET usuario = $1 WHERE id = $2";
                            $params = [$usuario, $id];
                        }
                        
                        $result = pg_query_params($connection, $sql, $params);
                        
                        if ($result) {
                            $mensaje = "Usuario actualizado correctamente";
                            $tipo_mensaje = "success";
                        } else {
                            $mensaje = "Error al actualizar el usuario";
                            $tipo_mensaje = "error";
                        }
                    } else {
                        $mensaje = "El nombre de usuario ya existe";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "Por favor complete todos los campos requeridos";
                    $tipo_mensaje = "error";
                }
                break;
                
            case 'eliminar_usuario':
                $id = intval($_POST['id'] ?? 0);
                
                // Evitar eliminar el usuario actual
                if ($id > 0 && $id != $_SESSION['user_id']) {
                    $sql = "DELETE FROM login WHERE id = $1";
                    $result = pg_query_params($connection, $sql, [$id]);
                    
                    if ($result) {
                        $mensaje = "Usuario eliminado correctamente";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al eliminar el usuario";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "No se puede eliminar el usuario actual";
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
$redirect_url = 'configuracion.php';
if ($mensaje) {
    $redirect_url .= '?mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo_mensaje);
}
header('Location: ' . $redirect_url);
exit();
?>