<?php
namespace Controllers;

use Views\Renderer;
use Dao\Producto as DaoProducto;
use Dao\AjusteInventario as DaoAjusteInventario;
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

        Renderer::render("inventario", [
            "productos" => $productos,
            "ajustesRecientes" => DaoAjusteInventario::getRecientes(10)
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
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!Security::validateCsrfPost()) {
                Renderer::render("inventario_ajustar", ["productos" => DaoProducto::getActivos(), "error" => "Solicitud inválida o expirada. Recargue la página e intente nuevamente."]);
                return;
            }

            $productoId = Validators::sanitizeId($_POST["producto_id"] ?? 0);
            $tipoAjuste = ($_POST["tipo_ajuste"] ?? "") === "SALIDA" ? "SALIDA" : "ENTRADA";
            $cantidad = Validators::sanitizeInt($_POST["cantidad"] ?? 0, 1);
            $motivo = Validators::sanitizeString($_POST["motivo"] ?? "");

            $producto = $productoId !== null ? DaoProducto::getById($productoId) : null;

            if ($productoId === null || $producto === null || $cantidad === null || $motivo === "") {
                Renderer::render("inventario_ajustar", ["productos" => DaoProducto::getActivos(), "error" => "Todos los campos son obligatorios y la cantidad debe ser mayor a cero."]);
                return;
            }

            if ($tipoAjuste === "SALIDA" && $cantidad > intval($producto["stock_actual"])) {
                Renderer::render("inventario_ajustar", ["productos" => DaoProducto::getActivos(), "error" => "No hay suficiente stock disponible para registrar esta salida."]);
                return;
            }

            $delta = $tipoAjuste === "SALIDA" ? -$cantidad : $cantidad;
            DaoProducto::ajustarStock($productoId, $delta);
            DaoAjusteInventario::insert($productoId, $tipoAjuste, $cantidad, $motivo, Security::getUserId());
            AuditLogger::log('ajustar', 'Inventario', "Ajuste de stock ($tipoAjuste) sobre " . $producto["nombre"] . ": $cantidad", ['producto_id' => $productoId]);

            Site::redirectTo("index.php?page=InventarioController&action=index");
            exit;
        }

        Renderer::render("inventario_ajustar", ["productos" => DaoProducto::getActivos()]);
    }
}
