<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener datos de configuración
function obtenerConfiguracion($connection) {
    $sql = "SELECT * FROM configuracion LIMIT 1";
    $result = pg_query($connection, $sql);
    return pg_fetch_assoc($result);
}

// Función para obtener todos los usuarios
function obtenerUsuarios($connection, $busqueda = '') {
    if (!empty($busqueda)) {
        $sql = "SELECT id, usuario, '******' as contrasena FROM login WHERE usuario ILIKE $1 ORDER BY usuario";
        $result = pg_query_params($connection, $sql, ["%$busqueda%"]);
    } else {
        $sql = "SELECT id, usuario, '******' as contrasena FROM login ORDER BY usuario";
        $result = pg_query($connection, $sql);
    }
    
    $usuarios = [];
    while ($row = pg_fetch_assoc($result)) {
        $usuarios[] = $row;
    }
    return $usuarios;
}

// Función para obtener un usuario específico (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_usuario' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $id = intval($_GET['id']);
    
    $sql = "SELECT id, usuario FROM login WHERE id = $1";
    $result = pg_query_params($connection, $sql, [$id]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
    exit();
}

// Función para obtener un registro de configuración específico (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_configuracion') {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    
    $sql = "SELECT * FROM configuracion LIMIT 1";
    $result = pg_query($connection, $sql);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Configuración no encontrada']);
    }
    exit();
}

// Función para eliminar usuario
function eliminarUsuario($connection, $id) {
    // Evitar eliminar el usuario actual
    if ($id == $_SESSION['user_id']) {
        return false;
    }
    
    $sql = "DELETE FROM login WHERE id = $1";
    return pg_query_params($connection, $sql, [$id]);
}

// Procesar acciones
if (isset($_POST['accion'])) {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $accion = $_POST['accion'];
    
    switch ($accion) {
        case 'eliminar_usuario':
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0 && $id != $_SESSION['user_id']) {
                if (eliminarUsuario($connection, $id)) {
                    $mensaje = "Usuario eliminado correctamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "No se puede eliminar el usuario actual o el usuario no existe";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "ID de usuario inválido";
                $tipo_mensaje = "error";
            }
            break;
            
        case 'guardar_configuracion':
            $id_config = intval($_POST['id_config'] ?? 0);
            $nitgranja = trim($_POST['nitgranja'] ?? '');
            $nombre = trim($_POST['nombre'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (!empty($nombre)) {
                if ($id_config > 0) {
                    // Actualizar configuración existente
                    $sql = "UPDATE configuracion SET nitgranja = $1, nombre = $2, direccion = $3, telefono = $4, email = $5 WHERE id = $6";
                    $result = pg_query_params($connection, $sql, [$nitgranja, $nombre, $direccion, $telefono, $email, $id_config]);
                } else {
                    // Insertar nueva configuración
                    $sql = "INSERT INTO configuracion (nitgranja, nombre, direccion, telefono, email) VALUES ($1, $2, $3, $4, $5)";
                    $result = pg_query_params($connection, $sql, [$nitgranja, $nombre, $direccion, $telefono, $email]);
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
    }
}

// Obtener datos
$db = new DatabaseConnection();
$connection = $db->getConnection();
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$configuracion = obtenerConfiguracion($connection);
$usuarios = obtenerUsuarios($connection, $busqueda);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Granja Porcina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../dashboard/">
                <i class="fas fa-piggy-bank me-2"></i>Granja Porcina
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                <li class="nav-item">
                        <a class="nav-link" href="../dashboard/index2.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../cerdos/index3.php"><i class="fas fa-piggy-bank me-1"></i>Cerdos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../controlpeso/controlpeso.php"><i class="fas fa-weight me-1"></i>Control de Peso</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../vacunas/vacuna.php"><i class="fas fa-syringe me-1"></i>Vacunas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../alimentos/alimentos.php"><i class="fas fa-utensils me-1"></i>Alimentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../gastos/gastos.php"><i class="fas fa-money-bill-wave me-1"></i>Gastos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../configuracion/configuracion.php"><i class="fas fa-cog me-1"></i>Configuración</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2 class="mb-0">
                    <i class="fas fa-cog me-2 text-secondary"></i>Configuración del Sistema
                </h2>
                <hr class="my-3">
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($_GET['mensaje']) || isset($mensaje)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-<?php echo (isset($_GET['tipo']) ? $_GET['tipo'] : (isset($tipo_mensaje) ? $tipo_mensaje : 'info')); ?> alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-<?php echo (isset($_GET['tipo']) && $_GET['tipo'] == 'success') || (isset($tipo_mensaje) && $tipo_mensaje == 'success') ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars(isset($_GET['mensaje']) ? $_GET['mensaje'] : $mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs de configuración -->
        <div class="row mb-4">
            <div class="col-md-12">
                <ul class="nav nav-tabs" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
                            <i class="fas fa-building me-2"></i>Datos de la Granja
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>Usuarios del Sistema
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content" id="configTabsContent">
            <!-- Tab Datos de la Granja -->
            <div class="tab-pane fade show active" id="datos" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2 text-secondary"></i>Información de la Granja
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="procesar.php">
                                    <input type="hidden" name="accion" value="guardar_configuracion">
                                    <input type="hidden" name="id_config" value="<?php echo $configuracion['id'] ?? ''; ?>">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-id-card me-1 text-primary"></i>NIT de la Granja
                                            </label>
                                            <input type="text" class="form-control form-control-lg border-primary" 
                                                   name="nitgranja" value="<?php echo htmlspecialchars($configuracion['nitgranja'] ?? ''); ?>"
                                                   placeholder="Ej: 123456789-0">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-signature me-1 text-success"></i>Nombre de la Granja *
                                            </label>
                                            <input type="text" class="form-control form-control-lg border-success" 
                                                   name="nombre" value="<?php echo htmlspecialchars($configuracion['nombre'] ?? ''); ?>"
                                                   required placeholder="Ej: Granja Porcina S.A.S">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-map-marker-alt me-1 text-warning"></i>Dirección
                                        </label>
                                        <input type="text" class="form-control form-control-lg border-warning" 
                                               name="direccion" value="<?php echo htmlspecialchars($configuracion['direccion'] ?? ''); ?>"
                                               placeholder="Ej: Carrera 1 # 2-3, Municipio, Departamento">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-phone me-1 text-info"></i>Teléfono
                                            </label>
                                            <input type="text" class="form-control form-control-lg border-info" 
                                                   name="telefono" value="<?php echo htmlspecialchars($configuracion['telefono'] ?? ''); ?>"
                                                   placeholder="Ej: (300) 123-4567">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-envelope me-1 text-danger"></i>Correo Electrónico
                                            </label>
                                            <input type="email" class="form-control form-control-lg border-danger" 
                                                   name="email" value="<?php echo htmlspecialchars($configuracion['email'] ?? ''); ?>"
                                                   placeholder="Ej: info@granjaporcina.com">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-image me-1 text-secondary"></i>Logo (URL)
                                        </label>
                                        <input type="text" class="form-control form-control-lg border-secondary" 
                                               name="logo" value="<?php echo htmlspecialchars($configuracion['logo'] ?? ''); ?>"
                                               placeholder="Ej: https://ejemplo.com/logo.png">
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" onclick="cargarConfiguracion()">
                                            <i class="fas fa-sync me-1"></i>Cargar Datos Actuales
                                        </button>
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save me-1"></i>Guardar Configuración
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-eye me-2 text-info"></i>Vista Previa
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <?php if (!empty($configuracion['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($configuracion['logo']); ?>" 
                                         alt="Logo" class="img-fluid mb-3" style="max-height: 100px;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                         style="width: 100px; height: 100px;">
                                        <i class="fas fa-piggy-bank fa-2x text-success"></i>
                                    </div>
                                <?php endif; ?>
                                <h4 class="text-success"><?php echo htmlspecialchars($configuracion['nombre'] ?? 'Nombre de la Granja'); ?></h4>
                                <p class="text-muted">
                                    <?php if (!empty($configuracion['nitgranja'])): ?>
                                        <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($configuracion['nitgranja']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($configuracion['direccion'])): ?>
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($configuracion['direccion']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($configuracion['telefono'])): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($configuracion['telefono']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($configuracion['email'])): ?>
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($configuracion['email']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Usuarios del Sistema -->
            <div class="tab-pane fade" id="usuarios" role="tabpanel">
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="search-box" style="width: 300px;">
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white border-secondary">
                                        <i class="fas fa-search text-secondary"></i>
                                    </span>
                                    <input type="text" id="busqueda" class="form-control form-control-lg border-secondary" 
                                           placeholder="Buscar usuarios..." value="<?php echo htmlspecialchars($busqueda); ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="btnBuscar">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <button class="btn btn-success btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarUsuario">
                                <i class="fas fa-plus-circle me-2"></i>Agregar Usuario
                            </button>
                        </div>

                        <!-- Estadísticas de usuarios -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-success shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users fa-2x text-success mb-2"></i>
                                        <h5 class="card-title">Total Usuarios</h5>
                                        <h3 class="text-success"><?php echo count($usuarios); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-check fa-2x text-primary mb-2"></i>
                                        <h5 class="card-title">Usuario Actual</h5>
                                        <h3 class="text-primary"><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning shadow-sm">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-shield fa-2x text-warning mb-2"></i>
                                        <h5 class="card-title">Administradores</h5>
                                        <h3 class="text-warning"><?php echo count($usuarios); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de usuarios -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-table me-2 text-secondary"></i>Listado de Usuarios
                                    <span class="badge bg-secondary float-end"><?php echo count($usuarios); ?> usuarios</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($usuarios)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">No se encontraron usuarios</h4>
                                        <p class="text-muted">Agrega tu primer usuario haciendo clic en el botón "Agregar Usuario"</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="tablaUsuarios">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre de Usuario</th>
                                                    <th>Contraseña</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <tr class="usuario-row animate-row">
                                                        <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                                        <td>
                                                            <strong class="text-primary"><?php echo htmlspecialchars($usuario['usuario']); ?></strong>
                                                            <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                                                <span class="badge bg-success ms-2">Tú</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($usuario['contrasena']); ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="btn-group" role="group">
                                                                <button class="btn btn-outline-primary btn-sm me-1" 
                                                                        title="Editar" 
                                                                        onclick="editarUsuario(<?php echo $usuario['id']; ?>)">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                                    <button class="btn btn-outline-danger btn-sm" 
                                                                            title="Eliminar" 
                                                                            onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['usuario']); ?>')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Usuario -->
    <div class="modal fade" id="modalAgregarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Agregar Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="agregar_usuario">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user me-1 text-primary"></i>Nombre de Usuario *
                            </label>
                            <input type="text" class="form-control form-control-lg border-primary" 
                                   name="usuario" required placeholder="Ej: administrador">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-success"></i>Contraseña *
                            </label>
                            <input type="password" class="form-control form-control-lg border-success" 
                                   name="contrasena" required placeholder="Ingrese la contraseña">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-warning"></i>Confirmar Contraseña *
                            </label>
                            <input type="password" class="form-control form-control-lg border-warning" 
                                   name="contrasena_confirm" required placeholder="Confirme la contraseña">
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Importante:</strong> Asegúrese de usar una contraseña segura y compartirla solo con personas autorizadas.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Agregar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Editar Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="editar_usuario">
                    <input type="hidden" name="id" id="editar_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user me-1 text-primary"></i>Nombre de Usuario *
                            </label>
                            <input type="text" class="form-control form-control-lg border-primary" 
                                   name="usuario" id="editar_usuario" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-success"></i>Nueva Contraseña
                            </label>
                            <input type="password" class="form-control form-control-lg border-success" 
                                   name="contrasena" id="editar_contrasena" placeholder="Deje en blanco para mantener la actual">
                            <small class="text-muted">Solo complete si desea cambiar la contraseña</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-warning"></i>Confirmar Nueva Contraseña
                            </label>
                            <input type="password" class="form-control form-control-lg border-warning" 
                                   name="contrasena_confirm" id="editar_contrasena_confirm" placeholder="Confirme la nueva contraseña">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación de Usuario -->
    <div class="modal fade" id="modalEliminarUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php">
                    <input type="hidden" name="accion" value="eliminar_usuario">
                    <input type="hidden" name="id" id="eliminar_id">
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-user-times fa-4x text-danger mb-3"></i>
                            <h4>¿Estás seguro?</h4>
                            <p class="text-muted">
                                Estás a punto de eliminar al usuario: 
                                <strong id="eliminar_nombre" class="text-danger"></strong>
                            </p>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Advertencia:</strong> Esta acción no se puede deshacer. El usuario perderá acceso al sistema permanentemente.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Sí, Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para editar usuario - ahora con AJAX
        function editarUsuario(id) {
            // Mostrar indicador de carga
            document.getElementById('editar_usuario').value = 'Cargando...';
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
            modal.show();
            
            // Hacer llamada AJAX para obtener los datos del usuario
            fetch(`?accion=obtener_usuario&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_id').value = data.data.id;
                        document.getElementById('editar_usuario').value = data.data.usuario;
                        document.getElementById('editar_contrasena').value = '';
                        document.getElementById('editar_contrasena_confirm').value = '';
                    } else {
                        alert('Error al cargar los datos del usuario');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del usuario');
                });
        }

        // Función para eliminar usuario
        function eliminarUsuario(id, nombre) {
            document.getElementById('eliminar_id').value = id;
            document.getElementById('eliminar_nombre').textContent = nombre;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEliminarUsuario'));
            modal.show();
        }

        // Función para cargar configuración actual
        function cargarConfiguracion() {
            fetch('?accion=obtener_configuracion')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const config = data.data;
                        document.querySelector('input[name="nitgranja"]').value = config.nitgranja || '';
                        document.querySelector('input[name="nombre"]').value = config.nombre || '';
                        document.querySelector('input[name="direccion"]').value = config.direccion || '';
                        document.querySelector('input[name="telefono"]').value = config.telefono || '';
                        document.querySelector('input[name="email"]').value = config.email || '';
                        document.querySelector('input[name="logo"]').value = config.logo || '';
                        
                        // Actualizar vista previa
                        location.reload();
                    } else {
                        alert('Error al cargar la configuración');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar la configuración');
                });
        }

        // Búsqueda en tiempo real
        document.getElementById('busqueda').addEventListener('input', function() {
            const busqueda = this.value;
            if (busqueda.length > 2 || busqueda.length === 0) {
                window.location.href = '?busqueda=' + encodeURIComponent(busqueda);
            }
        });

        // Animaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Animación para filas de la tabla
            const rows = document.querySelectorAll('.animate-row');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });

            // Efecto hover para tarjetas
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Efecto para botones de acción
            const actionButtons = document.querySelectorAll('.btn-group .btn');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.1)';
                });
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Validación de contraseñas en formularios
            const formAgregar = document.querySelector('form[action="procesar.php"][data-agregar-usuario]');
            if (formAgregar) {
                formAgregar.addEventListener('submit', function(e) {
                    const pass1 = document.querySelector('input[name="contrasena"]').value;
                    const pass2 = document.querySelector('input[name="contrasena_confirm"]').value;
                    if (pass1 !== pass2) {
                        e.preventDefault();
                        alert('Las contraseñas no coinciden');
                    }
                });
            }
        });
    </script>
</body>
</html>