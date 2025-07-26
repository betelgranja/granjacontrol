<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener todos los gastos
function obtenerGastos($connection, $busqueda = '', $tipo_filtro = '') {
    $params = [];
    $where_conditions = [];
    
    if (!empty($busqueda)) {
        $where_conditions[] = "nombregasto ILIKE $" . (count($params) + 1);
        $params[] = "%$busqueda%";
    }
    
    if (!empty($tipo_filtro)) {
        $where_conditions[] = "tipogasto = $" . (count($params) + 1);
        $params[] = $tipo_filtro;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "SELECT * FROM gastos $where_clause ORDER BY fechagasto DESC";
    $result = pg_query_params($connection, $sql, $params);
    
    $gastos = [];
    while ($row = pg_fetch_assoc($result)) {
        $gastos[] = $row;
    }
    return $gastos;
}

// Función para obtener tipos de gastos únicos
function obtenerTiposGastos($connection) {
    $sql = "SELECT DISTINCT tipogasto FROM gastos WHERE tipogasto IS NOT NULL ORDER BY tipogasto";
    $result = pg_query($connection, $sql);
    
    $tipos = [];
    while ($row = pg_fetch_assoc($result)) {
        $tipos[] = $row['tipogasto'];
    }
    return $tipos;
}

// Función para obtener un gasto específico (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_gasto' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM gastos WHERE id = $1";
    $result = pg_query_params($connection, $sql, [$id]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gasto no encontrado']);
    }
    exit();
}

// Función para eliminar gasto
function eliminarGasto($connection, $id) {
    $sql = "DELETE FROM gastos WHERE id = $1";
    return pg_query_params($connection, $sql, [$id]);
}

// Procesar eliminación
if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar' && isset($_POST['id'])) {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    if (eliminarGasto($connection, $_POST['id'])) {
        $mensaje = "Gasto eliminado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar el gasto";
        $tipo_mensaje = "error";
    }
}

// Obtener datos
$db = new DatabaseConnection();
$connection = $db->getConnection();
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$gastos = obtenerGastos($connection, $busqueda, $tipo_filtro);
$tipos_gastos = obtenerTiposGastos($connection);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos - Granja Porcina</title>
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
                        <a class="nav-link" href="../controlpeso/controlpeso.php"><i class="fas fa-weight me-1"></i>Control de Peso</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../vacunas/vacuna.php"><i class="fas fa-syringe me-1"></i>Vacunas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../alimentos/alimentos.php"><i class="fas fa-utensils me-1"></i>Alimentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../gastos/gastos.php"><i class="fas fa-money-bill-wave me-1"></i>Gastos</a>
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
                        <i class="fas fa-money-bill-wave me-2 text-danger"></i>Gestión de Gastos
                    </h2>
                    <button class="btn btn-danger btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Gasto
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
                                <span class="input-group-text bg-white border-danger">
                                    <i class="fas fa-search text-danger"></i>
                                </span>
                                <input type="text" id="busqueda" class="form-control form-control-lg border-danger" 
                                       placeholder="Buscar por nombre de gasto..." value="<?php echo htmlspecialchars($busqueda); ?>">
                                <button class="btn btn-outline-danger" type="button" id="btnBuscar">
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
                            <select class="form-select form-control-lg border-success" id="filtroTipo">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($tipos_gastos as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo ($tipo_filtro == $tipo) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="otros" <?php echo ($tipo_filtro == 'otros') ? 'selected' : ''; ?>>Otros</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica de distribución de gastos -->
        <?php if (!empty($gastos)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2 text-danger"></i>Distribución de Gastos por Tipo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <canvas id="graficaGastos" height="300"></canvas>
                                </div>
                                <div class="col-md-4">
                                    <div id="leyendaGastos"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-danger shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-money-bill-wave fa-2x text-danger mb-2"></i>
                        <h5 class="card-title">Total Gastos</h5>
                        <h3 class="text-danger"><?php echo count($gastos); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Gasto Total</h5>
                        <?php 
                        $gasto_total = 0;
                        if (!empty($gastos)) {
                            $gasto_total = array_sum(array_column($gastos, 'valorgasto'));
                        }
                        ?>
                        <h3 class="text-success"><?php echo formatearPesos($gasto_total); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-tags fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Tipos de Gastos</h5>
                        <h3 class="text-warning"><?php echo count($tipos_gastos); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Este Mes</h5>
                        <?php 
                        $gasto_este_mes = 0;
                        $cantidad_este_mes = 0;
                        if (!empty($gastos)) {
                            $hoy = new DateTime();
                            foreach ($gastos as $gasto) {
                                $fecha_gasto = new DateTime($gasto['fechagasto']);
                                if ($fecha_gasto->format('Y-m') === $hoy->format('Y-m')) {
                                    $gasto_este_mes += $gasto['valorgasto'];
                                    $cantidad_este_mes++;
                                }
                            }
                        }
                        ?>
                        <h3 class="text-primary"><?php echo $cantidad_este_mes; ?> (<?php echo formatearPesos($gasto_este_mes); ?>)</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de gastos -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2 text-danger"></i>Listado de Gastos
                            <span class="badge bg-danger float-end"><?php echo count($gastos); ?> registros</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($gastos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-money-bill-wave fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No se encontraron gastos</h4>
                                <p class="text-muted">Agrega tu primer gasto haciendo clic en el botón "Agregar Gasto"</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaGastos">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Gasto</th>
                                            <th>Tipo Gasto</th>
                                            <th>Fecha</th>
                                            <th>Valor</th>
                                            <th class="text-center">Días Transcurridos</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gastos as $gasto): ?>
                                            <tr class="gasto-row animate-row">
                                                <td><?php echo htmlspecialchars($gasto['id']); ?></td>
                                                <td>
                                                    <strong class="text-danger"><?php echo htmlspecialchars($gasto['nombregasto']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        $tipo_clase = 'secondary';
                                                        switch (strtolower($gasto['tipogasto'])) {
                                                            case 'alimentos': $tipo_clase = 'info'; break;
                                                            case 'medicina': $tipo_clase = 'warning'; break;
                                                            case 'vacunas': $tipo_clase = 'primary'; break;
                                                            case 'mantenimiento': $tipo_clase = 'success'; break;
                                                            case 'servicios': $tipo_clase = 'danger'; break;
                                                            default: $tipo_clase = 'secondary'; break;
                                                        }
                                                        echo $tipo_clase;
                                                    ?>">
                                                        <?php echo htmlspecialchars($gasto['tipogasto']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($gasto['fechagasto'])); ?></td>
                                                <td><?php echo formatearPesos($gasto['valorgasto']); ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    $fecha_gasto = new DateTime($gasto['fechagasto']);
                                                    $hoy = new DateTime();
                                                    $diferencia = $fecha_gasto->diff($hoy);
                                                    echo $diferencia->days;
                                                    ?>
                                                    <span class="badge bg-<?php echo $diferencia->days < 30 ? 'success' : ($diferencia->days < 90 ? 'warning' : 'secondary'); ?>">
                                                        días
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                title="Editar" 
                                                                onclick="editarGasto(<?php echo $gasto['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                title="Eliminar" 
                                                                onclick="eliminarGasto(<?php echo $gasto['id']; ?>, '<?php echo addslashes($gasto['nombregasto']); ?>')">
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

    <!-- Modal Agregar Gasto -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Gasto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-tag me-1 text-danger"></i>Nombre Gasto
                                </label>
                                <input type="text" class="form-control form-control-lg border-danger" 
                                       name="nombregasto" required placeholder="Ej: Pago de luz, Reparación de corral">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-tags me-1 text-warning"></i>Tipo Gasto
                                </label>
                                <select class="form-select form-control-lg border-warning" name="tipogasto" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="Alimentos">Alimentos</option>
                                    <option value="Medicina">Medicina</option>
                                    <option value="Vacunas">Vacunas</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Servicios">Servicios</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-success"></i>Fecha Gasto
                                </label>
                                <input type="date" class="form-control form-control-lg border-success" 
                                       name="fechagasto" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-primary"></i>Valor (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-primary" 
                                       name="valorgasto" required placeholder="Ej: 250000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save me-1"></i>Guardar Gasto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Gasto -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Gasto
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
                                    <i class="fas fa-tag me-1 text-danger"></i>Nombre Gasto
                                </label>
                                <input type="text" class="form-control form-control-lg border-danger" 
                                       name="nombregasto" id="editar_nombregasto" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-tags me-1 text-warning"></i>Tipo Gasto
                                </label>
                                <select class="form-select form-control-lg border-warning" name="tipogasto" id="editar_tipogasto" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="Alimentos">Alimentos</option>
                                    <option value="Medicina">Medicina</option>
                                    <option value="Vacunas">Vacunas</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Servicios">Servicios</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-success"></i>Fecha Gasto
                                </label>
                                <input type="date" class="form-control form-control-lg border-success" 
                                       name="fechagasto" id="editar_fechagasto" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-primary"></i>Valor (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-primary" 
                                       name="valorgasto" id="editar_valorgasto" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Gasto
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
                            <i class="fas fa-money-bill-wave fa-4x text-danger mb-3"></i>
                            <h4>¿Estás seguro?</h4>
                            <p class="text-muted">
                                Estás a punto de eliminar el gasto: 
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
        // Función para editar gasto - ahora con AJAX
        function editarGasto(id) {
            // Mostrar indicador de carga
            document.getElementById('editar_nombregasto').value = 'Cargando...';
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
            
            // Hacer llamada AJAX para obtener los datos del gasto
            fetch(`?accion=obtener_gasto&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_id').value = data.data.id;
                        document.getElementById('editar_nombregasto').value = data.data.nombregasto;
                        document.getElementById('editar_tipogasto').value = data.data.tipogasto;
                        document.getElementById('editar_fechagasto').value = data.data.fechagasto;
                        document.getElementById('editar_valorgasto').value = data.data.valorgasto;
                    } else {
                        alert('Error al cargar los datos del gasto');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del gasto');
                });
        }

        // Función para eliminar gasto
        function eliminarGasto(id, nombre) {
            document.getElementById('eliminar_id').value = id;
            document.getElementById('eliminar_nombre').textContent = nombre;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
            modal.show();
        }

        // Filtros
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

            // Formato de moneda en tiempo real
            document.querySelectorAll('input[name="valorgasto"]').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value) {
                        this.value = parseInt(this.value.replace(/\D/g, '')) || 0;
                    }
                });
            });

            // Filtro por tipo
            document.getElementById('filtroTipo').addEventListener('change', function() {
                const tipo = this.value;
                const busqueda = document.getElementById('busqueda').value;
                let url = '?';
                if (tipo) url += 'tipo=' + tipo;
                if (busqueda) url += (url.includes('?') ? '&' : '?') + 'busqueda=' + encodeURIComponent(busqueda);
                if (url === '?') url = '';
                window.location.href = url;
            });

            // Búsqueda en tiempo real
            document.getElementById('busqueda').addEventListener('input', function() {
                const busqueda = this.value;
                const tipo = document.getElementById('filtroTipo').value;
                if (busqueda.length > 2 || busqueda.length === 0) {
                    let url = '?';
                    if (busqueda) url += 'busqueda=' + encodeURIComponent(busqueda);
                    if (tipo) url += (url.includes('?') ? '&' : '?') + 'tipo=' + tipo;
                    if (url === '?') url = '';
                    window.location.href = url;
                }
            });
        });

        // Gráfica de distribución de gastos
        <?php if (!empty($gastos)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Preparar datos para la gráfica
            const gastosData = <?php echo json_encode($gastos); ?>;
            
            // Agrupar por tipo de gasto
            const tipos = {};
            gastosData.forEach(gasto => {
                const tipo = gasto.tipogasto || 'Sin tipo';
                if (!tipos[tipo]) {
                    tipos[tipo] = 0;
                }
                tipos[tipo] += parseFloat(gasto.valorgasto);
            });
            
            // Preparar datos para Chart.js
            const labels = Object.keys(tipos);
            const valores = Object.values(tipos);
            
            // Colores para diferentes tipos
            const colores = [
                '#dc3545', // Rojo
                '#28a745', // Verde
                '#ffc107', // Amarillo
                '#007bff', // Azul
                '#6f42c1', // Púrpura
                '#20c997', // Turquesa
                '#fd7e14', // Naranja
                '#6c757d'  // Gris
            ];
            
            // Asignar colores a cada tipo
            const backgroundColors = labels.map((_, index) => colores[index % colores.length]);
            
            const ctx = document.getElementById('graficaGastos').getContext('2d');
            const graficaGastos = new Chart(ctx, {
                type: 'pie',
                 {
                    labels: labels,
                    datasets: [{
                         valores,
                        backgroundColor: backgroundColors,
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 15
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
                                    const valor = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const porcentaje = ((valor / total) * 100).toFixed(1);
                                    return `${context.label}: ${valor.toLocaleString('es-CO', {
                                        style: 'currency',
                                        currency: 'COP',
                                        minimumFractionDigits: 0
                                    })} (${porcentaje}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeOutQuart'
                    }
                }
            });
            
            // Crear leyenda personalizada
            const leyendaDiv = document.getElementById('leyendaGastos');
            let leyendaHTML = '<h6 class="mb-3">Desglose por Tipo:</h6><ul class="list-unstyled">';
            
            labels.forEach((label, index) => {
                const valor = valores[index];
                const total = valores.reduce((a, b) => a + b, 0);
                const porcentaje = ((valor / total) * 100).toFixed(1);
                
                leyendaHTML += `
                    <li class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge me-2" style="background-color: ${backgroundColors[index]}; width: 20px; height: 20px; border-radius: 50%;">&nbsp;</span>
                                <span class="fw-semibold">${label}</span>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">${valor.toLocaleString('es-CO', {
                                    style: 'currency',
                                    currency: 'COP',
                                    minimumFractionDigits: 0
                                })}</div>
                                <small class="text-muted">${porcentaje}%</small>
                            </div>
                        </div>
                    </li>
                `;
            });
            
            leyendaHTML += '</ul>';
            leyendaDiv.innerHTML = leyendaHTML;
        });
        <?php endif; ?>
    </script>
</body>
</html>