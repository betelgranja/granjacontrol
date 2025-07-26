<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/conexion.php';
require_once '../includes/funciones.php';

// Función para obtener todos los cerdos
function obtenerCerdos($connection, $busqueda = '') {
    if (!empty($busqueda)) {
        $sql = "SELECT * FROM cerdo WHERE nombre ILIKE $1 ORDER BY nombre";
        $result = pg_query_params($connection, $sql, ["%$busqueda%"]);
    } else {
        $sql = "SELECT * FROM cerdo ORDER BY nombre";
        $result = pg_query($connection, $sql);
    }
    
    $cerdos = [];
    while ($row = pg_fetch_assoc($result)) {
        $cerdos[] = $row;
    }
    return $cerdos;
}

// Función para obtener un cerdo específico (para AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener_cerdo' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM cerdo WHERE id = $1";
    $result = pg_query_params($connection, $sql, [$id]);
    
    if ($row = pg_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cerdo no encontrado']);
    }
    exit();
}

// Función para eliminar cerdo
function eliminarCerdo($connection, $id) {
    $sql = "DELETE FROM cerdo WHERE id = $1";
    return pg_query_params($connection, $sql, [$id]);
}

// Procesar eliminación
if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar' && isset($_POST['id'])) {
    $db = new DatabaseConnection();
    $connection = $db->getConnection();
    if (eliminarCerdo($connection, $_POST['id'])) {
        $mensaje = "Cerdo eliminado correctamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar el cerdo";
        $tipo_mensaje = "error";
    }
}

// Obtener cerdos
$db = new DatabaseConnection();
$connection = $db->getConnection();
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$cerdos = obtenerCerdos($connection, $busqueda);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerdos - Granja Porcina</title>
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
                        <a class="nav-link active" href="../cerdos/index3.php"><i class="fas fa-piggy-bank me-1"></i>Cerdos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../controlpeso/controlpeso.php"><i class="fas fa-weight me-1"></i>Control de Peso</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../vacunas/vacuna.php"><i class="fas fa-syringe me-1"></i>Vacunas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../alimentos/alimenos.php"><i class="fas fa-utensils me-1"></i>Alimentos</a>
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
                        <i class="fas fa-pig me-2 text-success"></i>Gestión de Cerdos
                    </h2>
                    <button class="btn btn-success btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Cerdo
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
                        <span class="input-group-text bg-white border-success">
                            <i class="fas fa-search text-success"></i>
                        </span>
                        <input type="text" id="busqueda" class="form-control form-control-lg border-success" 
                               placeholder="Buscar cerdo por nombre..." value="<?php echo htmlspecialchars($busqueda); ?>">
                        <button class="btn btn-outline-success" type="button" id="btnBuscar">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-piggy-bank fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Total Cerdos</h5>
                        <h3 class="text-success"><?php echo count($cerdos); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-weight fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Peso Promedio</h5>
                        <?php 
                        $peso_promedio = 0;
                        if (!empty($cerdos)) {
                            $suma_pesos = array_sum(array_column($cerdos, 'pesoinicial'));
                            $peso_promedio = $suma_pesos / count($cerdos);
                        }
                        ?>
                        <h3 class="text-primary"><?php echo number_format($peso_promedio, 1); ?> kg</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Valor Total</h5>
                        <?php 
                        $valor_total = 0;
                        if (!empty($cerdos)) {
                            $valor_total = array_sum(array_column($cerdos, 'preciocompra'));
                        }
                        ?>
                        <h3 class="text-warning"><?php echo formatearPesos($valor_total); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-plus fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Recientes</h5>
                        <?php 
                        $recientes = 0;
                        if (!empty($cerdos)) {
                            $hoy = new DateTime();
                            foreach ($cerdos as $cerdo) {
                                $fecha_ingreso = new DateTime($cerdo['fechaingreso']);
                                $intervalo = $fecha_ingreso->diff($hoy);
                                if ($intervalo->days <= 30) {
                                    $recientes++;
                                }
                            }
                        }
                        ?>
                        <h3 class="text-info"><?php echo $recientes; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de cerdos -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2 text-success"></i>Listado de Cerdos
                            <span class="badge bg-success float-end"><?php echo count($cerdos); ?> registros</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cerdos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-pig fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No se encontraron cerdos</h4>
                                <p class="text-muted">Agrega tu primer cerdo haciendo clic en el botón "Agregar Cerdo"</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaCerdos">
                                    <thead class="table-success">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Peso Inicial (kg)</th>
                                            <th>Precio Compra</th>
                                            <th>Fecha Ingreso</th>
                                            <th>Observaciones</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cerdos as $cerdo): ?>
                                            <tr class="cerdo-row animate-row">
                                                <td><?php echo htmlspecialchars($cerdo['id']); ?></td>
                                                <td>
                                                    <strong class="text-success"><?php echo htmlspecialchars($cerdo['nombre']); ?></strong>
                                                </td>
                                                <td><?php echo number_format($cerdo['pesoinicial'], 2); ?> kg</td>
                                                <td><?php echo formatearPesos($cerdo['preciocompra']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($cerdo['fechaingreso'])); ?></td>
                                                <td><?php echo htmlspecialchars($cerdo['observaciones'] ?? ''); ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                title="Editar" 
                                                                onclick="editarCerdo(<?php echo $cerdo['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" 
                                                                title="Eliminar" 
                                                                onclick="eliminarCerdo(<?php echo $cerdo['id']; ?>, '<?php echo addslashes($cerdo['nombre']); ?>')">
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

    <!-- Modal Agregar Cerdo -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Cerdo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="procesar.php" class="animate-form">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-tag me-1 text-success"></i>Nombre
                                </label>
                                <input type="text" class="form-control form-control-lg border-success" 
                                       name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-weight me-1 text-primary"></i>Peso Inicial (kg)
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-lg border-primary" 
                                       name="pesoinicial" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-warning"></i>Precio Compra (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-warning" 
                                       name="preciocompra" required placeholder="Ej: 500000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-info"></i>Fecha Ingreso
                                </label>
                                <input type="date" class="form-control form-control-lg border-info" 
                                       name="fechaingreso" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-sticky-note me-1 text-secondary"></i>Observaciones
                            </label>
                            <textarea class="form-control border-secondary" name="observaciones" rows="3" 
                                      placeholder="Notas adicionales sobre el cerdo..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Guardar Cerdo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Cerdo -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Cerdo
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
                                    <i class="fas fa-tag me-1 text-success"></i>Nombre
                                </label>
                                <input type="text" class="form-control form-control-lg border-success" 
                                       name="nombre" id="editar_nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-weight me-1 text-primary"></i>Peso Inicial (kg)
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-lg border-primary" 
                                       name="pesoinicial" id="editar_pesoinicial" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-dollar-sign me-1 text-warning"></i>Precio Compra (COP)
                                </label>
                                <input type="number" step="1" class="form-control form-control-lg border-warning" 
                                       name="preciocompra" id="editar_preciocompra" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1 text-info"></i>Fecha Ingreso
                                </label>
                                <input type="date" class="form-control form-control-lg border-info" 
                                       name="fechaingreso" id="editar_fechaingreso" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-sticky-note me-1 text-secondary"></i>Observaciones
                            </label>
                            <textarea class="form-control border-secondary" name="observaciones" id="editar_observaciones" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Actualizar Cerdo
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
                            <i class="fas fa-pig fa-4x text-danger mb-3"></i>
                            <h4>¿Estás seguro?</h4>
                            <p class="text-muted">
                                Estás a punto de eliminar al cerdo: 
                                <strong id="eliminar_nombre" class="text-danger"></strong>
                            </p>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                Esta acción no se puede deshacer. Todos los registros relacionados también se eliminarán.
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
        // Función para editar cerdo - ahora con AJAX
        function editarCerdo(id) {
            // Mostrar indicador de carga
            document.getElementById('editar_nombre').value = 'Cargando...';
            document.getElementById('editar_pesoinicial').value = '';
            document.getElementById('editar_preciocompra').value = '';
            document.getElementById('editar_fechaingreso').value = '';
            document.getElementById('editar_observaciones').value = '';
            
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
            modal.show();
            
            // Hacer llamada AJAX para obtener los datos del cerdo
            fetch(`?accion=obtener_cerdo&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editar_id').value = data.data.id;
                        document.getElementById('editar_nombre').value = data.data.nombre;
                        document.getElementById('editar_pesoinicial').value = data.data.pesoinicial;
                        document.getElementById('editar_preciocompra').value = data.data.preciocompra;
                        document.getElementById('editar_fechaingreso').value = data.data.fechaingreso;
                        document.getElementById('editar_observaciones').value = data.data.observaciones || '';
                    } else {
                        alert('Error al cargar los datos del cerdo');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del cerdo');
                });
        }

        // Función para eliminar cerdo
        function eliminarCerdo(id, nombre) {
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
        });

        // Formato de moneda en tiempo real
        document.querySelectorAll('input[name="preciocompra"]').forEach(input => {
            input.addEventListener('input', function() {
                // Solo números enteros para COP
                if (this.value) {
                    this.value = parseInt(this.value.replace(/\D/g, '')) || 0;
                }
            });
        });
    </script>
</body>
</html>