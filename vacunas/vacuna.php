<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener todas las vacunas con datos del cerdo
function obtenerVacunas($connection, $busqueda = '') {
    if (!empty($busqueda)) {
        $sql = "SELECT v.*, c.nombre as nombrecerdo_nombre FROM vacunas v 
                LEFT JOIN cerdo c ON v.idecerdo = c.id 
                WHERE v.nombrevacuna ILIKE $1 OR c.nombre ILIKE $1 
                ORDER BY v.fechaaplicacion DESC";
        $result = pg_query_params($connection, $sql, ["%$busqueda%"]);
    } else {
        $sql = "SELECT v.*, c.nombre as nombrecerdo_nombre FROM vacunas v 
                LEFT JOIN cerdo c ON v.idecerdo = c.id 
                ORDER BY v.fechaaplicacion DESC";
        $result = pg_query($connection, $sql);
    }
    
    $vacunas = [];
    while ($row = pg_fetch_assoc($result)) {
        $vacunas[] = $row;
    }
    return $vacunas;
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

// Función para obtener una vacuna específica (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_vacuna' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM vacunas WHERE id = $1";
    $result = pg_query_params($connection, $sql, [$id]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vacuna no encontrada']);
    }
    exit();
}

// Función para eliminar vacuna
function eliminarVacuna($connection, $id) {
    $sql = "DELETE FROM vacunas WHERE id = $1";
    return pg_query_params($connection, $sql, [$id]);
}

// Procesar eliminación
if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar' && isset($_POST['id'])) {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    if (eliminarVacuna($connection, $_POST['id'])) {
        $mensaje = "Vacuna eliminada correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar la vacuna";
        $tipo_mensaje = "error";
    }
}

// Obtener vacunas y cerdos
$db = new DatabaseConnection();
$connection = $db->getConnection();
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$vacunas = obtenerVacunas($connection, $busqueda);
$cerdos = obtenerCerdos($connection);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacunas - Granja Porcina</title>
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
                        <a class="nav-link active" href="../vacunas/vacuna.php"><i class="fas fa-syringe me-1"></i>Vacunas</a>
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
                        <i class="fas fa-syringe me-2 text-warning"></i>Gestión de Vacunas
                    </h2>
                    <button class="btn btn-warning btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Vacuna
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

        <!-- Barra de búsqueda -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-box">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-warning">
                            <i class="fas fa-search text-warning"></i>
                        </span>
                        <input type="text" id="busqueda" class="form-control form-control-lg border-warning" 
                               placeholder="Buscar por nombre de vacuna o cerdo..." value="<?php echo htmlspecialchars($busqueda); ?>">
                        <button class="btn btn-outline-warning" type="button" id="btnBuscar">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-syringe fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Total Vacunas</h5>
                        <h3 class="text-warning"><?php echo count($vacunas); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-piggy-bank fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Cerdos Vacunados</h5>
                        <?php 
                        $cerdos_vacunados = count(array_unique(array_column($vacunas, 'idecerdo')));
                        ?>
                        <h3 class="text-success"><?php echo $cerdos_vacunados; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Costo Total</h5>
                        <?php 
                        $costo_total = 0;
                        if (!empty($vacunas)) {
                            $costo_total = array_sum(array_column($vacunas, 'valorvacuna'));
                        }
                        ?>
                        <h3 class="text-primary"><?php echo formatearPesos($costo_total); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Próximas 30 días</h5>
                        <?php 
                        $proximas = 0;
                        if (!empty($vacunas)) {
                            $hoy = new DateTime();
                            foreach ($vacunas as $vacuna) {
                                if (!empty($vacuna['proximaaplicacion'])) {
                                    $proxima_fecha = new DateTime($vacuna['proximaaplicacion']);
                                    $intervalo = $hoy->diff($proxima_fecha);
                                    if ($proxima_fecha >= $hoy && $intervalo->days <= 30) {
                                        $proximas++;
                                    }
                                }
                            }
                        }
                        ?>
                        <h3 class="text-info"><?php echo $proximas; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de vacunas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2 text-warning"></i>Listado de Vacunas
                            <span class="badge bg-warning float-end"><?php echo count($vacunas); ?> registros</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vacunas)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-syringe fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No se encontraron vacunas</h4>
                                <p class="text-muted">Agrega tu primera vacuna haciendo clic en el botón "Agregar Vacuna"</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaVacunas">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>ID</th>
                                            <th>Cerdo</th>
                                            <th>Fecha Aplicación</th>
                                            <th>Nombre Vacuna</th>
                                            <th>Dosis</th>
                                            <th>Valor</th>
                                            <th>Próxima Aplicación</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vacunas as $vacuna): ?>
                                            <tr class="vacuna-row animate-row">
                                                <td><?php echo htmlspecialchars($vacuna['id']); ?></td>
                                                <td>
                                                    <strong class="text-success"><?php echo htmlspecialchars($vacuna['nombrecerdo_nombre'] ?? $vacuna['nombrecerdo']); ?></strong>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($vacuna['fechaaplicacion'])); ?></td>
                                                <td><?php echo htmlspecialchars($vacuna['nombrevacuna']); ?></td>
                                                <td><?php echo htmlspecialchars($vacuna['dosis'] ?? 'N/A'); ?></td>
                                                <td><?php echo formatearPesos($vacuna['valorvacuna']); ?></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($vacuna['proximaaplicacion'])) {
                                                        echo date('d/m/Y', strtotime($vacuna['proximaaplicacion']));
                                                        // Marcar en rojo si está próxima
                                                        $proxima_fecha = new DateTime($vacuna['proximaaplicacion']);
                                                        $hoy = new DateTime();
                                                        $intervalo = $hoy->diff($proxima_fecha);
                                                        if ($proxima_fecha >= $hoy && $intervalo->days <= 30) {
                                                            echo ' <span class="badge bg-danger">Próxima</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted">N/A</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                title="Editar" 
                                                                onclick="editarVacuna(<?php echo $vacuna['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                title="Eliminar" 
                                                                onclick="eliminarVacuna(<?php echo $vacuna['id']; ?>, '<?php echo addslashes($vacuna['nombrevacuna']); ?>')">
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

    <!-- Modal Agregar Vacuna -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Nueva Vacuna
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
                                <select class="form-select form-control-lg border-success" name="idecerdo" required>
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
                                    <i class="fas fa-calendar me-1 text-info"></i>Fecha Aplicación
                                </label>
                                <input type="date" class="form-control form-control-lg border-info" 
                                       name="fechaaplicacion" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-syringe me-1 text-warning"></i>Nombre Vacuna
                                </label>
                                <input type="text" class="form-control form-control-lg border-warning" 
                                       name="nombrevacuna" required placeholder="Ej: Vitamina B12">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-prescription-bottle me-1 text-primary"></i>Dosis
                                </label>
                                <input type="text" class="form-control form-control-lg border-primary" 
                                       name="dosis" placeholder="Ej: 2ml">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-success"></i>Valor (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-success" 
                                       name="valorvacuna" required placeholder="Ej: 50000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar-plus me-1 text-danger"></i>Próxima Aplicación
                                </label>
                                <input type="date" class="form-control form-control-lg border-danger" 
                                       name="proximaaplicacion">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Guardar Vacuna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Vacuna -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Vacuna
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
                                <select class="form-select form-control-lg border-success" name="idecerdo" id="editar_idecerdo" required>
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
                                    <i class="fas fa-calendar me-1 text-info"></i>Fecha Aplicación
                                </label>
                                <input type="date" class="form-control form-control-lg border-info" 
                                       name="fechaaplicacion" id="editar_fechaaplicacion" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-syringe me-1 text-warning"></i>Nombre Vacuna
                                </label>
                                <input type="text" class="form-control form-control-lg border-warning" 
                                       name="nombrevacuna" id="editar_nombrevacuna" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-prescription-bottle me-1 text-primary"></i>Dosis
                                </label>
                                <input type="text" class="form-control form-control-lg border-primary" 
                                       name="dosis" id="editar_dosis">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-success"></i>Valor (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-success" 
                                       name="valorvacuna" id="editar_valorvacuna" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar-plus me-1 text-danger"></i>Próxima Aplicación
                                </label>
                                <input type="date" class="form-control form-control-lg border-danger" 
                                       name="proximaaplicacion" id="editar_proximaaplicacion">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Vacuna
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
                            <i class="fas fa-syringe fa-4x text-danger mb-3"></i>
                            <h4>¿Estás seguro?</h4>
                            <p class="text-muted">
                                Estás a punto de eliminar la vacuna: 
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
        // Función para editar vacuna - ahora con AJAX
        function editarVacuna(id) {
            // Mostrar indicador de carga
            document.getElementById('editar_nombrevacuna').value = 'Cargando...';
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
            
            // Hacer llamada AJAX para obtener los datos de la vacuna
            fetch(`?accion=obtener_vacuna&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_id').value = data.data.id;
                        document.getElementById('editar_idecerdo').value = data.data.idecerdo;
                        document.getElementById('editar_fechaaplicacion').value = data.data.fechaaplicacion;
                        document.getElementById('editar_nombrevacuna').value = data.data.nombrevacuna;
                        document.getElementById('editar_dosis').value = data.data.dosis || '';
                        document.getElementById('editar_valorvacuna').value = data.data.valorvacuna;
                        document.getElementById('editar_proximaaplicacion').value = data.data.proximaaplicacion || '';
                    } else {
                        alert('Error al cargar los datos de la vacuna');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos de la vacuna');
                });
        }

        // Función para eliminar vacuna
        function eliminarVacuna(id, nombre) {
            document.getElementById('eliminar_id').value = id;
            document.getElementById('eliminar_nombre').textContent = nombre;
            
            var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
            modal.show();
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

            // Establecer fecha actual por defecto en formularios
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });
        });

        // Formato de moneda en tiempo real
        document.querySelectorAll('input[name="valorvacuna"]').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value) {
                    this.value = parseInt(this.value.replace(/\D/g, '')) || 0;
                }
            });
        });
    </script>
</body>
</html>