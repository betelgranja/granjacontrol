<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Incluir conexión a base de datos
require_once '../includes/conexion.php';

// Función para formatear moneda en Pesos Colombianos
function formatearPesos($valor) {
    return '$' . number_format($valor, 0, ',', '.') . ' COP';
}

function formatearPesosDecimales($valor) {
    return '$' . number_format($valor, 2, ',', '.') . ' COP';
}

// Funciones para obtener estadísticas usando pg_connect
function getEstadisticas($connection) {
    $estadisticas = [];
    try {
        // Total de cerdos
        $result = pg_query($connection, "SELECT COUNT(*) as total FROM cerdo");
        $row = pg_fetch_assoc($result);
        $estadisticas['total_cerdos'] = $row['total'];

        // Peso promedio
        $result = pg_query($connection, "SELECT AVG(pesoinicial) as promedio FROM cerdo WHERE pesoinicial IS NOT NULL");
        $row = pg_fetch_assoc($result);
        $estadisticas['peso_promedio'] = round($row['promedio'] ?? 0, 2);

        // Vacunas pendientes (próximos 30 días)
        $result = pg_query($connection, "SELECT COUNT(*) as pendientes FROM vacunas WHERE proximaaplicacion <= CURRENT_DATE + INTERVAL '30 days' AND proximaaplicacion >= CURRENT_DATE");
        $row = pg_fetch_assoc($result);
        $estadisticas['vacunas_pendientes'] = $row['pendientes'];

        // Gastos del mes
        $result = pg_query($connection, "SELECT COALESCE(SUM(valorgasto), 0) as total FROM gastos WHERE fechagasto >= DATE_TRUNC('month', CURRENT_DATE)");
        $row = pg_fetch_assoc($result);
        $estadisticas['gastos_mes'] = $row['total'];

        // Cerdo más gordo
        $result = pg_query($connection, "SELECT nombre, pesoinicial FROM cerdo WHERE pesoinicial = (SELECT MAX(pesoinicial) FROM cerdo WHERE pesoinicial IS NOT NULL) LIMIT 1");
        $row = pg_fetch_assoc($result);
        $estadisticas['cerdo_mas_gordo'] = $row ? $row['nombre'] . ' (' . $row['pesoinicial'] . ' kg)' : 'No hay datos';

        // Datos para gráficas
        // Compras de alimentos del mes
        $result = pg_query($connection, "SELECT COALESCE(SUM(preciocompra), 0) as total FROM alimentos WHERE fechacompra >= DATE_TRUNC('month', CURRENT_DATE)");
        $row = pg_fetch_assoc($result);
        $estadisticas['compras_alimentos'] = $row['total'];

        // Compras de cerdos del mes
        $result = pg_query($connection, "SELECT COALESCE(SUM(preciocompra), 0) as total FROM cerdo WHERE fechaingreso >= DATE_TRUNC('month', CURRENT_DATE)");
        $row = pg_fetch_assoc($result);
        $estadisticas['compras_cerdos'] = $row['total'];

        // Costo de vacunas del mes
        $result = pg_query($connection, "SELECT COALESCE(SUM(valorvacuna), 0) as total FROM vacunas WHERE fechaaplicacion >= DATE_TRUNC('month', CURRENT_DATE)");
        $row = pg_fetch_assoc($result);
        $estadisticas['costo_vacunas'] = $row['total'];

        // Otros gastos del mes
        $result = pg_query($connection, "SELECT COALESCE(SUM(valorgasto), 0) as total FROM gastos WHERE fechagasto >= DATE_TRUNC('month', CURRENT_DATE)");
        $row = pg_fetch_assoc($result);
        $estadisticas['otros_gastos'] = $row['total'];

    } catch (Exception $e) {
        // Valores por defecto en caso de error
        $estadisticas = [
            'total_cerdos' => 0,
            'peso_promedio' => 0,
            'vacunas_pendientes' => 0,
            'gastos_mes' => 0,
            'cerdo_mas_gordo' => 'Sin datos',
            'compras_alimentos' => 0,
            'compras_cerdos' => 0,
            'costo_vacunas' => 0,
            'otros_gastos' => 0
        ];
    }
    return $estadisticas;
}


// Obtener estadísticas
$db = new DatabaseConnection();
$connection = $db->getConnection();
$estadisticas = getEstadisticas($connection);

// Calcular el total para la gráfica
$total_gastos_grafica = $estadisticas['compras_alimentos'] + $estadisticas['compras_cerdos'] + $estadisticas['costo_vacunas'] + $estadisticas['otros_gastos'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Granja Porcina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-piggy-bank me-2"></i>Granja Porcina
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="../cerdos/index3.php"><i class="fas fa-home me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../cerdos/index3.php"><i class="fas fa-piggy-bank  me-1"></i>Cerdos</a>
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
        <!-- Estadísticas principales -->
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">
                    <i class="fas fa-chart-line me-2 text-success"></i>Dashboard - Estadísticas Generales
                </h2>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card border-success h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <i class="fas fa-piggy-bank fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Total Cerdos</h5>
                        <h2 class="text-success"><?php echo number_format($estadisticas['total_cerdos']); ?></h2>
                        <small class="text-muted">Animales registrados</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card border-primary h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <i class="fas fa-weight fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Peso Promedio</h5>
                        <h2 class="text-primary"><?php echo number_format($estadisticas['peso_promedio'], 1); ?> kg</h2>
                        <small class="text-muted">Peso inicial promedio</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card border-warning h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <i class="fas fa-syringe fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Vacunas Pendientes</h5>
                        <h2 class="text-warning"><?php echo $estadisticas['vacunas_pendientes']; ?></h2>
                        <small class="text-muted">Próximos 30 días</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card border-info h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Gastos del Mes</h5>
                        <h2 class="text-info"><?php echo formatearPesos($estadisticas['gastos_mes']); ?></h2>
                        <small class="text-muted">Total mensual</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cerdo más gordo -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>Cerdo Más Gordo
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-crown fa-3x text-warning mb-3"></i>
                        <h3 class="text-success"><?php echo htmlspecialchars($estadisticas['cerdo_mas_gordo']); ?></h3>
                        <p class="text-muted">¡El campeón del corral!</p>
                    </div>
                </div>
            </div>
        </div>

       <!-- Gráficas -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2 text-success"></i>Distribución de Gastos del Mes
                </h5>
            </div>
            <div class="card-body">
                <?php if ($estadisticas['compras_alimentos'] + $estadisticas['compras_cerdos'] + $estadisticas['costo_vacunas'] + $estadisticas['otros_gastos'] > 0): ?>
                    <canvas id="gastosChart" height="300"></canvas>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay datos suficientes</h4>
                        <p class="text-muted">Agrega gastos para ver la distribución</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>Resumen Mensual
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-utensils text-success me-2"></i>Alimentos</span>
                        <span class="badge bg-success rounded-pill"><?php echo formatearPesos($estadisticas['compras_alimentos']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-piggy-bank text-primary me-2"></i>Nuevos Cerdos</span>
                        <span class="badge bg-primary rounded-pill"><?php echo formatearPesos($estadisticas['compras_cerdos']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-syringe text-warning me-2"></i>Vacunas</span>
                        <span class="badge bg-warning rounded-pill"><?php echo formatearPesos($estadisticas['costo_vacunas']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-invoice-dollar text-info me-2"></i>Otros Gastos</span>
                        <span class="badge bg-info rounded-pill"><?php echo formatearPesos($estadisticas['otros_gastos']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center fw-bold">
                        <span><i class="fas fa-calculator text-danger me-2"></i>Total</span>
                        <span class="badge bg-danger rounded-pill"><?php echo formatearPesos($estadisticas['compras_alimentos'] + $estadisticas['compras_cerdos'] + $estadisticas['costo_vacunas'] + $estadisticas['otros_gastos']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Menú de navegación rápido -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-compass me-2 text-success"></i>Navegación Rápida
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 col-6 mb-3">
                        <a href="../cerdos/index3.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-piggy-bank fa-2x mb-2"></i><br>
                            <small>Cerdos</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="../controlpeso/controlpeso.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-weight fa-2x mb-2"></i><br>
                            <small>Pesos</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="../vacunas/vacuna.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-syringe fa-2x mb-2"></i><br>
                            <small>Vacunas</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="../alimentos/alimentos.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-utensils fa-2x mb-2"></i><br>
                            <small>Alimentos</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="../gastos/gastos.php" class="btn btn-outline-danger w-100">
                            <i class="fas fa-money-bill-wave fa-2x mb-2"></i><br>
                            <small>Gastos</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <a href="../configuracion/configuracion.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-cog fa-2x mb-2"></i><br>
                            <small>Config</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfica de gastos - Solo se inicializa si hay datos
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($total_gastos_grafica > 0): ?>
            const ctx = document.getElementById('gastosChart').getContext('2d');
            const gastosChart = new Chart(ctx, {
                type: 'pie',
                 {
                    labels: ['Alimentos', 'Nuevos Cerdos', 'Vacunas', 'Otros Gastos'],
                    datasets: [{
                         [
                            <?php echo $estadisticas['compras_alimentos']; ?>,
                            <?php echo $estadisticas['compras_cerdos']; ?>,
                            <?php echo $estadisticas['costo_vacunas']; ?>,
                            <?php echo $estadisticas['otros_gastos']; ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#007bff',
                            '#ffc107',
                            '#17a2b8'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    // Formato para Pesos Colombianos en tooltip
                                    return `${label}: $${parseFloat(value).toLocaleString('es-CO', {maximumFractionDigits: 0})} COP`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            // Efectos hover para tarjetas
            const cards = document.querySelectorAll('.hover-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>