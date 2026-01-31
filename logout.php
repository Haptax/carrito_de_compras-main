<?php
session_start();
require_once 'funciones/funciones_auth.php';

cerrar_sesion();
header('Location: index.php');
exit;
