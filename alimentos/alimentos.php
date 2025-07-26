<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener todos los alimentos
function obtenerAlimentos($connection, $busqueda = '') {
    if (!empty($busqueda)) {
        $sql = "SELECT * FROM alimentos 
                WHERE nombrealimento ILIKE $1 OR facturacompra ILIKE $1 
                ORDER BY fechacompra DESC";
        $result = pg_query_params($connection, $sql, ["%$busqueda%"]);
    } else {
        $sql = "SELECT * FROM alimentos ORDER BY fechacompra DESC";
        $result = pg_query($connection, $sql);
    }
    
    $alimentos = [];
    while ($row = pg_fetch_assoc($result)) {
        $alimentos[] = $row;
    }
    return $alimentos;
}

// Función para obtener un alimento específico (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_alimento' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM alimentos WHERE id = $1";
    $result = pg_query_params($connection, $sql, [$id]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Alimento no encontrado']);
    }
    exit();
}

// Función para eliminar alimento
function eliminarAlimento($connection, $id) {
    $sql = "DELETE FROM alimentos WHERE id = $1";
    return pg_query_params($connection, $sql, [$id]);
}

// Procesar eliminación
if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar' && isset($_POST['id'])) {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    if (eliminarAlimento($connection, $_POST['id'])) {
        $mensaje = "Alimento eliminado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar el alimento";
        $tipo_mensaje = "error";
    }
}

// Obtener alimentos
$db = new DatabaseConnection();
$connection = $db->getConnection();
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$alimentos = obtenerAlimentos($connection, $busqueda);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alimentos - Granja Porcina</title>
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
                        <a class="nav-link" href="../cerdos/index3.php"><i class="fas fa-piggy-bankme-1"></i>Cerdos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../controlpeso/controlpeso.php"><i class="fas fa-weight me-1"></i>Control de Peso</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../vacunas/vacuna.php"><i class="fas fa-syringe me-1"></i>Vacunas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../alimentos/alimentos.php"><i class="fas fa-utensils me-1"></i>Alimentos</a>
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
                        <i class="fas fa-utensils me-2 text-info"></i>Gestión de Alimentos
                    </h2>
                    <button class="btn btn-info btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Alimento
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
                        <span class="input-group-text bg-white border-info">
                            <i class="fas fa-search text-info"></i>
                        </span>
                        <input type="text" id="busqueda" class="form-control form-control-lg border-info" 
                               placeholder="Buscar por nombre o factura..." value="<?php echo htmlspecialchars($busqueda); ?>">
                        <button class="btn btn-outline-info" type="button" id="btnBuscar">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica de gastos en alimentos -->
        <?php if (!empty($alimentos)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2 text-info"></i>Gastos en Alimentos - Últimos 6 Meses
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="graficaAlimentos" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Total Alimentos</h5>
                        <h3 class="text-info"><?php echo count($alimentos); ?></h3>
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
                        if (!empty($alimentos)) {
                            $gasto_total = array_sum(array_column($alimentos, 'preciocompra'));
                        }
                        ?>
                        <h3 class="text-success"><?php echo formatearPesos($gasto_total); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-receipt fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Facturas Únicas</h5>
                        <?php 
                        $facturas_unicas = 0;
                        if (!empty($alimentos)) {
                            $facturas_unicas = count(array_unique(array_column($alimentos, 'facturacompra')));
                        }
                        ?>
                        <h3 class="text-warning"><?php echo $facturas_unicas; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Este Mes</h5>
                        <?php 
                        $este_mes = 0;
                        $gasto_este_mes = 0;
                        if (!empty($alimentos)) {
                            $hoy = new DateTime();
                            foreach ($alimentos as $alimento) {
                                $fecha_compra = new DateTime($alimento['fechacompra']);
                                if ($fecha_compra->format('Y-m') === $hoy->format('Y-m')) {
                                    $este_mes++;
                                    $gasto_este_mes += $alimento['preciocompra'];
                                }
                            }
                        }
                        ?>
                        <h3 class="text-primary"><?php echo $este_mes; ?> (<?php echo formatearPesos($gasto_este_mes); ?>)</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de alimentos -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2 text-info"></i>Listado de Alimentos
                            <span class="badge bg-info float-end"><?php echo count($alimentos); ?> registros</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($alimentos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No se encontraron alimentos</h4>
                                <p class="text-muted">Agrega tu primer alimento haciendo clic en el botón "Agregar Alimento"</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaAlimentos">
                                    <thead class="table-info">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre Alimento</th>
                                            <th>Fecha Compra</th>
                                            <th>Factura</th>
                                            <th>Precio Compra</th>
                                            <th class="text-center">Días Transcurridos</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alimentos as $alimento): ?>
                                            <tr class="alimento-row animate-row">
                                                <td><?php echo htmlspecialchars($alimento['id']); ?></td>
                                                <td>
                                                    <strong class="text-info"><?php echo htmlspecialchars($alimento['nombrealimento']); ?></strong>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($alimento['fechacompra'])); ?></td>
                                                <td><?php echo htmlspecialchars($alimento['facturacompra'] ?? 'N/A'); ?></td>
                                                <td><?php echo formatearPesos($alimento['preciocompra']); ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    $fecha_compra = new DateTime($alimento['fechacompra']);
                                                    $hoy = new DateTime();
                                                    $diferencia = $fecha_compra->diff($hoy);
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
                                                                onclick="editarAlimento(<?php echo $alimento['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                title="Eliminar" 
                                                                onclick="eliminarAlimento(<?php echo $alimento['id']; ?>, '<?php echo addslashes($alimento['nombrealimento']); ?>')">
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

    <!-- Modal Agregar Alimento -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Alimento
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-utensils me-1 text-info"></i>Nombre Alimento
                                </label>
                                <input type="text" class="form-control form-control-lg border-info" 
                                       name="nombrealimento" required placeholder="Ej: Alimento Balanceado 20kg">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-success"></i>Fecha Compra
                                </label>
                                <input type="date" class="form-control form-control-lg border-success" 
                                       name="fechacompra" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-receipt me-1 text-warning"></i>Factura Compra
                                </label>
                                <input type="text" class="form-control form-control-lg border-warning" 
                                       name="facturacompra" placeholder="Ej: FAC-001-2024">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-primary"></i>Precio Compra (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-primary" 
                                       name="preciocompra" required placeholder="Ej: 150000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save me-1"></i>Guardar Alimento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Alimento -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Alimento
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
                                    <i class="fas fa-utensils me-1 text-info"></i>Nombre Alimento
                                </label>
                                <input type="text" class="form-control form-control-lg border-info" 
                                       name="nombrealimento" id="editar_nombrealimento" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-success"></i>Fecha Compra
                                </label>
                                <input type="date" class="form-control form-control-lg border-success" 
                                       name="fechacompra" id="editar_fechacompra" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-receipt me-1 text-warning"></i>Factura Compra
                                </label>
                                <input type="text" class="form-control form-control-lg border-warning" 
                                       name="facturacompra" id="editar_facturacompra">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-primary"></i>Precio Compra (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-primary" 
                                       name="preciocompra" id="editar_preciocompra" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Alimento
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
                            <i class="fas fa-utensils fa-4x text-danger mb-3"></i>
                            <h4>¿Estás seguro?</h4>
                            <p class="text-muted">
                                Estás a punto de eliminar el alimento: 
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
        // Función para editar alimento - ahora con AJAX
        function editarAlimento(id) {
            // Mostrar indicador de carga
            document.getElementById('editar_nombrealimento').value = 'Cargando...';
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
            
            // Hacer llamada AJAX para obtener los datos del alimento
            fetch(`?accion=obtener_alimento&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_id').value = data.data.id;
                        document.getElementById('editar_nombrealimento').value = data.data.nombrealimento;
                        document.getElementById('editar_fechacompra').value = data.data.fechacompra;
                        document.getElementById('editar_facturacompra').value = data.data.facturacompra || '';
                        document.getElementById('editar_preciocompra').value = data.data.preciocompra;
                    } else {
                        alert('Error al cargar los datos del alimento');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del alimento');
                });
        }

        // Función para eliminar alimento
        function eliminarAlimento(id, nombre) {
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

            // Establecer fecha actual por defecto
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (!input.value) {
                    input.value = today;
                }
            });

            // Formato de moneda en tiempo real
            document.querySelectorAll('input[name="preciocompra"]').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value) {
                        this.value = parseInt(this.value.replace(/\D/g, '')) || 0;
                    }
                });
            });
        });

        // Gráfica de gastos en alimentos
        <?php if (!empty($alimentos)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Preparar datos para la gráfica (últimos 6 meses)
            const alimentosData = <?php echo json_encode($alimentos); ?>;
            
            // Agrupar por mes
            const meses = {};
            const hoy = new Date();
            const seisMesesAtras = new Date();
            seisMesesAtras.setMonth(seisMesesAtras.getMonth() - 5);
            
            // Inicializar meses
            for (let i = 5; i >= 0; i--) {
                const fecha = new Date();
                fecha.setMonth(hoy.getMonth() - i);
                const mesKey = fecha.getFullYear() + '-' + String(fecha.getMonth() + 1).padStart(2, '0');
                meses[mesKey] = 0;
            }
            
            // Sumar gastos por mes
            alimentosData.forEach(alimento => {
                const fecha = new Date(alimento.fechacompra);
                const mesKey = fecha.getFullYear() + '-' + String(fecha.getMonth() + 1).padStart(2, '0');
                if (meses.hasOwnProperty(mesKey)) {
                    meses[mesKey] += parseFloat(alimento.preciocompra);
                }
            });
            
            // Preparar datos para Chart.js
            const labels = Object.keys(meses).map(key => {
                const [year, month] = key.split('-');
                const date = new Date(year, month - 1);
                return date.toLocaleDateString('es-ES', { month: 'short', year: '2-digit' });
            });
            
            const valores = Object.values(meses);
            
            const ctx = document.getElementById('graficaAlimentos').getContext('2d');
            const graficaAlimentos = new Chart(ctx, {
                type: 'bar',
                 {
                    labels: labels,
                    datasets: [{
                        label: 'Gasto en Alimentos (COP)',
                         valores,
                        backgroundColor: 'rgba(13, 202, 240, 0.7)',
                        borderColor: 'rgba(13, 202, 240, 1)',
                        borderWidth: 2,
                        borderRadius: 5,
                        borderSkipped: false,
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
                                    return 'Gasto: ' + context.parsed.y.toLocaleString('es-CO', {
                                        style: 'currency',
                                        currency: 'COP',
                                        minimumFractionDigits: 0
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-CO');
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