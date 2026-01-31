<?php
include('config/config.php');

function obtener_filtro_carrito($con)
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
        return ['field' => 'user_id', 'value' => (int)$_SESSION['user']['id']];
    }

    if (isset($_SESSION['tokenStoragel']) && $_SESSION['tokenStoragel'] !== '') {
        return ['field' => 'tokenCliente', 'value' => mysqli_real_escape_string($con, $_SESSION['tokenStoragel'])];
    }

    return null;
}

/**
 * Funcion para obtener todos los productos
 * de mi tienda
 */
function getProductData($con)
{
    $sqlProducts = ("
        SELECT 
            p.id AS prodId,
            p.nameProd,
            p.precio,
            f.foto1
        FROM 
            products AS p
        INNER JOIN
            fotoproducts AS f
        ON 
            p.id = f.products_id;
    ");
    $queryProducts = mysqli_query($con, $sqlProducts);

    if (!$queryProducts) {
        return false;
    }
    // Si todo está bien, devuelves el resultado del query
    return $queryProducts;
}

/**
 * Detalles del producto seleccionado
 */
function detalles_producto_seleccionado($con, $idProd)
{
    $sqlDetalleProducto = ("
        SELECT 
            p.id AS prodId,
            p.nameProd,
            p.description_Prod,
            p.precio,
            
            f.foto1,
            f.foto2,
            f.foto3
        FROM 
            products AS p
        INNER JOIN
            fotoproducts AS f
        ON 
            p.id = f.products_id
            AND p.id ='" . $idProd . "'
            LIMIT 1;
	");
    $queryProductoSeleccionado = mysqli_query($con, $sqlDetalleProducto);
    if (!$queryProductoSeleccionado) {
        return false;
    }
    return $queryProductoSeleccionado;
}

/**
 * Funciona para validar si el carrito tiene algun producto
 */
function validando_carrito()
{
    if (!obtener_filtro_carrito($GLOBALS['con'])) {
        return '
            <div class="row align-items-center">
                <div class="col-lg-12 text-center mt-5">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Ops.!</strong> Tu carrito está vacío.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="col-lg-12 text-center mt-5 mb-5">
                    <a href="./" class="red_button btn_raza" style="padding: 5px 20px;">
                    <i class="bi bi-arrow-left-circle"></i>  Volver a la Tienda</a>
                </div>
            </div>';
    }
}


/**
 * Retornando productos del carrito de compra
 */
function mi_carrito_de_compra($con)
{
    $filtro = obtener_filtro_carrito($con);
    if ($filtro) {
        $where = $filtro['field'] === 'user_id'
            ? "pt.user_id = " . (int)$filtro['value']
            : "pt.tokenCliente = '" . $filtro['value'] . "'";
        $sqlCarritoCompra = ("
                SELECT 
                    p.id AS prodId,
                    p.nameProd,
                    p.description_Prod,
                    p.precio,
                    f.foto1,
                    pt.id AS tempId,
                    pt.producto_id,
                    pt.cantidad,
                    pt.tokenCliente
                FROM 
                    products AS p
                INNER JOIN
                    fotoproducts AS f ON p.id = f.products_id
                INNER JOIN
                    pedidostemporales AS pt ON p.id = pt.producto_id
                WHERE 
                    " . $where . ");
        $queryCarrito   = mysqli_query($con, $sqlCarritoCompra);
        if (!$queryCarrito) {
            return false;
        }
        return $queryCarrito;
    } else {
        return 0;
    }
}


/**
 * Mostrar la cantidad de productos seleccionados en el icono de carrito
 */
function iconoCarrito($con)
{
    $filtro = obtener_filtro_carrito($con);
    if ($filtro) {
        $where = $filtro['field'] === 'user_id'
            ? "user_id = " . (int)$filtro['value']
            : "tokenCliente = '" . $filtro['value'] . "'";
        $sqlTotalProduct = "SELECT SUM(cantidad) AS totalProd FROM pedidostemporales WHERE " . $where . " GROUP BY " . $filtro['field'];
        $jqueryTotalProduct = mysqli_query($con, $sqlTotalProduct);

        if ($jqueryTotalProduct) {
            // La consulta se ejecutó correctamente
            $dataTotalProducto = mysqli_fetch_array($jqueryTotalProduct);
            return '<span id="checkout_items" class="checkout_items">' . $dataTotalProducto["totalProd"] . '</span>';
        } else {
            return '<span id="checkout_items" class="checkout_items">0</span>';
        }
    } else {
        return '<span id="checkout_items" class="checkout_items">0</span>';
    }
}

function totalAcumuladoDeuda($con)
{
    $filtro = obtener_filtro_carrito($con);
    if ($filtro) {
        $where = $filtro['field'] === 'user_id'
            ? "pt.user_id = " . (int)$filtro['value']
            : "pt.tokenCliente = '" . $filtro['value'] . "'";
        $SqlDeudaTotal = "
        SELECT SUM(p.precio * pt.cantidad) AS totalPagar 
        FROM products AS p
        INNER JOIN pedidostemporales AS pt
        ON p.id = pt.producto_id
        WHERE " . $where . "
        ";
        $jqueryDeuda = mysqli_query($con, $SqlDeudaTotal);
        $dataDeuda = mysqli_fetch_array($jqueryDeuda);
        return  number_format($dataDeuda['totalPagar'], 0, '', '.');
    }
}
