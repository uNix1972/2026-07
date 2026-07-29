<?php
namespace Controllers;

use Views\Renderer;
use Dao\FacturaCompra as DaoFacturaCompra;
use Dao\Proveedor as DaoProveedor;
use Dao\Producto as DaoProducto;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Utilities\AuditLogger;

class ComprasController extends PrivateController
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

            case "view":
                $this->view();
                break;

            case "edit":
                $this->edit();
                break;

            case "proveedores":
                $this->proveedores();
                break;

            case "proveedor_edit":
                $this->proveedorEdit();
                break;

            case "proveedor_desactivar":
                $this->proveedorDesactivar();
                break;

            case "proveedor_activar":
                $this->proveedorActivar();
                break;

            case "proveedor_eliminar":
                $this->proveedorEliminar();
                break;

            default:
                $this->index();
                break;
        }
    }

    private function index(): void
    {
        $facturas = DaoFacturaCompra::getAll();
        $numeroFila = 1;
        foreach ($facturas as &$factura) {
            $factura["numero_fila"] = $numeroFila;
            $numeroFila++;
        }
        unset($factura);

        Renderer::render("compras", ["facturas" => $facturas]);
    }

    private function buildUnidadesPorCajaMap(): array
    {
        $map = [];
        foreach (DaoProducto::getActivos() as $p) {
            $map[(int) $p["id"]] = max(1, (int) $p["unidades_por_caja"]);
        }
        return $map;
    }

    private function buildLineas(array $productoIds, array $cantidades, array $precios, array $tiposCompra): array
    {
        $lineas = [];
        $unidadesPorCajaMap = $this->buildUnidadesPorCajaMap();

        foreach ($productoIds as $index => $rawProductoId) {
            $productoId = Validators::sanitizeId($rawProductoId);
            $cantidadIngresada = Validators::sanitizeInt($cantidades[$index] ?? 0, 1);
            $precioIngresado = Validators::sanitizeFloat($precios[$index] ?? 0, 0.01);
            $tipoCompra = ($tiposCompra[$index] ?? "UNI") === "CAJ" ? "CAJ" : "UNI";

            if ($productoId === null || $cantidadIngresada === null || $precioIngresado === null) {
                continue;
            }

            $unidadesPorCaja = $unidadesPorCajaMap[$productoId] ?? 1;

            if ($tipoCompra === "CAJ" && $unidadesPorCaja > 1) {
                $lineas[] = [
                    "producto_id" => $productoId,
                    "cantidad" => $cantidadIngresada * $unidadesPorCaja,
                    "precio_unitario" => round($precioIngresado / $unidadesPorCaja, 2),
                    "tipo_compra" => "CAJ",
                    "cantidad_cajas" => $cantidadIngresada
                ];
            } else {
                $lineas[] = [
                    "producto_id" => $productoId,
                    "cantidad" => $cantidadIngresada,
                    "precio_unitario" => $precioIngresado,
                    "tipo_compra" => "UNI",
                    "cantidad_cajas" => null
                ];
            }
        }

        return $lineas;
    }

    private function buildProveedoresOpciones(int $proveedorIdSeleccionado): array
    {
        return array_map(function ($p) use ($proveedorIdSeleccionado) {
            return [
                "id" => $p["id"],
                "nombre" => $p["nombre"],
                "selected" => (int) $p["id"] === $proveedorIdSeleccionado
            ];
        }, DaoProveedor::getActivos());
    }

    private function buildDetalleParaEdicion(array $detalle): array
    {
        $productosActivos = DaoProducto::getActivos();

        foreach ($detalle as &$linea) {
            $unidadesPorCaja = max(1, (int) $linea["unidades_por_caja"]);
            $esCaja = $linea["tipo_compra"] === "CAJ";

            $linea["es_caja"] = $esCaja;
            $linea["cantidad_display"] = $esCaja ? (int) $linea["cantidad_cajas"] : (int) $linea["cantidad"];
            $linea["precio_display"] = $esCaja
                ? round(((float) $linea["precio_unitario"]) * $unidadesPorCaja, 2)
                : (float) $linea["precio_unitario"];

            $productoId = (int) $linea["producto_id"];
            $linea["opciones_producto"] = array_map(function ($p) use ($productoId) {
                return [
                    "id" => $p["id"],
                    "nombre" => $p["nombre"],
                    "unidad_medida" => $p["unidad_medida"],
                    "unidades_por_caja" => $p["unidades_por_caja"],
                    "selected" => (int) $p["id"] === $productoId
                ];
            }, $productosActivos);
        }
        unset($linea);

        return $detalle;
    }

    private function view(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $factura = $id !== null ? DaoFacturaCompra::getById($id) : null;

        if ($factura === null) {
            Site::redirectTo("index.php?page=ComprasController&action=index");
            exit;
        }

        $detalle = DaoFacturaCompra::getDetalleByFactura($id);
        foreach ($detalle as &$linea) {
            $linea["es_caja"] = $linea["tipo_compra"] === "CAJ";
        }
        unset($linea);

        Renderer::render("compra_view", [
            "factura" => $factura,
            "detalle" => $detalle
        ]);
    }

    private function create(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("compra_create", [
                    "proveedores" => DaoProveedor::getActivos(),
                    "productos" => DaoProducto::getActivos(),
                    "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                ]);
                return;
            }

            $proveedorId = Validators::sanitizeId($_POST["proveedor_id"] ?? 0);
            $productoIds = $_POST["producto_id"] ?? [];
            $cantidades = $_POST["cantidad"] ?? [];
            $precios = $_POST["precio_unitario"] ?? [];
            $tiposCompra = $_POST["tipo_compra"] ?? [];

            $lineas = [];
            $error = null;

            if ($proveedorId === null) {
                $error = "Seleccione un proveedor.";
            } elseif (!is_array($productoIds) || count($productoIds) === 0) {
                $error = "Agregue al menos un producto a la compra.";
            } else {
                $lineas = $this->buildLineas($productoIds, $cantidades, $precios, $tiposCompra);

                if (count($lineas) === 0) {
                    $error = "Debe capturar al menos una línea de producto válida (producto, cantidad y precio).";
                }
            }

            if ($error !== null) {
                Renderer::render("compra_create", [
                    "proveedores" => DaoProveedor::getActivos(),
                    "productos" => DaoProducto::getActivos(),
                    "error" => $error
                ]);
                return;
            }

            $resultado = DaoFacturaCompra::insertConDetalle($proveedorId, Security::getUserId(), $lineas);
            AuditLogger::log('crear', 'Compras', 'Factura de compra registrada: ' . $resultado['numero_factura'], ['factura_compra_id' => $resultado['id']]);

            Site::redirectTo("index.php?page=ComprasController&action=view&id=" . $resultado['id']);
            exit;
        }

        Renderer::render("compra_create", [
            "proveedores" => DaoProveedor::getActivos(),
            "productos" => DaoProducto::getActivos()
        ]);
    }

    private function edit(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $factura = $id !== null ? DaoFacturaCompra::getById($id) : null;

        if ($factura === null) {
            Site::redirectTo("index.php?page=ComprasController&action=index");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("compra_edit", [
                    "factura" => $factura,
                    "proveedores" => $this->buildProveedoresOpciones((int) $factura["proveedor_id"]),
                    "detalle" => $this->buildDetalleParaEdicion(DaoFacturaCompra::getDetalleByFactura($id)),
                    "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                ]);
                return;
            }

            $proveedorId = Validators::sanitizeId($_POST["proveedor_id"] ?? 0);
            $productoIds = $_POST["producto_id"] ?? [];
            $cantidades = $_POST["cantidad"] ?? [];
            $precios = $_POST["precio_unitario"] ?? [];
            $tiposCompra = $_POST["tipo_compra"] ?? [];

            $lineas = [];
            $error = null;

            if ($proveedorId === null) {
                $error = "Seleccione un proveedor.";
            } elseif (!is_array($productoIds) || count($productoIds) === 0) {
                $error = "Agregue al menos un producto a la compra.";
            } else {
                $lineas = $this->buildLineas($productoIds, $cantidades, $precios, $tiposCompra);

                if (count($lineas) === 0) {
                    $error = "Debe capturar al menos una línea de producto válida (producto, cantidad y precio).";
                }
            }

            if ($error !== null) {
                Renderer::render("compra_edit", [
                    "factura" => $factura,
                    "proveedores" => $this->buildProveedoresOpciones($proveedorId ?? (int) $factura["proveedor_id"]),
                    "detalle" => $this->buildDetalleParaEdicion(DaoFacturaCompra::getDetalleByFactura($id)),
                    "error" => $error
                ]);
                return;
            }

            DaoFacturaCompra::updateConDetalle($id, $proveedorId, $lineas);
            AuditLogger::log('editar', 'Compras', 'Factura de compra actualizada: ' . $factura['numero_factura'], ['factura_compra_id' => $id]);

            Site::redirectTo("index.php?page=ComprasController&action=view&id=" . $id);
            exit;
        }

        Renderer::render("compra_edit", [
            "factura" => $factura,
            "proveedores" => $this->buildProveedoresOpciones((int) $factura["proveedor_id"]),
            "detalle" => $this->buildDetalleParaEdicion(DaoFacturaCompra::getDetalleByFactura($id))
        ]);
    }

    private function getProveedoresConNumeroFila(): array
    {
        $proveedores = DaoProveedor::getAll();
        $numeroFila = 1;
        foreach ($proveedores as &$proveedor) {
            $proveedor["numero_fila"] = $numeroFila;
            $proveedor["esActivo"] = ($proveedor["estado"] ?? "") === "ACT";
            $numeroFila++;
        }
        unset($proveedor);

        return $proveedores;
    }

    private function proveedores(): void
    {
        // Aviso de "no se pudo eliminar" que llega por GET después de un
        // redirect desde proveedorEliminar() (cuando la base de datos
        // rechaza el borrado por tener facturas de compra registradas).
        $errorEliminarProveedor = trim((string) ($_GET["errorEliminar"] ?? ""));

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("proveedores", ["proveedores" => $this->getProveedoresConNumeroFila(), "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."]);
                return;
            }

            $nombre = Validators::sanitizeString($_POST["nombre"] ?? "");
            $contacto = Validators::sanitizeString($_POST["contacto"] ?? "");
            $telefono = Validators::sanitizeString($_POST["telefono"] ?? "");
            $email = Validators::sanitizeString($_POST["email"] ?? "");
            $direccion = Validators::sanitizeString($_POST["direccion"] ?? "");

            if ($nombre === "") {
                Renderer::render("proveedores", ["proveedores" => $this->getProveedoresConNumeroFila(), "error" => "El nombre del proveedor es obligatorio."]);
                return;
            }

            $newId = DaoProveedor::insert($nombre, $contacto, $telefono, $email, $direccion);
            AuditLogger::log('crear', 'Compras', 'Proveedor creado: ' . $nombre, ['proveedor_id' => $newId]);

            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        Renderer::render("proveedores", [
            "proveedores" => $this->getProveedoresConNumeroFila(),
            "errorEliminarProveedor" => $errorEliminarProveedor
        ]);
    }

    private function proveedorEdit(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? $_POST["id"] ?? 0);
        $proveedor = $id !== null ? DaoProveedor::getById($id) : null;

        if (!$proveedor) {
            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("proveedor_edit", [
                    "proveedor" => $proveedor,
                    "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                ]);
                return;
            }

            $nombre = Validators::sanitizeString($_POST["nombre"] ?? "");
            $contacto = Validators::sanitizeString($_POST["contacto"] ?? "");
            $telefono = Validators::sanitizeString($_POST["telefono"] ?? "");
            $email = Validators::sanitizeString($_POST["email"] ?? "");
            $direccion = Validators::sanitizeString($_POST["direccion"] ?? "");

            if ($nombre === "") {
                Renderer::render("proveedor_edit", [
                    "proveedor" => array_merge($proveedor, [
                        "nombre" => $nombre, "contacto" => $contacto, "telefono" => $telefono,
                        "email" => $email, "direccion" => $direccion
                    ]),
                    "error" => "El nombre del proveedor es obligatorio."
                ]);
                return;
            }

            DaoProveedor::update($id, $nombre, $contacto, $telefono, $email, $direccion);
            AuditLogger::log('editar', 'Compras', 'Proveedor actualizado: ' . $nombre, ['proveedor_id' => $id]);

            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        Renderer::render("proveedor_edit", ["proveedor" => $proveedor]);
    }

    /**
     * Desactiva un proveedor (no lo borra, solo lo oculta del combo de
     * "Registrar compra" que solo lista proveedores ACT). Mismo patrón que
     * Producto::disable() en Inventario.
     */
    private function proveedorDesactivar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $proveedor = DaoProveedor::getById($id);
            DaoProveedor::disable($id);
            AuditLogger::log('eliminar', 'Compras', 'Proveedor desactivado: ' . ($proveedor['nombre'] ?? ''), ['proveedor_id' => $id]);
        }

        Site::redirectTo("index.php?page=ComprasController&action=proveedores");
        exit;
    }

    private function proveedorActivar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $proveedor = DaoProveedor::getById($id);
            DaoProveedor::enable($id);
            AuditLogger::log('activar', 'Compras', 'Proveedor reactivado: ' . ($proveedor['nombre'] ?? ''), ['proveedor_id' => $id]);
        }

        Site::redirectTo("index.php?page=ComprasController&action=proveedores");
        exit;
    }

    /**
     * Borrado DEFINITIVO de un proveedor. La base de datos lo rechaza sola
     * (PDOException, SQLSTATE 23000) si el proveedor tiene facturas de
     * compra registradas, porque factura_compra.proveedor_id usa
     * ON DELETE RESTRICT a propósito. Mismo patrón que
     * InventarioController::eliminar().
     */
    private function proveedorEliminar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $proveedor = DaoProveedor::getById($id);
            $nombreProveedor = $proveedor ? (string) $proveedor['nombre'] : '';

            try {
                DaoProveedor::delete($id);
                AuditLogger::log('eliminar', 'Compras', 'Proveedor eliminado definitivamente: ' . $nombreProveedor, ['proveedor_id' => $id]);
            } catch (\PDOException $ex) {
                AuditLogger::log('error', 'Compras', 'No se pudo eliminar (tiene compras registradas): ' . $nombreProveedor, ['proveedor_id' => $id]);
                Site::redirectTo("index.php?page=ComprasController&action=proveedores&errorEliminar=" . urlencode($nombreProveedor));
                exit;
            }
        }

        Site::redirectTo("index.php?page=ComprasController&action=proveedores");
        exit;
    }
}
