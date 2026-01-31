<?php
session_start();
require_once 'config/config.php';
require_once 'funciones/funciones_auth.php';
require_once 'funciones/funciones_tienda.php';

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($nombre === '' || $email === '' || $password === '' || $confirm === '') {
        $errores[] = 'Completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no es válido.';
    } elseif ($password !== $confirm) {
        $errores[] = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $resultado = registrar_usuario($con, $nombre, $email, $password);
        if (!empty($resultado['ok'])) {
            $_SESSION['user'] = $resultado['user'];
            if (isset($_SESSION['tokenStoragel']) && $_SESSION['tokenStoragel'] !== '') {
                $token = mysqli_real_escape_string($con, $_SESSION['tokenStoragel']);
                $userId = (int)$resultado['user']['id'];
                mysqli_query($con, "UPDATE pedidostemporales SET user_id = " . $userId . " WHERE tokenCliente = '" . $token . "'");
                unset($_SESSION['tokenStoragel']);
            }
            header('Location: index.php');
            exit;
        }
        $errores[] = $resultado['error'] ?? 'No se pudo registrar.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="assets/images/icon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="assets/styles/bootstrap4/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/styles/main_styles.css">
    <link rel="stylesheet" type="text/css" href="assets/styles/responsive.css">
    <link rel="stylesheet" href="assets/styles/loader.css">
    <title>Registro</title>
</head>

<body>
    <div class="page-loading active">
        <div class="page-loading-inner">
            <div class="page-spinner"></div>
            <span>cargando...</span>
        </div>
    </div>

    <div class="super_container">
        <?php include('header.php'); ?>

        <div class="container mt-5 pt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="mb-4 text-center">Crear cuenta</h3>
                            <?php if (!empty($errores)) { ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errores as $error) { ?>
                                        <div><?php echo htmlspecialchars($error); ?></div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Correo</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Contraseña</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirmar contraseña</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Registrarme</button>
                            </form>
                            <div class="text-center mt-3">
                                ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <script src="assets/styles/bootstrap4/bootstrap.min.js"></script>
    <script src="assets/js/loader.js"></script>
</body>

</html>
