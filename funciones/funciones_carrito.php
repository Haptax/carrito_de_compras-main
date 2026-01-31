<?php
session_start();
include('../config/config.php');
// Establecer las cabeceras para indicar que la respuesta es en formato JSON
header('Content-Type: application/json');

$sessionUserId = isset($_SESSION['user']) && !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

function tiene_columna_user_id($con)
{
    static $hasUserId = null;
    if ($hasUserId !== null) {
        return $hasUserId;
    }
    $result = mysqli_query($con, "SHOW COLUMNS FROM pedidostemporales LIKE 'user_id'");
    $hasUserId = $result && mysqli_num_rows($result) > 0;
    return $hasUserId;
}

function filtro_carrito_api($con, $tokenCliente, $sessionUserId)
{
    if ($sessionUserId && tiene_columna_user_id($con)) {
        return ['field' => 'user_id', 'value' => (int)$sessionUserId];
    }
    if ($tokenCliente !== null && $tokenCliente !== '') {
        return ['field' => 'tokenCliente', 'value' => mysqli_real_escape_string($con, $tokenCliente)];
    }
    return null;
}


if (isset($_POST["aumentarCantidad"])) {
    $idProd               = $_POST['idProd'];
    $precio               = $_POST['precio'];
    $tokenCliente         = $_POST['tokenCliente'];
    $cantidaProducto      = $_POST['aumentarCantidad'];

    $filtro = filtro_carrito_api($con, $tokenCliente, $sessionUserId);
    if (!$filtro) {
        echo json_encode(['estado' => 'ERROR']);
        exit;
    }
    $where = $filtro && $filtro['field'] === 'user_id'
        ? "user_id = " . (int)$filtro['value']
        : "tokenCliente = '" . $filtro['value'] . "'";

    $UpdateCant = "UPDATE pedidostemporales 
              SET cantidad ='$cantidaProducto'
              WHERE " . $where . "
              AND id='$idProd'";
    $result = mysqli_query($con, $UpdateCant);

    $responseData = array(
        'estado' => 'OK',
        'totalPagar' => totalAccionAumentarDisminuir($con, $filtro)
    );
    // Enviar la respuesta JSON
    echo json_encode($responseData);
}



/**
 * Agregar a carrito de compra el producto
 */
if (isset($_POST["accion"]) && $_POST["accion"] == "addCar") {
    if (!$sessionUserId) {
        $_SESSION['tokenStoragel']  = $_POST['tokenCliente'];
    }
    $idProduct                  = $_POST['idProduct'];
    $precio                     = $_POST['precio'];
    $tokenCliente               = $_POST['tokenCliente'];

    $filtro = filtro_carrito_api($con, $tokenCliente, $sessionUserId);
    $where = $filtro && $filtro['field'] === 'user_id'
        ? "user_id = " . (int)$filtro['value']
        : "tokenCliente = '" . $filtro['value'] . "'";

    //Verifico si ya existe el producto almacenado en la tabla temporal de acuerdo al Token Unico del Cliente
    $ConsultarProduct = ("SELECT * FROM pedidostemporales WHERE " . $where . " AND producto_id='" . $idProduct . "' ");
    $jqueryProduct    = mysqli_query($con, $ConsultarProduct);
    //Caso 1; si ya existe dicho producto agregado con respecto al token que tiene asignado el Cliente.
    if (mysqli_num_rows($jqueryProduct) > 0) {
        $DataProducto     = mysqli_fetch_array($jqueryProduct);
        $newCantidad   = ($DataProducto['cantidad'] + 1);

        $UdateCantidad = ("UPDATE pedidostemporales SET cantidad='" . $newCantidad . "' WHERE producto_id='" . $idProduct . "' AND " . $where . " ");
        $resultUpdat = mysqli_query($con, $UdateCantidad);
    } else {
        //Caso 2; No existe producto agregado en la tabla de pedidos
        if ($filtro && $filtro['field'] === 'user_id') {
            $InsertProduct = ("INSERT INTO pedidostemporales (producto_id, cantidad, user_id) VALUES ('$idProduct','1','" . (int)$filtro['value'] . "')");
        } else {
            $InsertProduct = ("INSERT INTO pedidostemporales (producto_id, cantidad, tokenCliente) VALUES ('$idProduct','1','$tokenCliente')");
        }
        $result = mysqli_query($con, $InsertProduct);
    }

    //Total carrito en el icono de compra
    $SqlTotalProduct       = ("SELECT SUM(cantidad) AS totalProd FROM pedidostemporales WHERE " . $where . " GROUP BY " . $filtro['field'] . "");
    $jqueryTotalProduct    = mysqli_query($con, $SqlTotalProduct);
    $DataTotalProducto     = mysqli_fetch_array($jqueryTotalProduct);
    echo $DataTotalProducto['totalProd'];
}

/**
 * Disminuir cantidad de mi carrito de compra
 */
if (isset($_POST["accion"]) && $_POST["accion"] == "disminuirCantidad") {

    if (!$sessionUserId) {
        $_SESSION['tokenStoragel']  = $_POST['tokenCliente'];
    }
    // Evitar posibles ataques de inyección SQL escapando las variables
    $idProd                     = mysqli_real_escape_string($con, $_POST['idProd']);
    $precio                     = mysqli_real_escape_string($con, $_POST['precio']);
    $tokenCliente               = mysqli_real_escape_string($con, $_POST['tokenCliente']);
    $cantidad_Disminuida        = mysqli_real_escape_string($con, $_POST['cantidad_Disminuida']);

    $filtro = filtro_carrito_api($con, $tokenCliente, $sessionUserId);
    $where = $filtro && $filtro['field'] === 'user_id'
        ? "user_id = " . (int)$filtro['value']
        : "tokenCliente = '" . $filtro['value'] . "'";

    if ($cantidad_Disminuida == 0) {
        $DeleteRegistro = ("DELETE FROM pedidostemporales WHERE " . $where . " AND id='" . $idProd . "' ");
        mysqli_query($con, $DeleteRegistro);
        $responseData = array(
            'totalProductos' => totalProductosSeleccionados($con, $filtro),
            'totalPagar' => totalAccionAumentarDisminuir($con, $filtro),
            'estado' => 'OK'
        );
    } else {
        $UpdateCant = ("UPDATE pedidostemporales 
    SET cantidad ='$cantidad_Disminuida'
    WHERE " . $where . " 
    AND id='" . $idProd . "' ");
        $result = mysqli_query($con, $UpdateCant);

        $responseData = array(
            'totalProductos' => totalProductosSeleccionados($con, $filtro),
            'totalPagar' => totalAccionAumentarDisminuir($con, $filtro),
            'estado' => 'OK'
        );
    }

    // Enviar la respuesta JSON
    echo json_encode($responseData);
}


/**
 * Borrar producto del carrito
 */
if (isset($_POST["accion"]) && $_POST["accion"] == "borrarproductoModal") {
    $nameTokenProducto  = $_POST['tokenCliente'];

    $filtro = filtro_carrito_api($con, $nameTokenProducto, $sessionUserId);
    $where = $filtro && $filtro['field'] === 'user_id'
        ? "user_id = " . (int)$filtro['value']
        : "tokenCliente = '" . $filtro['value'] . "'";

    $DeleteRegistro = ("DELETE FROM pedidostemporales WHERE " . $where . " AND id= '" . $_POST["idProduct"] . "' ");
    mysqli_query($con, $DeleteRegistro);

    $respData = array(
        'totalProductos' => totalProductosSeleccionados($con, $filtro),
        'totalProductoSeleccionados' => totalProductosBD($con, $filtro),
        'totalPagar' => totalAccionAumentarDisminuir($con, $filtro),
        'estado' => 'OK'
    );
    echo json_encode($respData);
}

/**
 * Finalizar pedido y registrar historial
 */
if (isset($_POST["accion"]) && $_POST["accion"] == "finalizarPedido") {
    $tokenCliente = isset($_POST['tokenCliente']) ? $_POST['tokenCliente'] : '';

    $tablaPedidos = mysqli_query($con, "SHOW TABLES LIKE 'pedidos'");
    $tablaDetalle = mysqli_query($con, "SHOW TABLES LIKE 'pedidos_detalle'");
    if (!($tablaPedidos && mysqli_num_rows($tablaPedidos) > 0) || !($tablaDetalle && mysqli_num_rows($tablaDetalle) > 0)) {
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'Falta crear las tablas de pedidos.']);
        exit;
    }

    $filtro = filtro_carrito_api($con, $tokenCliente, $sessionUserId);
    if (!$filtro) {
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'No hay carrito activo.']);
        exit;
    }
    $where = $filtro['field'] === 'user_id'
        ? "pt.user_id = " . (int)$filtro['value']
        : "pt.tokenCliente = '" . mysqli_real_escape_string($con, $filtro['value']) . "'";

    $sqlItems = "SELECT pt.producto_id, pt.cantidad, p.precio FROM pedidostemporales AS pt INNER JOIN products AS p ON pt.producto_id = p.id WHERE " . $where;
    $itemsResult = mysqli_query($con, $sqlItems);
    if (!$itemsResult || mysqli_num_rows($itemsResult) === 0) {
        echo json_encode(['estado' => 'ERROR', 'mensaje' => 'El carrito está vacío.']);
        exit;
    }

    $items = [];
    $total = 0;
    while ($row = mysqli_fetch_assoc($itemsResult)) {
        $items[] = $row;
        $total += ((int)$row['cantidad']) * ((float)$row['precio']);
    }

    mysqli_begin_transaction($con);
    try {
        if ($filtro['field'] === 'user_id') {
            $sqlPedido = "INSERT INTO pedidos (user_id, total) VALUES (" . (int)$filtro['value'] . ", " . $total . ")";
        } else {
            $sqlPedido = "INSERT INTO pedidos (tokenCliente, total) VALUES ('" . mysqli_real_escape_string($con, $filtro['value']) . "', " . $total . ")";
        }

        $okPedido = mysqli_query($con, $sqlPedido);
        if (!$okPedido) {
            throw new Exception('No se pudo crear el pedido.');
        }
        $pedidoId = mysqli_insert_id($con);

        foreach ($items as $item) {
            $sqlDetalle = "INSERT INTO pedidos_detalle (pedido_id, producto_id, cantidad, precio) VALUES (" . (int)$pedidoId . ", " . (int)$item['producto_id'] . ", " . (int)$item['cantidad'] . ", " . (float)$item['precio'] . ")";
            $okDetalle = mysqli_query($con, $sqlDetalle);
            if (!$okDetalle) {
                throw new Exception('No se pudo registrar el detalle.');
            }
        }

        mysqli_commit($con);
        echo json_encode(['estado' => 'OK', 'pedidoId' => $pedidoId]);
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo json_encode(['estado' => 'ERROR', 'mensaje' => $e->getMessage()]);
    }
}

/**
 * Total productos en mi carrito de compra
 */
function totalProductosBD($con, $filtro)
{
    if (!$filtro) {
        return 0;
    }
    $where = $filtro['field'] === 'user_id'
        ? "user_id = " . (int)$filtro['value']
        : "tokenCliente = '" . $filtro['value'] . "'";
    $sqlTotalProduct = "SELECT SUM(cantidad) AS totalProd FROM pedidostemporales WHERE " . $where . " GROUP BY " . $filtro['field'];
    $jqueryTotalProduct = mysqli_query($con, $sqlTotalProduct);
    if ($jqueryTotalProduct) {
        $dataTotalProducto = mysqli_fetch_array($jqueryTotalProduct);
        return  $dataTotalProducto["totalProd"];
    } else {
        return 0;
    }
}

function totalAccionAumentarDisminuir($con, $filtro)
{
    if (!$filtro) {
        return 0;
    }
    $where = $filtro['field'] === 'user_id'
        ? "pt.user_id = " . (int)$filtro['value']
        : "pt.tokenCliente = '" . $filtro['value'] . "'";
    $SqlDeudaTotal = "
        SELECT SUM(p.precio * pt.cantidad) AS totalPagar 
        FROM products AS p
        INNER JOIN pedidostemporales AS pt
        ON p.id = pt.producto_id
        WHERE " .  $where . "";
    $jqueryDeuda = mysqli_query($con, $SqlDeudaTotal);
    $dataDeuda = mysqli_fetch_array($jqueryDeuda);
    return $dataDeuda['totalPagar'];
}

/**
 * Funcion que esta al pendiente de verificar si hay pedidos activos por el usuario en cuestión
 */
function totalProductosSeleccionados($con, $filtro)
{
    if (!$filtro) {
        return 0;
    }
    $where = $filtro['field'] === 'user_id'
        ? "user_id = " . (int)$filtro['value']
        : "tokenCliente = '" . $filtro['value'] . "'";
    $ConsultarProduct = ("SELECT * FROM pedidostemporales WHERE " . $where . " ");
    $jqueryProduct    = mysqli_query($con, $ConsultarProduct);
    if (mysqli_num_rows($jqueryProduct) > 0) {
        return mysqli_num_rows($jqueryProduct);
    } else {
        return 0;
    }
}

/**
 * funcion limpiar carrito
 */
if (isset($_POST["accion"]) && $_POST["accion"] == "limpiarTodoElCarrito") {
    $tokenCliente = isset($_POST['tokenCliente']) ? $_POST['tokenCliente'] : '';
    $filtro = filtro_carrito_api($con, $tokenCliente, $sessionUserId);
    if ($filtro) {
        $where = $filtro['field'] === 'user_id'
            ? "user_id = " . (int)$filtro['value']
            : "tokenCliente = '" . $filtro['value'] . "'";
        mysqli_query($con, "DELETE FROM pedidostemporales WHERE " . $where);
    }

    if (!$sessionUserId) {
        unset($_SESSION['tokenStoragel']);
    }

    echo json_encode(['mensaje' => 1]);
}
