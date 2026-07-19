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

            case "proveedores":
                $this->proveedores();
                break;

            default:
                $this->index();
                break;
        }
    }

    private function index(): void
    {
        Renderer::render("compras", ["facturas" => DaoFacturaCompra::getAll()]);
    }

    private function view(): void
    {
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $factura = $id !== null ? DaoFacturaCompra::getById($id) : null;

        if ($factura === null) {
            Site::redirectTo("index.php?page=ComprasController&action=index");
            exit;
        }

        Renderer::render("compra_view", [
            "factura" => $factura,
            "detalle" => DaoFacturaCompra::getDetalleByFactura($id)
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
            $numeroFactura = Validators::sanitizeString($_POST["numero_factura"] ?? "");
            $productoIds = $_POST["producto_id"] ?? [];
            $cantidades = $_POST["cantidad"] ?? [];
            $precios = $_POST["precio_unitario"] ?? [];

            $lineas = [];
            $error = null;

            if ($proveedorId === null || $numeroFactura === "") {
                $error = "Seleccione un proveedor y capture el número de factura.";
            } elseif (!is_array($productoIds) || count($productoIds) === 0) {
                $error = "Agregue al menos un producto a la compra.";
            } else {
                foreach ($productoIds as $index => $rawProductoId) {
                    $productoId = Validators::sanitizeId($rawProductoId);
                    $cantidad = Validators::sanitizeInt($cantidades[$index] ?? 0, 1);
                    $precioUnitario = Validators::sanitizeFloat($precios[$index] ?? 0, 0.01);

                    if ($productoId === null || $cantidad === null || $precioUnitario === null) {
                        continue;
                    }

                    $lineas[] = [
                        "producto_id" => $productoId,
                        "cantidad" => $cantidad,
                        "precio_unitario" => $precioUnitario
                    ];
                }

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

            $facturaCompraId = DaoFacturaCompra::insertConDetalle($proveedorId, $numeroFactura, Security::getUserId(), $lineas);
            AuditLogger::log('crear', 'Compras', 'Factura de compra registrada: ' . $numeroFactura, ['factura_compra_id' => $facturaCompraId]);

            Site::redirectTo("index.php?page=ComprasController&action=view&id=" . $facturaCompraId);
            exit;
        }

        Renderer::render("compra_create", [
            "proveedores" => DaoProveedor::getActivos(),
            "productos" => DaoProducto::getActivos()
        ]);
    }

    private function proveedores(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("proveedores", ["proveedores" => DaoProveedor::getAll(), "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."]);
                return;
            }

            $nombre = Validators::sanitizeString($_POST["nombre"] ?? "");
            $contacto = Validators::sanitizeString($_POST["contacto"] ?? "");
            $telefono = Validators::sanitizeString($_POST["telefono"] ?? "");
            $email = Validators::sanitizeString($_POST["email"] ?? "");
            $direccion = Validators::sanitizeString($_POST["direccion"] ?? "");

            if ($nombre === "") {
                Renderer::render("proveedores", ["proveedores" => DaoProveedor::getAll(), "error" => "El nombre del proveedor es obligatorio."]);
                return;
            }

            $newId = DaoProveedor::insert($nombre, $contacto, $telefono, $email, $direccion);
            AuditLogger::log('crear', 'Compras', 'Proveedor creado: ' . $nombre, ['proveedor_id' => $newId]);

            Site::redirectTo("index.php?page=ComprasController&action=proveedores");
            exit;
        }

        Renderer::render("proveedores", ["proveedores" => DaoProveedor::getAll()]);
    }
}
