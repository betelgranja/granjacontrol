<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener todos los controles de peso con datos del cerdo
function obtenerControlesPeso($connection, $busqueda = '', $idcerdo = '') {
    $params = [];
    $where_conditions = [];
    
    if (!empty($busqueda)) {
        $where_conditions[] = "(c.nombre ILIKE $" . (count($params) + 1) . " OR cer.nombre ILIKE $" . (count($params) + 2) . ")";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    if (!empty($idcerdo)) {
        $where_conditions[] = "c.idcerdo = $" . (count($params) + 1);
        $params[] = $idcerdo;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "SELECT c.*, cer.nombre as nombrecerdo_nombre FROM controlpeso c 
            LEFT JOIN cerdo cer ON c.idcerdo = cer.id 
            $where_clause
            ORDER BY c.fechacontrol DESC, c.id DESC";
    
    $result = pg_query_params($connection, $sql, $params);
    
    $controles = [];
    while ($row = pg_fetch_assoc($result)) {
        $controles[] = $row;
    }
    return $controles;
}

// Función para obtener controles de peso de un cerdo específico para la gráfica
function obtenerHistorialPesoCerdo($connection, $idcerdo) {
    $sql = "SELECT * FROM controlpeso 
            WHERE idcerdo = $1 
            ORDER BY fechacontrol ASC";
    $result = pg_query_params($connection, $sql, [$idcerdo]);
    
    $historial = [];
    while ($row = pg_fetch_assoc($result)) {
        $historial[] = $row;
    }
    return $historial;
}

// Función para obtener lista de cerdos para el select
function obtenerCerdos($connection) {
    $sql = "SELECT id, nombre FROM cerdo ORDER BY nombre";
    $result = pg_query($connection, $sql);
    
    $cerdos = [];
    while ($row = pg_fetch_assoc($result)) {
        $cerdos[] = $row;
    }
    return $cerdos;
}

// Función para obtener un control de peso específico (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_control' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM controlpeso WHERE id = $1";
    $result = pg_query_params($connection, $sql, [$id]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Control de peso no encontrado']);
    }
    exit();
}

// Función para eliminar control de peso
function eliminarControlPeso($connection, $id) {
    $sql = "DELETE FROM controlpeso WHERE id = $1";
    return pg_query_params($connection, $sql, [$id]);
}

// Procesar eliminación
if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar' && isset($_POST['id'])) {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    if (eliminarControlPeso($connection, $_POST['id'])) {
        $mensaje = "Control de peso eliminado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar el control de peso";
        $tipo_mensaje = "error";
    }
}

// Obtener datos
$db = new DatabaseConnection();
$connection = $db->getConnection();
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$idcerdo_filtro = isset($_GET['idcerdo']) ? $_GET['idcerdo'] : '';
$controles = obtenerControlesPeso($connection, $busqueda, $idcerdo_filtro);
$cerdos = obtenerCerdos($connection);

// Obtener historial para gráfica si se seleccionó un cerdo
$historial_grafica = [];
$nombre_cerdo_grafica = '';
if (!empty($idcerdo_filtro)) {
    $historial_grafica = obtenerHistorialPesoCerdo($connection, $idcerdo_filtro);
    // Obtener nombre del cerdo
    $sql_nombre = "SELECT nombre FROM cerdo WHERE id = $1";
    $result_nombre = pg_query_params($connection, $sql_nombre, [$idcerdo_filtro]);
    $cerdo_nombre = pg_fetch_assoc($result_nombre);
    $nombre_cerdo_grafica = $cerdo_nombre['nombre'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Peso - Granja Porcina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a class="nav-link active" href="../controlpeso/controlpeso.php"><i class="fas fa-weight me-1"></i>Control de Peso</a>
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
                        <a class="nav-link" href="../configuracion/configuracion.php"><i class="fas fa-cog me-1"></i>Configuración</a>
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
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <i class="fas fa-weight me-2 text-primary"></i>Control de Peso
                    </h2>
                    <button class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Control
                    </button>
                </div>
                <hr class="my-3">
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-<?php echo $_GET['tipo'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-<?php echo $_GET['tipo'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtros y búsqueda -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="search-box">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-primary">
                                    <i class="fas fa-search text-primary"></i>
                                </span>
                                <input type="text" id="busqueda" class="form-control form-control-lg border-primary" 
                                       placeholder="Buscar por nombre de cerdo..." value="<?php echo htmlspecialchars($busqueda); ?>">
                                <button class="btn btn-outline-primary" type="button" id="btnBuscar">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-success">
                                <i class="fas fa-filter text-success"></i>
                            </span>
                            <select class="form-select form-control-lg border-success" id="filtroCerdo">
                                <option value="">Todos los cerdos</option>
                                <?php foreach ($cerdos as $cerdo): ?>
                                    <option value="<?php echo $cerdo['id']; ?>" <?php echo ($idcerdo_filtro == $cerdo['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cerdo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica de historial (solo si hay datos) -->
        <?php if (!empty($historial_grafica) && !empty($idcerdo_filtro)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2 text-primary"></i>Historial de Peso - <?php echo htmlspecialchars($nombre_cerdo_grafica); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="graficaPeso" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-weight fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Total Controles</h5>
                        <h3 class="text-primary"><?php echo count($controles); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-piggy-bank fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Cerdos Monitoreados</h5>
                        <?php 
                        $cerdos_monitoreados = count(array_unique(array_column($controles, 'idcerdo')));
                        ?>
                        <h3 class="text-success"><?php echo $cerdos_monitoreados; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-up fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Mayor Peso</h5>
                        <?php 
                        $mayor_peso = 0;
                        if (!empty($controles)) {
                            $mayor_peso = max(array_column($controles, 'pesoactual'));
                        }
                        ?>
                        <h3 class="text-warning"><?php echo number_format($mayor_peso, 2); ?> kg</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Esta Semana</h5>
                        <?php 
                        $esta_semana = 0;
                        if (!empty($controles)) {
                            $hoy = new DateTime();
                            foreach ($controles as $control) {
                                $fecha_control = new DateTime($control['fechacontrol']);
                                $intervalo = $hoy->diff($fecha_control);
                                if ($intervalo->days <= 7) {
                                    $esta_semana++;
                                }
                            }
                        }
                        ?>
                        <h3 class="text-info"><?php echo $esta_semana; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de controles de peso -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2 text-primary"></i>Listado de Controles de Peso
                            <span class="badge bg-primary float-end"><?php echo count($controles); ?> registros</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($controles)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-weight fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No se encontraron controles de peso</h4>
                                <p class="text-muted">Agrega tu primer control haciendo clic en el botón "Agregar Control"</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaControles">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>ID</th>
                                            <th>Cerdo</th>
                                            <th>Fecha Control</th>
                                            <th>Peso Anterior (kg)</th>
                                            <th>Peso Actual (kg)</th>
                                            <th>Diferencia (kg)</th>
                                            <th class="text-center">Variación</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($controles as $control): ?>
                                            <tr class="control-row animate-row">
                                                <td><?php echo htmlspecialchars($control['id']); ?></td>
                                                <td>
                                                    <strong class="text-success"><?php echo htmlspecialchars($control['nombrecerdo_nombre'] ?? $control['nombre']); ?></strong>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($control['fechacontrol'])); ?></td>
                                                <td><?php echo number_format($control['pesoanterior'] ?? 0, 2); ?> kg</td>
                                                <td><?php echo number_format($control['pesoactual'], 2); ?> kg</td>
                                                <td>
                                                    <?php 
                                                    $diferencia = $control['diferencia'] ?? 0;
                                                    echo number_format(abs($diferencia), 2);
                                                    echo $diferencia >= 0 ? ' kg ↑' : ' kg ↓';
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($diferencia > 0): ?>
                                                        <span class="badge bg-success">Subida</span>
                                                    <?php elseif ($diferencia < 0): ?>
                                                        <span class="badge bg-danger">Bajada</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Sin cambio</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                title="Editar" 
                                                                onclick="editarControl(<?php echo $control['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                title="Eliminar" 
                                                                onclick="eliminarControl(<?php echo $control['id']; ?>, '<?php echo addslashes($control['nombre']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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

    <!-- Modal Agregar Control -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Control de Peso
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-pig me-1 text-success"></i>Seleccionar Cerdo
                                </label>
                                <select class="form-select form-control-lg border-success" name="idcerdo" id="agregar_idcerdo" required onchange="actualizarPesoAnterior(this.value)">
                                    <option value="">Seleccione un cerdo</option>
                                    <?php foreach ($cerdos as $cerdo): ?>
                                        <option value="<?php echo $cerdo['id']; ?>">
                                            <?php echo htmlspecialchars($cerdo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-info"></i>Fecha Control
                                </label>
                                <input type="date" class="form-control form-control-lg border-info" 
                                       name="fechacontrol" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-weight me-1 text-primary"></i>Peso Anterior (kg)
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-lg border-primary" 
                                       name="pesoanterior" id="agregar_pesoanterior" readonly>
                                <small class="text-muted">Se calcula automáticamente</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-weight me-1 text-success"></i>Peso Actual (kg)
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-lg border-success" 
                                       name="pesoactual" id="agregar_pesoactual" required placeholder="Ej: 85.50">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calculator me-1 text-warning"></i>Diferencia (kg)
                            </label>
                            <input type="number" step="0.01" class="form-control form-control-lg border-warning" 
                                   name="diferencia" id="agregar_diferencia" readonly>
                            <small class="text-muted">Calculada automáticamente</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Guardar Control
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Control -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Control de Peso
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" id="editar_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-pig me-1 text-success"></i>Seleccionar Cerdo
                                </label>
                                <select class="form-select form-control-lg border-success" name="idcerdo" id="editar_idcerdo" required>
                                    <option value="">Seleccione un cerdo</option>
                                    <?php foreach ($cerdos as $cerdo): ?>
                                        <option value="<?php echo $cerdo['id']; ?>">
                                            <?php echo htmlspecialchars($cerdo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-info"></i>Fecha Control
                                </label>
                                <input type="date" class="form-control form-control-lg border-info" 
                                       name="fechacontrol" id="editar_fechacontrol" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-weight me-1 text-primary"></i>Peso Anterior (kg)
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-lg border-primary" 
                                       name="pesoanterior" id="editar_pesoanterior" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-weight me-1 text-success"></i>Peso Actual (kg)
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-lg border-success" 
                                       name="pesoactual" id="editar_pesoactual" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calculator me-1 text-warning"></i>Diferencia (kg)
                            </label>
                            <input type="number" step="0.01" class="form-control form-control-lg border-warning" 
                                   name="diferencia" id="editar_diferencia" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Control
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="eliminar_id">
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-weight fa-4x text-danger mb-3"></i>
                            <h4>¿Estás seguro?</h4>
                            <p class="text-muted">
                                Estás a punto de eliminar el control de peso del cerdo: 
                                <strong id="eliminar_nombre" class="text-danger"></strong>
                            </p>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta acción no se puede deshacer.
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
        // Función para editar control - ahora con AJAX
        function editarControl(id) {
            // Mostrar indicador de carga
            document.getElementById('editar_pesoactual').value = '';
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
            
            // Hacer llamada AJAX para obtener los datos del control
            fetch(`?accion=obtener_control&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_id').value = data.data.id;
                        document.getElementById('editar_idcerdo').value = data.data.idcerdo;
                        document.getElementById('editar_fechacontrol').value = data.data.fechacontrol;
                        document.getElementById('editar_pesoanterior').value = data.data.pesoanterior || '';
                        document.getElementById('editar_pesoactual').value = data.data.pesoactual;
                        document.getElementById('editar_diferencia').value = data.data.diferencia || '';
                        
                        // Calcular diferencia si cambia el peso actual
                        document.getElementById('editar_pesoactual').addEventListener('input', calcularDiferenciaEditar);
                    } else {
                        alert('Error al cargar los datos del control');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del control');
                });
        }

        // Función para eliminar control
        function eliminarControl(id, nombre) {
            document.getElementById('eliminar_id').value = id;
            document.getElementById('eliminar_nombre').textContent = nombre;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
            modal.show();
        }

        // Función para actualizar peso anterior automáticamente
        function actualizarPesoAnterior(idcerdo) {
            if (idcerdo) {
                fetch(`procesar.php?accion=obtener_ultimo_peso&idcerdo=${idcerdo}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('agregar_pesoanterior').value = data.ultimo_peso || '';
                            // Calcular diferencia
                            calcularDiferenciaAgregar();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            } else {
                document.getElementById('agregar_pesoanterior').value = '';
                document.getElementById('agregar_diferencia').value = '';
            }
        }

        // Calcular diferencia en formulario de agregar
        function calcularDiferenciaAgregar() {
            const pesoAnterior = parseFloat(document.getElementById('agregar_pesoanterior').value) || 0;
            const pesoActual = parseFloat(document.getElementById('agregar_pesoactual').value) || 0;
            const diferencia = pesoActual - pesoAnterior;
            document.getElementById('agregar_diferencia').value = diferencia.toFixed(2);
        }

        // Calcular diferencia en formulario de editar
        function calcularDiferenciaEditar() {
            const pesoAnterior = parseFloat(document.getElementById('editar_pesoanterior').value) || 0;
            const pesoActual = parseFloat(document.getElementById('editar_pesoactual').value) || 0;
            const diferencia = pesoActual - pesoAnterior;
            document.getElementById('editar_diferencia').value = diferencia.toFixed(2);
        }

        // Event listeners para cálculo automático
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

            // Establecer fecha actual por defecto
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });

            // Event listeners para cálculo automático de diferencias
            document.getElementById('agregar_pesoactual').addEventListener('input', calcularDiferenciaAgregar);
            
            // Filtro por cerdo
            document.getElementById('filtroCerdo').addEventListener('change', function() {
                const idcerdo = this.value;
                const busqueda = document.getElementById('busqueda').value;
                let url = '?';
                if (idcerdo) url += 'idcerdo=' + idcerdo;
                if (busqueda) url += (url.includes('?') ? '&' : '?') + 'busqueda=' + encodeURIComponent(busqueda);
                if (url === '?') url = '';
                window.location.href = url;
            });

            // Búsqueda en tiempo real
            document.getElementById('busqueda').addEventListener('input', function() {
                const busqueda = this.value;
                const idcerdo = document.getElementById('filtroCerdo').value;
                if (busqueda.length > 2 || busqueda.length === 0) {
                    let url = '?';
                    if (busqueda) url += 'busqueda=' + encodeURIComponent(busqueda);
                    if (idcerdo) url += (url.includes('?') ? '&' : '?') + 'idcerdo=' + idcerdo;
                    if (url === '?') url = '';
                    window.location.href = url;
                }
            });
        });

        // Gráfica de historial de peso
        <?php if (!empty($historial_grafica) && !empty($idcerdo_filtro)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('graficaPeso').getContext('2d');
            const fechas = <?php echo json_encode(array_column($historial_grafica, 'fechacontrol')); ?>;
            const pesos = <?php echo json_encode(array_column($historial_grafica, 'pesoactual')); ?>;
            const fechasFormateadas = fechas.map(fecha => {
                const date = new Date(fecha);
                return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
            });

            const graficaPeso = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: fechasFormateadas,
                    datasets: [{
                        label: 'Peso (kg)',
                        data: pesos,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return `Peso: ${context.parsed.y} kg`;
                                },
                                title: function(context) {
                                    return `Fecha: ${context[0].label}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + ' kg';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>

</html>