<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'includes/conexion.php';
    
    $usuario = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    
    if (!empty($usuario) && !empty($contrasena)) {
        try {
            $db = new DatabaseConnection();
            $connection = $db->getConnection();
            
            $sql = "SELECT id, usuario, contrasena FROM login WHERE usuario = $1";
            $result = pg_query_params($connection, $sql, [$usuario]);
            
            if ($result && pg_num_rows($result) > 0) {
                $user = pg_fetch_assoc($result);
                
                // Comparación directa (en producción usa password_hash)
                if ($contrasena === $user['contrasena']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['usuario'];
                    header('Location: dashboard/index2.php');
                    exit();
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error en la autenticación: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Granja Porcina - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-5 col-md-7">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-success text-white text-center py-4">
                        <div class="icon-circle bg-white text-success mx-auto mb-3">
                            <i class="fas fa-piggy-bank fa-2x"></i>
                        </div>
                        <h2 class="mb-0 fw-bold">Granja Porcina</h2>
                        <p class="mb-0">Sistema de Control y Gestión</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="animate__animated animate__fadeIn">
                            <div class="mb-4">
                                <label for="usuario" class="form-label fw-semibold">
                                    <i class="fas fa-user me-2 text-success"></i>Usuario
                                </label>
                                <input type="text" class="form-control form-control-lg border-success" 
                                       id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="contrasena" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-success"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control form-control-lg border-success" 
                                           id="contrasena" name="contrasena" placeholder="Ingrese su contraseña" required>
                                    <button class="btn btn-outline-success" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-success btn-lg rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>Sistema seguro de gestión ganadera
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/Ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#contrasena');
            
            if (togglePassword && password) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    // Cambiar icono
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>