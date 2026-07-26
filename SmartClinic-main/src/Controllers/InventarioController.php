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
        $productos = DaoProducto::getAll();
        $numeroFila = 1;
        foreach ($productos as &$producto) {
            $producto["stock_bajo"] = intval($producto["stock_actual"]) < intval($producto["stock_minimo"]);
            $producto["numero_fila"] = $numeroFila;
            $numeroFila++;
        }
        unset($producto);

        // "Movimientos recientes": antes este bloque leía solo
        // DaoAjusteInventario::getRecientes() (ajustes manuales), y por lo
        // tanto no mostraba las entradas por compra. Se cambió a la fuente
        // unificada (Dao\MovimientoInventario) para que esta vista cuente
        // la misma historia que la pantalla de Kárdex y no confunda al
        // usuario mostrando información incompleta.
        $movimientosRecientes = DaoMovimientoInventario::getRecientes(10);
        foreach ($movimientosRecientes as &$mov) {
            $mov["es_salida"] = $mov["tipo_movimiento"] === "SALIDA";
            $mov["es_compra"] = $mov["origen"] === "COMPRA";
        }
        unset($mov);

        Renderer::render("inventario", [
            "productos" => $productos,
            "movimientosRecientes" => $movimientosRecientes
        ]);
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
     *   - producto_id : filtra el kárdex a un solo producto
     *   - fecha_inicio: 'YYYY-MM-DD', límite inferior (inclusive)
     *   - fecha_fin   : 'YYYY-MM-DD', límite superior (inclusive)
     */
    private function kardex(): void
    {
        // Los filtros viajan en la URL (GET), no hay POST aquí porque esta
        // pantalla solo consulta, no modifica nada. Por eso no requiere
        // token CSRF (el CSRF solo protege acciones que cambian datos).
        $productoId = Validators::sanitizeId($_GET["producto_id"] ?? "");
        $fechaInicio = Validators::sanitizeDate($_GET["fecha_inicio"] ?? "");
        $fechaFin = Validators::sanitizeDate($_GET["fecha_fin"] ?? "");

        // Lista de productos para el <select> del filtro. Se usa getAll()
        // (no getActivos()) a propósito: un producto desactivado puede
        // seguir teniendo movimientos históricos que alguien necesite
        // consultar, así que no debe desaparecer del filtro por eso.
        $productos = array_map(function ($p) use ($productoId) {
            $p["selected"] = $productoId !== null && (int) $p["id"] === $productoId;
            return $p;
        }, DaoProducto::getAll());

        // Historial a mostrar en pantalla, ya recortado según los filtros
        // de fecha que haya puesto el usuario.
        $movimientos = DaoMovimientoInventario::getMovimientosConSaldo($productoId, $fechaInicio, $fechaFin);

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
            "movimientos" => $movimientos,
            "totalMovimientos" => count($movimientos),
            "totalEntradas" => $totalEntradas,
            "totalSalidas" => $totalSalidas,
            "fechaInicio" => $fechaInicio ?? "",
            "fechaFin" => $fechaFin ?? "",
            "productoSeleccionado" => $productoSeleccionado,
            "saldoCuadra" => $saldoCuadra
        ]);
    }
}
