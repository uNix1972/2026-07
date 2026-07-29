<?php
namespace Controllers;

use Views\Renderer;
use Dao\Producto as DaoProducto;
use Dao\AjusteInventario as DaoAjusteInventario;
use Dao\CentroSalud as DaoCentroSalud;
use Dao\MovimientoInventario as DaoMovimientoInventario;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Utilities\AuditLogger;

class InventarioController extends PrivateController
{
    public function run(): void
    {
        $action = trim(strval($_GET["action"] ?? "index"));

        switch ($action) {
            case "index":
                $this->index();
                break;

            case "create":
                $this->create();
                break;

            case "edit":
                $this->edit();
                break;

            case "delete":
                $this->delete();
                break;

            case "eliminar":
                $this->eliminar();
                break;

            case "activar":
                $this->activar();
                break;

            case "ajustar":
                $this->ajustar();
                break;

            case "kardex":
                $this->kardex();
                break;

            default:
                $this->index();
                break;
        }
    }

    private function buildUnidadesMedida(string $seleccion): array
    {
        $unidades = DaoProducto::UNIDADES_MEDIDA;
        $yaExiste = false;
        foreach ($unidades as $unidad) {
            if (strcasecmp($unidad, $seleccion) === 0) {
                $yaExiste = true;
                break;
            }
        }
        if ($seleccion !== "" && !$yaExiste) {
            $unidades[] = $seleccion;
        }

        return array_map(function ($unidad) use ($seleccion) {
            return [
                "valor" => $unidad,
                "selected" => strcasecmp($unidad, $seleccion) === 0
            ];
        }, $unidades);
    }

    private function index(): void
    {
        // Aviso de "no se pudo eliminar" que llega por GET después de un
        // redirect desde eliminar() (cuando la base de datos rechaza el
        // borrado por tener compras registradas). Ver el catch en
        // eliminar() para el detalle.
        $errorEliminarProducto = trim((string) ($_GET["errorEliminar"] ?? ""));

        // --- Barra de búsqueda de producto para la tabla de INVENTARIO ---
        // Mismo componente que ya se usa en el Kárdex (kardex-autocomplete.js
        // + Utilities\Site::addEndScript): un <input> de texto que filtra en
        // el navegador la lista de productos ya cargada, sin ir al servidor
        // mientras se escribe, y que al elegir una opción o presionar Enter
        // manda el id real por un <input hidden> y envía el formulario.
        //
        // PERO: si el usuario escribe un nombre que NO coincide con ningún
        // producto (o le da clic al botón "Buscar" sin haber elegido una
        // sugerencia de la lista), el <input hidden> se queda vacío y antes
        // no había forma de saber qué había escrito, así que la pantalla
        // simplemente mostraba TODOS los productos sin avisar nada — el bug
        // que reportó Johnny. Por eso el campo de texto también viaja al
        // servidor como "q", y si no hay producto_id (o sea, no se eligió
        // nada de la lista) se hace una búsqueda por coincidencia de nombre
        // aquí en el servidor, y si tampoco encuentra nada, se muestra el
        // aviso "No se encontró..." usando lo que el usuario escribió.
        $productoBuscadoId = Validators::sanitizeId($_GET["producto_id"] ?? "");
        $productoBuscadoQuery = trim((string) ($_GET["q"] ?? ""));

        // --- Barra de búsqueda de producto para "Movimientos recientes" ---
        // Mismo mecanismo, con nombres de parámetro distintos (mov_producto_id
        // / mov_q) para no chocar con la búsqueda de la tabla de productos:
        // ambos filtros conviven en la misma URL/pantalla al mismo tiempo.
        $movProductoBuscadoId = Validators::sanitizeId($_GET["mov_producto_id"] ?? "");
        $movProductoBuscadoQuery = trim((string) ($_GET["mov_q"] ?? ""));

        // --- Inventario "a una fecha" ------------------------------------
        // Si viene fecha_corte, no se muestra el stock ACTUAL de cada
        // producto, sino el que tenía reconstruido a partir del historial
        // de movimientos hasta el final de ese día (misma lógica de saldo
        // acumulado que ya usa el Kárdex, solo que aplicada a todos los
        // productos de una vez). Esto es posible precisamente porque el
        // Kárdex ya une ajustes + compras en una sola fuente confiable.
        $fechaCorte = Validators::sanitizeDate($_GET["fecha_corte"] ?? "");
        $saldosHistoricos = $fechaCorte !== null
            ? DaoMovimientoInventario::getSaldosPorProductoAFecha($fechaCorte)
            : null;

        $productos = DaoProducto::getAll();
        foreach ($productos as &$producto) {
            $productoId = (int) $producto["id"];
            $producto["esActivo"] = ($producto["estado"] ?? "") === "ACT";

            if ($saldosHistoricos === null) {
                // Modo normal: lo de siempre, el stock en vivo.
                $producto["modoHistorico"] = false;
                $producto["noExistiaAun"] = false;
                $producto["stockMostrado"] = (int) $producto["stock_actual"];
                $producto["stock_bajo"] = intval($producto["stock_actual"]) < intval($producto["stock_minimo"]);
            } else {
                // Modo histórico: si el producto se creó DESPUÉS de la
                // fecha de corte, todavía no existía — mostrar "0" ahí
                // sería engañoso (parecería que existía sin stock).
                $fechaCreacion = substr((string) ($producto["fecha_creacion"] ?? ""), 0, 10);
                $existiaEnFecha = $fechaCreacion === "" || $fechaCreacion <= $fechaCorte;

                $producto["modoHistorico"] = true;
                $producto["noExistiaAun"] = !$existiaEnFecha;
                if ($existiaEnFecha) {
                    $stockHistorico = $saldosHistoricos[$productoId] ?? 0;
                    $producto["stockMostrado"] = $stockHistorico;
                    // OJO: el stock_minimo usado aquí es el ACTUAL, no el
                    // que tenía el producto en esa fecha (no se lleva un
                    // historial de ese campo). Es una limitación conocida,
                    // no un error de cálculo del stock en sí.
                    $producto["stock_bajo"] = $stockHistorico < intval($producto["stock_minimo"]);
                } else {
                    $producto["stockMostrado"] = null;
                    $producto["stock_bajo"] = false;
                }
            }
        }
        unset($producto);

        // Registra el script de la barra de búsqueda con autocompletar
        // (el mismo archivo que ya usa el Kárdex). Se agrega aquí y no en
        // el layout general porque solo esta pantalla lo necesita.
        Site::addEndScript('public/js/kardex-autocomplete.js');

        // Lista de productos para AMBOS buscadores (tabla de productos y
        // Movimientos recientes comparten el mismo catálogo), en el formato
        // {id, nombre} que espera kardex-autocomplete.js, escapada para
        // poder viajar dentro de un atributo data-options (el motor de
        // plantillas de este proyecto no escapa automáticamente lo que
        // imprime).
        $productosParaBuscador = $this->mapearParaBuscador($productos);
        $productosJsonAttr = $this->jsonAttrParaAutocompletar($productosParaBuscador);

        // Si se buscó un producto puntual, la tabla de abajo se recorta a
        // solo ese producto (útil cuando hay muchos y no se quiere scrollear
        // toda la lista). El campo de texto necesita el nombre ya resuelto
        // para mostrarlo seleccionado si se recarga la página.
        $productoBuscadoNombre = "";
        if ($productoBuscadoId !== null) {
            // Caso normal: el usuario eligió una sugerencia de la lista (o
            // escribió el nombre exacto), así que ya se sabe el id real.
            $productos = array_values(array_filter($productos, function ($p) use ($productoBuscadoId) {
                return (int) $p["id"] === $productoBuscadoId;
            }));
            if (count($productos) > 0) {
                $productoBuscadoNombre = $productos[0]["nombre"];
            }
        } elseif ($productoBuscadoQuery !== "") {
            // Caso "no eligió nada de la lista": se busca por coincidencia
            // de nombre (sin importar mayúsculas/acentos, igual que hace el
            // autocompletar en el navegador) directamente contra los
            // productos ya cargados. Si no encuentra nada, $productos queda
            // vacío y la vista muestra el aviso de "no encontrado".
            $queryNormalizada = $this->normalizarBusquedaProducto($productoBuscadoQuery);
            $productos = array_values(array_filter($productos, function ($p) use ($queryNormalizada) {
                return strpos($this->normalizarBusquedaProducto((string) $p["nombre"]), $queryNormalizada) !== false;
            }));
            // Se conserva tal cual lo que escribió, tanto si encontró algo
            // (para que el campo no se vacíe) como si no (para poder
            // mostrar "No se encontró ningún producto con '...'").
            $productoBuscadoNombre = $productoBuscadoQuery;
        }

        // --- Paginación de la tabla de productos --------------------------
        // Se renumera aquí (después de aplicar la búsqueda) para que la
        // columna "ID" muestre una numeración continua 1..N sobre el
        // resultado ya filtrado, sin importar en qué página esté.
        $numeroFila = 1;
        foreach ($productos as &$p) {
            $p["numero_fila"] = $numeroFila;
            $numeroFila++;
        }
        unset($p);

        $pagProductos = $this->paginar($productos, 25, "pageProductos");
        $productosPagina = $pagProductos["items"];
        $paginaProductos = $pagProductos["paginaActual"];
        $totalPaginasProductos = $pagProductos["totalPaginas"];

        // URL base con los filtros de productos activos (búsqueda + fecha
        // de corte), para que "Anterior"/"Siguiente" no los pierda al
        // cambiar de página.
        $filtrosProductosUrl = "index.php?page=InventarioController&action=index";
        if ($productoBuscadoId !== null) {
            $filtrosProductosUrl .= "&producto_id=" . $productoBuscadoId;
        } elseif ($productoBuscadoQuery !== "") {
            $filtrosProductosUrl .= "&q=" . urlencode($productoBuscadoQuery);
        }
        if ($fechaCorte !== null) {
            $filtrosProductosUrl .= "&fecha_corte=" . urlencode($fechaCorte);
        }
        $urlPaginaAnteriorProductos = $paginaProductos > 1 ? $filtrosProductosUrl . "&pageProductos=" . ($paginaProductos - 1) . "#tabla-productos" : "";
        $urlPaginaSiguienteProductos = $paginaProductos < $totalPaginasProductos ? $filtrosProductosUrl . "&pageProductos=" . ($paginaProductos + 1) . "#tabla-productos" : "";

        // --- Filtros de "Movimientos recientes" (fecha + producto) -------
        $movFechaInicio = Validators::sanitizeDate($_GET["mov_fecha_inicio"] ?? "");
        $movFechaFin = Validators::sanitizeDate($_GET["mov_fecha_fin"] ?? "");
        if ($movFechaInicio !== null && $movFechaFin !== null && $movFechaInicio > $movFechaFin) {
            $tmp = $movFechaInicio;
            $movFechaInicio = $movFechaFin;
            $movFechaFin = $tmp;
        }

        // Igual que arriba: si no se eligió una sugerencia de la lista, se
        // intenta resolver el texto escrito contra el catálogo COMPLETO de
        // productos (no el ya filtrado de la tabla de arriba, que puede
        // estar recortado por su propia búsqueda). Si no coincide con nada,
        // se avisa en vez de mostrar el historial sin filtrar como si nada.
        $movProductoBuscadoNombre = "";
        $movBusquedaSinResultados = false;
        if ($movProductoBuscadoId !== null) {
            foreach ($productosParaBuscador as $p) {
                if ($p["id"] === $movProductoBuscadoId) {
                    $movProductoBuscadoNombre = $p["nombre"];
                    break;
                }
            }
        } elseif ($movProductoBuscadoQuery !== "") {
            $queryNormalizada = $this->normalizarBusquedaProducto($movProductoBuscadoQuery);
            foreach ($productosParaBuscador as $p) {
                if (strpos($this->normalizarBusquedaProducto($p["nombre"]), $queryNormalizada) !== false) {
                    $movProductoBuscadoId = $p["id"];
                    $movProductoBuscadoNombre = $p["nombre"];
                    break;
                }
            }
            if ($movProductoBuscadoId === null) {
                $movBusquedaSinResultados = true;
                $movProductoBuscadoNombre = $movProductoBuscadoQuery;
            }
        }

        $hayBusquedaMovProducto = $movProductoBuscadoId !== null || $movProductoBuscadoQuery !== "";
        $filtroMovimientosActivo = $movFechaInicio !== null || $movFechaFin !== null || $hayBusquedaMovProducto;

        // "Movimientos recientes": antes este bloque leía solo
        // DaoAjusteInventario::getRecientes() (ajustes manuales), y por lo
        // tanto no mostraba las entradas por compra. Se cambió a la fuente
        // unificada (Dao\MovimientoInventario) para que esta vista cuente
        // la misma historia que la pantalla de Kárdex y no confunda al
        // usuario mostrando información incompleta.
        //
        // Sin ningún filtro: los últimos 10, como siempre (vista rápida).
        // Con cualquier filtro activo (fecha y/o producto): se trae TODO el
        // historial que cumple el filtro y se pagina abajo, igual que en el
        // Kárdex, en vez del límite fijo de 25 que había antes.
        if ($movBusquedaSinResultados) {
            // El texto buscado no coincide con ningún producto: no tiene
            // sentido ni consultar la base de datos, ya se sabe que no
            // habrá resultados.
            $movimientosRecientes = [];
        } elseif ($filtroMovimientosActivo) {
            $movimientosRecientes = DaoMovimientoInventario::getMovimientos($movProductoBuscadoId, $movFechaInicio, $movFechaFin, 'DESC');
        } else {
            $movimientosRecientes = DaoMovimientoInventario::getRecientes(10);
        }

        foreach ($movimientosRecientes as &$mov) {
            $mov["es_salida"] = $mov["tipo_movimiento"] === "SALIDA";
            $mov["es_compra"] = $mov["origen"] === "COMPRA";
        }
        unset($mov);

        // --- Paginación de "Movimientos recientes" ------------------------
        $pagMovimientos = $this->paginar($movimientosRecientes, 25, "pageMovimientos");
        $movimientosRecientesPagina = $pagMovimientos["items"];
        $paginaMovimientos = $pagMovimientos["paginaActual"];
        $totalPaginasMovimientos = $pagMovimientos["totalPaginas"];

        // URL base con los filtros de movimientos activos (búsqueda + rango
        // de fechas), para que "Anterior"/"Siguiente" no los pierda.
        $filtrosMovimientosUrl = "index.php?page=InventarioController&action=index";
        if ($movProductoBuscadoId !== null) {
            $filtrosMovimientosUrl .= "&mov_producto_id=" . $movProductoBuscadoId;
        } elseif ($movProductoBuscadoQuery !== "") {
            $filtrosMovimientosUrl .= "&mov_q=" . urlencode($movProductoBuscadoQuery);
        }
        if ($movFechaInicio !== null) {
            $filtrosMovimientosUrl .= "&mov_fecha_inicio=" . urlencode($movFechaInicio);
        }
        if ($movFechaFin !== null) {
            $filtrosMovimientosUrl .= "&mov_fecha_fin=" . urlencode($movFechaFin);
        }
        $urlPaginaAnteriorMovimientos = $paginaMovimientos > 1 ? $filtrosMovimientosUrl . "&pageMovimientos=" . ($paginaMovimientos - 1) . "#movimientos-recientes" : "";
        $urlPaginaSiguienteMovimientos = $paginaMovimientos < $totalPaginasMovimientos ? $filtrosMovimientosUrl . "&pageMovimientos=" . ($paginaMovimientos + 1) . "#movimientos-recientes" : "";

        Renderer::render("inventario", [
            "errorEliminarProducto" => $errorEliminarProducto,
            "productos" => $productosPagina,
            "movimientosRecientes" => $movimientosRecientesPagina,
            "movFechaInicio" => $movFechaInicio ?? "",
            "movFechaFin" => $movFechaFin ?? "",
            // Inventario histórico
            "fechaCorte" => $fechaCorte ?? "",
            "modoHistorico" => $saldosHistoricos !== null,
            // Barra de búsqueda de producto (tabla de productos)
            "productosJsonAttr" => $productosJsonAttr,
            "productoBuscadoIdValue" => $productoBuscadoId !== null ? (string) $productoBuscadoId : "",
            "productoBuscadoNombre" => $productoBuscadoNombre,
            "hayBusquedaProducto" => $productoBuscadoId !== null || $productoBuscadoQuery !== "",
            // Paginación de la tabla de productos
            "paginaProductos" => $paginaProductos,
            "totalPaginasProductos" => $totalPaginasProductos,
            "urlPaginaAnteriorProductos" => $urlPaginaAnteriorProductos,
            "urlPaginaSiguienteProductos" => $urlPaginaSiguienteProductos,
            // Barra de búsqueda de producto (Movimientos recientes)
            "productosJsonAttrMov" => $productosJsonAttr,
            "movProductoBuscadoIdValue" => $movProductoBuscadoId !== null ? (string) $movProductoBuscadoId : "",
            "movProductoBuscadoNombre" => $movProductoBuscadoNombre,
            "hayBusquedaMovProducto" => $hayBusquedaMovProducto,
            "movBusquedaSinResultados" => $movBusquedaSinResultados,
            // Paginación de "Movimientos recientes"
            "paginaMovimientos" => $paginaMovimientos,
            "totalPaginasMovimientos" => $totalPaginasMovimientos,
            "urlPaginaAnteriorMovimientos" => $urlPaginaAnteriorMovimientos,
            "urlPaginaSiguienteMovimientos" => $urlPaginaSiguienteMovimientos
        ]);
    }

    /**
     * Quita acentos y pasa a minúsculas, para poder comparar nombres de
     * producto "a lo bruto" (mismo criterio que usa kardex-autocomplete.js
     * en el navegador con normalize("NFD"), pero del lado del servidor).
     *
     * OJO: a propósito NO usa mb_strtolower/mb_strpos. La extensión
     * mbstring no está instalada en el Dockerfile de este proyecto (ver
     * Utilities\AuditLogger, que por eso mismo valida function_exists antes
     * de usar mb_substr), así que usarla aquí sin más habría tirado un
     * error fatal "Call to undefined function". En su lugar, primero se
     * reemplazan las vocales acentuadas (mayúsculas y minúsculas) por su
     * versión sin acento con strtr — que compara secuencias de bytes
     * completas, así que funciona bien con UTF-8 aunque no sea "mb-aware" —
     * y luego strtolower() ya solo tiene que lidiar con letras ASCII.
     */
    private function normalizarBusquedaProducto(string $texto): string
    {
        $texto = trim($texto);
        $sinAcentos = [
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'
        ];
        return strtolower(strtr($texto, $sinAcentos));
    }

    /**
     * Convierte productos o centros al formato {id, nombre} que necesita la
     * barra de búsqueda con autocompletar (kardex-autocomplete.js). Se usa
     * tanto en index() como en kardex() para no repetir el mismo array_map
     * en los dos lugares.
     */
    private function mapearParaBuscador(array $items): array
    {
        return array_map(function ($item) {
            return ["id" => (int) $item["id"], "nombre" => (string) $item["nombre"]];
        }, $items);
    }

    /**
     * Convierte una lista ya mapeada con mapearParaBuscador() a un texto
     * JSON listo para meterse como atributo HTML (data-options), escapado
     * porque el motor de plantillas de este proyecto no escapa lo que
     * imprime automáticamente.
     */
    private function jsonAttrParaAutocompletar(array $opciones): string
    {
        return htmlspecialchars(json_encode($opciones, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Pagina cualquier lista ya cargada en memoria: calcula el total de
     * páginas, sanea el número de página actual (por si viene fuera de
     * rango en la URL) y recorta el arreglo a la página pedida. $nombreParam
     * es el nombre del parámetro GET a leer (distinto en cada tabla de la
     * pantalla: pageProductos, pageMovimientos, pageNum) para poder paginar
     * varias tablas independientes en la misma página.
     */
    private function paginar(array $items, int $porPagina, string $nombreParam): array
    {
        $total = count($items);
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $paginaActual = Validators::sanitizeInt($_GET[$nombreParam] ?? 1, 1, $totalPaginas) ?? 1;
        $offset = ($paginaActual - 1) * $porPagina;
        return [
            "items" => array_slice($items, $offset, $porPagina),
            "paginaActual" => $paginaActual,
            "totalPaginas" => $totalPaginas
        ];
    }

    private function create(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("inventario_create", ["unidades" => $this->buildUnidadesMedida("Unidad"), "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."]);
                return;
            }

            $nombre = Validators::sanitizeString($_POST["nombre"] ?? "");
            $descripcion = Validators::sanitizeString($_POST["descripcion"] ?? "");
            $unidadMedida = Validators::sanitizeString($_POST["unidad_medida"] ?? "Unidad");
            $unidadesPorCaja = Validators::sanitizeInt($_POST["unidades_por_caja"] ?? 1, 1);
            $stockMinimo = Validators::sanitizeInt($_POST["stock_minimo"] ?? 0, 0);
            $precioUnitario = Validators::sanitizeFloat($_POST["precio_unitario"] ?? 0, 0);

            if ($nombre === "" || $unidadesPorCaja === null || $stockMinimo === null || $precioUnitario === null) {
                Renderer::render("inventario_create", ["unidades" => $this->buildUnidadesMedida($unidadMedida === "" ? "Unidad" : $unidadMedida), "error" => "Todos los campos obligatorios deben ser válidos."]);
                return;
            }

            $newId = DaoProducto::insert($nombre, $descripcion, $unidadMedida === "" ? "Unidad" : $unidadMedida, $unidadesPorCaja, $stockMinimo, $precioUnitario);
            AuditLogger::log('crear', 'Inventario', 'Producto creado: ' . $nombre, ['producto_id' => $newId]);

            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        Renderer::render("inventario_create", ["unidades" => $this->buildUnidadesMedida("Unidad")]);
    }

    private function edit(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        if ($id === null) {
            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                $producto = DaoProducto::getById($id);
                Renderer::render("inventario_edit", ["producto" => $producto, "unidades" => $this->buildUnidadesMedida($producto["unidad_medida"] ?? "Unidad"), "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."]);
                return;
            }

            $nombre = Validators::sanitizeString($_POST["nombre"] ?? "");
            $descripcion = Validators::sanitizeString($_POST["descripcion"] ?? "");
            $unidadMedida = Validators::sanitizeString($_POST["unidad_medida"] ?? "Unidad");
            $unidadesPorCaja = Validators::sanitizeInt($_POST["unidades_por_caja"] ?? 1, 1);
            $stockMinimo = Validators::sanitizeInt($_POST["stock_minimo"] ?? 0, 0);
            $precioUnitario = Validators::sanitizeFloat($_POST["precio_unitario"] ?? 0, 0);

            if ($nombre === "" || $unidadesPorCaja === null || $stockMinimo === null || $precioUnitario === null) {
                $producto = DaoProducto::getById($id);
                Renderer::render("inventario_edit", ["producto" => $producto, "unidades" => $this->buildUnidadesMedida($unidadMedida === "" ? "Unidad" : $unidadMedida), "error" => "Todos los campos obligatorios deben ser válidos."]);
                return;
            }

            DaoProducto::update($id, $nombre, $descripcion, $unidadMedida === "" ? "Unidad" : $unidadMedida, $unidadesPorCaja, $stockMinimo, $precioUnitario);
            AuditLogger::log('editar', 'Inventario', 'Producto actualizado: ' . $nombre, ['producto_id' => $id]);

            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        $producto = DaoProducto::getById($id);
        if (!$producto) {
            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }
        Renderer::render("inventario_edit", ["producto" => $producto, "unidades" => $this->buildUnidadesMedida($producto["unidad_medida"] ?? "Unidad")]);
    }

    private function delete(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $producto = DaoProducto::getById($id);
            DaoProducto::disable($id);
            AuditLogger::log('eliminar', 'Inventario', 'Producto desactivado: ' . ($producto['nombre'] ?? ''), ['producto_id' => $id]);
        }

        Site::redirectTo("index.php?page=InventarioController&action=index");
        exit;
    }

    /**
     * Reactiva un producto que se había desactivado con delete(). No
     * requiere confirmación aparte porque no borra nada, solo revierte el
     * estado a ACT (vuelve a aparecer en los combos de compras/ajustes).
     */
    private function activar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $producto = DaoProducto::getById($id);
            DaoProducto::enable($id);
            AuditLogger::log('activar', 'Inventario', 'Producto reactivado: ' . ($producto['nombre'] ?? ''), ['producto_id' => $id]);
        }

        Site::redirectTo("index.php?page=InventarioController&action=index");
        exit;
    }

    /**
     * Borrado DEFINITIVO de un producto (a diferencia de delete(), que solo
     * lo desactiva). Johnny pidió explícitamente esta opción sabiendo que:
     *  - si el producto tenía ajustes manuales, esos movimientos del
     *    Kárdex se pierden con él (la base los borra en cascada);
     *  - si el producto tiene compras registradas, la base de datos
     *    RECHAZA el borrado (para no perder el historial de una compra
     *    real) — ese caso se captura aquí para mostrar un aviso claro en
     *    vez de una pantalla de error en blanco.
     */
    private function eliminar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $producto = DaoProducto::getById($id);
            $nombreProducto = $producto ? (string) $producto['nombre'] : '';

            try {
                DaoProducto::delete($id);
                AuditLogger::log('eliminar', 'Inventario', 'Producto eliminado definitivamente: ' . $nombreProducto, ['producto_id' => $id]);
            } catch (\PDOException $ex) {
                // SQLSTATE 23000 = violación de llave foránea. Es el caso
                // esperado cuando el producto tiene compras registradas
                // (factura_compra_detalle usa ON DELETE RESTRICT a
                // propósito). Se avisa al usuario en vez de dejar que la
                // excepción reviente la página con un error 500 en blanco.
                AuditLogger::log('error', 'Inventario', 'No se pudo eliminar (tiene compras registradas): ' . $nombreProducto, ['producto_id' => $id]);
                Site::redirectTo("index.php?page=InventarioController&action=index&errorEliminar=" . urlencode($nombreProducto));
                exit;
            }
        }

        Site::redirectTo("index.php?page=InventarioController&action=index");
        exit;
    }

    private function ajustar(): void
    {
        $defaults = [
            "producto_id" => 0,
            "centro_salud_id" => 0,
            "tipo_ajuste" => "ENTRADA",
            "cantidad" => "",
            "motivo" => ""
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $productoId = Validators::sanitizeId($_POST["producto_id"] ?? 0);
            $centroSaludId = Validators::sanitizeId(
                $_POST["centro_salud_id"] ?? 0
            );
            $tipoAjuste = strval($_POST["tipo_ajuste"] ?? "");
            $cantidad = Validators::sanitizeInt($_POST["cantidad"] ?? 0, 1);
            $motivo = Validators::sanitizeString($_POST["motivo"] ?? "");

            $defaults = [
                "producto_id" => $productoId ?? 0,
                "centro_salud_id" => $centroSaludId ?? 0,
                "tipo_ajuste" => in_array(
                    $tipoAjuste,
                    ["ENTRADA", "SALIDA"],
                    true
                ) ? $tipoAjuste : "ENTRADA",
                "cantidad" => $cantidad ?? "",
                "motivo" => $motivo
            ];

            if (!Security::validateCsrfPost()) {
                $this->renderAjuste(
                    $defaults,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                );
                return;
            }

            $producto = $productoId !== null ? DaoProducto::getById($productoId) : null;
            $centro = $centroSaludId !== null
                ? DaoCentroSalud::getById($centroSaludId)
                : null;

            if (
                $productoId === null
                || !$producto
                || ($producto["estado"] ?? "") !== "ACT"
                || $centroSaludId === null
                || !$centro
                || ($centro["estado"] ?? "") !== "ACT"
                || !in_array($tipoAjuste, ["ENTRADA", "SALIDA"], true)
                || $cantidad === null
                || $motivo === ""
            ) {
                $this->renderAjuste(
                    $defaults,
                    "Todos los campos son obligatorios, el producto y centro deben estar activos, y la cantidad debe ser mayor a cero."
                );
                return;
            }

            if ($tipoAjuste === "SALIDA" && $cantidad > intval($producto["stock_actual"])) {
                $this->renderAjuste(
                    $defaults,
                    "No hay suficiente stock disponible para registrar esta salida."
                );
                return;
            }

            try {
                $adjustmentId =
                    DaoAjusteInventario::registerWithStockChange(
                        $productoId,
                        $centroSaludId,
                        $tipoAjuste,
                        $cantidad,
                        $motivo,
                        Security::getUserId()
                    );
            } catch (\DomainException $error) {
                $this->renderAjuste($defaults, $error->getMessage());
                return;
            } catch (\Throwable $error) {
                error_log(
                    "No se pudo registrar el ajuste de inventario: "
                    . $error->getMessage()
                );
                $this->renderAjuste(
                    $defaults,
                    "No fue posible registrar el ajuste. Intente nuevamente."
                );
                return;
            }

            AuditLogger::log(
                "ajustar",
                "Inventario",
                "Ajuste de stock ($tipoAjuste) sobre "
                    . $producto["nombre"]
                    . " en "
                    . $centro["nombre"]
                    . ": $cantidad",
                [
                    "ajuste_id" => $adjustmentId,
                    "producto_id" => $productoId,
                    "centro_salud_id" => $centroSaludId
                ]
            );

            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        $this->renderAjuste($defaults);
    }

    private function renderAjuste(
        array $values,
        string $error = ""
    ): void {
        $productoId = intval($values["producto_id"] ?? 0);
        $centroSaludId = intval($values["centro_salud_id"] ?? 0);
        $productos = DaoProducto::getActivos();
        $centros = DaoCentroSalud::getActivos();

        foreach ($productos as &$producto) {
            $producto["selected"] = intval($producto["id"]) === $productoId;
        }
        unset($producto);

        foreach ($centros as &$centro) {
            $centro["selected"] = intval($centro["id"]) === $centroSaludId;
        }
        unset($centro);

        Renderer::render("inventario_ajustar", [
            "productos" => $productos,
            "centros" => $centros,
            "tipoEntrada" =>
                ($values["tipo_ajuste"] ?? "ENTRADA") === "ENTRADA",
            "tipoSalida" =>
                ($values["tipo_ajuste"] ?? "") === "SALIDA",
            "cantidad" => $values["cantidad"] ?? "",
            "motivo" => $values["motivo"] ?? "",
            "sinProductos" => count($productos) === 0,
            "sinCentros" => count($centros) === 0,
            "puedeGuardar" =>
                count($productos) > 0 && count($centros) > 0,
            "error" => $error
        ]);
    }

    /**
     * Pantalla de Kárdex: historial de entradas y salidas de inventario,
     * con saldo acumulado, combinando ajustes manuales y compras a
     * proveedor (ver Dao\MovimientoInventario para el detalle de por qué
     * hace falta unir ambas fuentes).
     *
     * Filtros disponibles por GET (todos opcionales, se puede acceder sin
     * ninguno y se muestra el historial completo de todos los productos):
     *   - producto_id     : filtra el kárdex a un solo producto
     *   - centro_salud_id : filtra a los movimientos ocurridos en un centro
     *                       (las compras, que aún son globales, se ocultan
     *                       de la vista cuando este filtro está activo)
     *   - fecha_inicio    : 'YYYY-MM-DD', límite inferior (inclusive)
     *   - fecha_fin       : 'YYYY-MM-DD', límite superior (inclusive)
     */
    private function kardex(): void
    {
        // Los filtros viajan en la URL (GET), no hay POST aquí porque esta
        // pantalla solo consulta, no modifica nada. Por eso no requiere
        // token CSRF (el CSRF solo protege acciones que cambian datos).
        $productoId = Validators::sanitizeId($_GET["producto_id"] ?? "");
        $centroSaludId = Validators::sanitizeId($_GET["centro_salud_id"] ?? "");
        $fechaInicio = Validators::sanitizeDate($_GET["fecha_inicio"] ?? "");
        $fechaFin = Validators::sanitizeDate($_GET["fecha_fin"] ?? "");

        // Si el usuario invirtió el rango (p.ej. "Desde" 2026-08-01 y "Hasta"
        // 2026-01-01), se corrige solo en vez de devolver una tabla vacía
        // sin explicación. Se avisa en pantalla que se hizo el ajuste.
        $fechasInvertidas = false;
        if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio > $fechaFin) {
            $tmp = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin = $tmp;
            $fechasInvertidas = true;
        }

        // Lista de productos para el <select> del filtro. Se usa getAll()
        // (no getActivos()) a propósito: un producto desactivado puede
        // seguir teniendo movimientos históricos que alguien necesite
        // consultar, así que no debe desaparecer del filtro por eso.
        $productos = array_map(function ($p) use ($productoId) {
            $p["selected"] = $productoId !== null && (int) $p["id"] === $productoId;
            return $p;
        }, DaoProducto::getAll());

        // Igual para centros de salud: se usa getAll() para no esconder del
        // filtro un centro desactivado que aún tenga historial.
        $centros = array_map(function ($c) use ($centroSaludId) {
            $c["selected"] = $centroSaludId !== null && (int) $c["id"] === $centroSaludId;
            return $c;
        }, DaoCentroSalud::getAll());

        // Registra el script de la barra de búsqueda con autocompletar.
        // Solo esta pantalla lo necesita, así que se agrega aquí y no en el
        // layout general (que cargaría el archivo en TODAS las páginas).
        Site::addEndScript('public/js/kardex-autocomplete.js');

        // Los <input> de búsqueda necesitan la lista completa de opciones
        // en el navegador (para filtrar mientras se escribe, sin ir al
        // servidor). Se manda como JSON dentro de un atributo data-options,
        // por eso se escapa con htmlspecialchars: el motor de plantillas de
        // este proyecto no escapa automáticamente lo que imprime, y sin
        // este escape las comillas del JSON romperían el HTML del atributo.
        $productosParaBuscador = $this->mapearParaBuscador($productos);
        $centrosParaBuscador = $this->mapearParaBuscador($centros);
        $productosJsonAttr = $this->jsonAttrParaAutocompletar($productosParaBuscador);
        $centrosJsonAttr = $this->jsonAttrParaAutocompletar($centrosParaBuscador);

        // Para que el campo de texto muestre el nombre ya seleccionado al
        // recargar la página (por ejemplo, al volver de "Filtrar").
        $productoNombreSeleccionado = "";
        foreach ($productos as $p) {
            if (!empty($p["selected"])) {
                $productoNombreSeleccionado = $p["nombre"];
                break;
            }
        }
        $centroNombreSeleccionado = "";
        foreach ($centros as $c) {
            if (!empty($c["selected"])) {
                $centroNombreSeleccionado = $c["nombre"];
                break;
            }
        }

        // Historial a mostrar en pantalla, ya recortado según los filtros
        // de fecha y de centro que haya puesto el usuario. El saldo
        // acumulado, sin embargo, siempre se calcula sobre el historial
        // GLOBAL (ver el comentario del parámetro $centroSaludId en
        // Dao\MovimientoInventario::getMovimientosConSaldo): el stock del
        // producto todavía no está partido por centro.
        $movimientos = DaoMovimientoInventario::getMovimientosConSaldo($productoId, $fechaInicio, $fechaFin, $centroSaludId);

        // Se arma un pequeño resumen (total entradas / total salidas del
        // rango filtrado) para que la pantalla no sea solo una tabla larga.
        $totalEntradas = 0;
        $totalSalidas = 0;
        $numeroFila = 1;
        foreach ($movimientos as &$mov) {
            // El motor de plantillas del proyecto solo sabe evaluar "verdadero/falso"
            // (no comparar texto), así que se calculan aquí las banderas que la
            // vista necesita para pintar entradas en verde y salidas en rojo,
            // y para distinguir visualmente si el movimiento vino de un ajuste
            // manual o de una compra a proveedor.
            $mov["es_salida"] = $mov["tipo_movimiento"] === "SALIDA";
            $mov["es_compra"] = $mov["origen"] === "COMPRA";

            if ($mov["es_salida"]) {
                $totalSalidas += (int) $mov["cantidad"];
            } else {
                $totalEntradas += (int) $mov["cantidad"];
            }
            $mov["numero_fila"] = $numeroFila;
            $numeroFila++;
        }
        unset($mov);

        // Exportación a CSV: se ofrece TODO el historial que cumple los
        // filtros (no solo la página que se está viendo en pantalla), que
        // es lo que espera alguien que va a abrir el archivo en Excel o
        // llevarlo a una auditoría.
        if (($_GET["export"] ?? "") === "csv") {
            $this->exportKardexCsv($movimientos);
            return;
        }

        // --- Paginación -----------------------------------------------------
        // Sin esto, un año de operación con cientos de movimientos volvería
        // esta pantalla lentísima y la tabla sería imposible de recorrer.
        // Los totales de arriba (Movimientos / Entradas / Salidas) se
        // calculan sobre el TOTAL filtrado, no solo la página visible.
        $totalMovimientosFiltrados = count($movimientos);
        $pagKardex = $this->paginar($movimientos, 25, "pageNum");
        $movimientosPagina = $pagKardex["items"];
        $paginaActual = $pagKardex["paginaActual"];
        $totalPaginas = $pagKardex["totalPaginas"];

        // Se arma la URL base con los filtros actuales para que "Anterior"
        // y "Siguiente" no pierdan lo que el usuario ya había filtrado.
        $filtrosUrl = "index.php?page=InventarioController&action=kardex";
        if ($productoId !== null) {
            $filtrosUrl .= "&producto_id=" . $productoId;
        }
        if ($centroSaludId !== null) {
            $filtrosUrl .= "&centro_salud_id=" . $centroSaludId;
        }
        if ($fechaInicio !== null) {
            $filtrosUrl .= "&fecha_inicio=" . urlencode($fechaInicio);
        }
        if ($fechaFin !== null) {
            $filtrosUrl .= "&fecha_fin=" . urlencode($fechaFin);
        }
        $urlPaginaAnterior = $paginaActual > 1 ? $filtrosUrl . "&pageNum=" . ($paginaActual - 1) : "";
        $urlPaginaSiguiente = $paginaActual < $totalPaginas ? $filtrosUrl . "&pageNum=" . ($paginaActual + 1) : "";
        $urlExportarCsv = $filtrosUrl . "&export=csv";

        // --- Verificación de integridad (comprobación del "hueco" cerrado) ---
        // Cuando se filtra por UN producto específico, se puede comprobar
        // que la unión de ajustes + compras realmente cuadra con el stock
        // real guardado en la tabla producto. Para esto NO se debe usar el
        // saldo del historial ya filtrado por fecha (ese es solo "hasta
        // cierta fecha"), sino el saldo con el historial COMPLETO del
        // producto, que debe coincidir con producto.stock_actual en este
        // mismo instante.
        $productoSeleccionado = null;
        $saldoCuadra = null;
        if ($productoId !== null) {
            // OJO: DaoProducto::getById() devuelve `false` (no `null`) cuando
            // no encuentra el producto, porque así funciona PDOStatement::fetch()
            // internamente (ver Dao\Table::obtenerUnRegistro). Por eso se valida
            // con "!$productoEncontrado" (falsy) y no con "!== null", y se deja
            // $productoSeleccionado explícitamente en null si no existe, para que
            // la vista (que sí compara contra null/estar-seteado) se comporte igual
            // tanto si no se filtró por producto como si se filtró por un id que
            // ya no existe.
            $productoEncontrado = DaoProducto::getById($productoId);
            if ($productoEncontrado) {
                $historialCompleto = DaoMovimientoInventario::getMovimientosConSaldo($productoId, null, null);
                $saldoCalculado = count($historialCompleto) > 0
                    ? (int) end($historialCompleto)["saldo_acumulado"]
                    : 0;
                $saldoCuadra = $saldoCalculado === (int) $productoEncontrado["stock_actual"];
                $productoEncontrado["saldo_calculado"] = $saldoCalculado;
                $productoSeleccionado = $productoEncontrado;
            }
        }

        Renderer::render("inventario_kardex", [
            "productos" => $productos,
            "centros" => $centros,
            "movimientos" => $movimientosPagina,
            "totalMovimientos" => $totalMovimientosFiltrados,
            "totalEntradas" => $totalEntradas,
            "totalSalidas" => $totalSalidas,
            "fechaInicio" => $fechaInicio ?? "",
            "fechaFin" => $fechaFin ?? "",
            "productoSeleccionado" => $productoSeleccionado,
            "saldoCuadra" => $saldoCuadra,
            // Le avisa a la vista que muestre la nota aclarando que, con un
            // centro filtrado, las compras (todavía globales) no aparecen.
            "filtroPorCentroActivo" => $centroSaludId !== null,
            // Avisa que se corrigió un rango de fechas invertido.
            "fechasInvertidas" => $fechasInvertidas,
            // Datos para las barras de búsqueda con autocompletar.
            "productoIdSeleccionadoValue" => $productoId !== null ? (string) $productoId : "",
            "productoNombreSeleccionado" => $productoNombreSeleccionado,
            "centroIdSeleccionadoValue" => $centroSaludId !== null ? (string) $centroSaludId : "",
            "centroNombreSeleccionado" => $centroNombreSeleccionado,
            "productosJsonAttr" => $productosJsonAttr,
            "centrosJsonAttr" => $centrosJsonAttr,
            // Paginación y exportación.
            "paginaActual" => $paginaActual,
            "totalPaginas" => $totalPaginas,
            "urlPaginaAnterior" => $urlPaginaAnterior,
            "urlPaginaSiguiente" => $urlPaginaSiguiente,
            "urlExportarCsv" => $urlExportarCsv
        ]);
    }

    /**
     * Descarga el historial ya filtrado como CSV (mismo patrón que
     * ReportesController::exportCsv). Se manda TODO lo que cumple el
     * filtro, sin paginar, porque es lo que alguien espera al exportar
     * para revisar en Excel o entregar a una auditoría.
     */
    private function exportKardexCsv(array $movimientos): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=smartclinic_kardex.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Fecha',
            'Producto',
            'Centro de salud',
            'Tipo',
            'Origen',
            'Cantidad',
            'Referencia',
            'Registrado por',
            'Saldo acumulado'
        ]);
        foreach ($movimientos as $mov) {
            fputcsv($out, [
                $mov['fecha'] ?? '',
                $mov['producto_nombre'] ?? '',
                $mov['centro_nombre'] ?? '',
                $mov['tipo_movimiento'] ?? '',
                $mov['origen'] ?? '',
                $mov['cantidad'] ?? '',
                $mov['referencia'] ?? '',
                $mov['usuario_nombre'] ?? '',
                $mov['saldo_acumulado'] ?? ''
            ]);
        }
        fclose($out);
    }
}
