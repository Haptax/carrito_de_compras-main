<?php

function autenticar_usuario($con, $email, $password)
{
    $email = trim(strtolower($email));

    $stmt = mysqli_prepare($con, "SELECT id, nombre, email, password_hash FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuario = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$usuario) {
        return false;
    }

    if (!password_verify($password, $usuario['password_hash'])) {
        return false;
    }

    return [
        'id' => (int)$usuario['id'],
        'nombre' => $usuario['nombre'],
        'email' => $usuario['email']
    ];
}

function registrar_usuario($con, $nombre, $email, $password)
{
    $nombre = trim($nombre);
    $email = trim(strtolower($email));

    $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo validar el correo.'];
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existe = $result && mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($existe) {
        return ['ok' => false, 'error' => 'El correo ya está registrado.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($con, "INSERT INTO users (nombre, email, password_hash) VALUES (?, ?, ?)");
    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo registrar el usuario.'];
    }
    mysqli_stmt_bind_param($stmt, "sss", $nombre, $email, $hash);
    $ok = mysqli_stmt_execute($stmt);
    $userId = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['ok' => false, 'error' => 'No se pudo registrar el usuario.'];
    }

    return [
        'ok' => true,
        'user' => [
            'id' => (int)$userId,
            'nombre' => $nombre,
            'email' => $email
        ]
    ];
}

function usuario_actual()
{
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function cerrar_sesion()
{
    session_unset();
    session_destroy();
}
