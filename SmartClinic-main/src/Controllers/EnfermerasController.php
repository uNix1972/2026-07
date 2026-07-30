<?php

namespace Controllers;

use Dao\CentroSalud as DaoCentroSalud;
use Dao\EnfermeraCentroSalud as DaoEnfermeraCentroSalud;
use Dao\Enfermeras as DaoEnfermeras;
use Utilities\AuditLogger;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

/**
 * CRUD de Enfermeras. Mismo flujo de pantallas que MedicosController
 * (listado con buscador/paginación, crear/editar con asignación de centros
 * de salud, desactivar/activar en vez de borrar), simplificado porque las
 * enfermeras no tienen citas ni conflicto de sala: varias pueden compartir
 * la misma área/turno en un centro.
 */
class EnfermerasController extends PublicController
{
    private array $viewData = [];

    public function run(): void
    {
        $action = trim(strval($_GET["action"] ?? "index"));

        switch ($action) {
            case "create":
                $this->create();
                break;
            case "edit":
                $this->edit();
                break;
            case "desactivar":
                $this->desactivar();
                break;
            case "activar":
                $this->activar();
                break;
            case "eliminar":
                $this->eliminar();
                break;
            default:
                $this->index();
                break;
        }
    }

    private function index(): void
    {
        $enfermeraBuscadaId = Validators::sanitizeId($_GET["enfermera_id"] ?? "");
        $search = Validators::sanitizeString($_GET["search"] ?? "");
        $enfermeras = DaoEnfermeras::getAllEnfermeras();

        foreach ($enfermeras as &$enfermera) {
            $centrosTexto = (string) ($enfermera["centros_salud"] ?? "");
            $enfermera["centros_lista"] = [];
            if ($centrosTexto !== "") {
                foreach (explode(", ", $centrosTexto) as $item) {
                    $partes = explode(" - Área: ", $item, 2);
                    $enfermera["centros_lista"][] = [
                        "centro_nombre" => $partes[0] ?? $item,
                        "area" => $partes[1] ?? "",
                    ];
                }
            }
            $enfermera["tieneCentros"] = count($enfermera["centros_lista"]) > 0;
            $enfermera["esActivo"] = ($enfermera["estado"] ?? "ACT") === "ACT";
            // Solo se ofrece "Eliminar" (borrado definitivo) si la
            // enfermera nunca tuvo ningún centro asignado; si tuvo, la
            // única opción es Desactivar/Activar para no perder su
            // historial.
            $enfermera["puedeEliminar"] = !((bool) ($enfermera["tiene_asignaciones"] ?? false));
        }
        unset($enfermera);

        // Mismo patrón que Médicos: la lista de opciones del buscador se
        // arma ANTES de filtrar, con el listado completo.
        $enfermerasParaBuscador = array_map(
            static function (array $item): array {
                return [
                    "id" => (string) $item["id"],
                    "nombre" => trim((string) $item["nombres"] . " " . (string) $item["apellidos"]),
                    "extra" => (string) ($item["num_colegiatura"] ?? ""),
                ];
            },
            $enfermeras
        );
        $this->viewData["enfermerasJsonAttr"] = htmlspecialchars(
            json_encode($enfermerasParaBuscador, JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );

        $enfermeraBuscadaNombre = "";
        if ($enfermeraBuscadaId !== null) {
            $enfermeras = array_values(array_filter(
                $enfermeras,
                function (array $item) use ($enfermeraBuscadaId): bool {
                    return (int) $item["id"] === $enfermeraBuscadaId;
                }
            ));
            $enfermeraBuscadaNombre = count($enfermeras) > 0
                ? trim($enfermeras[0]["nombres"] . " " . $enfermeras[0]["apellidos"])
                : $search;
        } elseif ($search !== "") {
            $searchNormalizado = $this->normalizarBusqueda($search);
            $enfermeras = array_values(array_filter(
                $enfermeras,
                function (array $item) use ($searchNormalizado): bool {
                    return strpos($this->normalizarBusqueda((string) ($item["nombres"] ?? "")), $searchNormalizado) !== false
                        || strpos($this->normalizarBusqueda((string) ($item["apellidos"] ?? "")), $searchNormalizado) !== false
                        || strpos($this->normalizarBusqueda((string) ($item["num_colegiatura"] ?? "")), $searchNormalizado) !== false
                        || strpos($this->normalizarBusqueda((string) ($item["centros_salud"] ?? "")), $searchNormalizado) !== false;
                }
            ));
            $enfermeraBuscadaNombre = $search;
        }

        $enfermeras = array_values($enfermeras);

        // Paginación: 5 enfermeras por página, aplicada DESPUÉS del filtro
        // de búsqueda, igual que MedicosController::paginar().
        $paginacion = $this->paginar($enfermeras, 5, "pagina");

        $userId = Security::getUserId();
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);
        $this->viewData["enfermeras"] = $paginacion["items"];
        $this->viewData["paginaActual"] = $paginacion["paginaActual"];
        $this->viewData["totalPaginas"] = $paginacion["totalPaginas"];
        $this->viewData["totalEnfermeras"] = count($enfermeras);
        $this->viewData["showCrudActions"] =
            Security::isAuthorized($userId, "EnfermerasController", "CTR") || $isAdmin;
        $this->viewData["searchValue"] = $enfermeraBuscadaNombre;
        $this->viewData["enfermeraBuscadaIdValue"] = $enfermeraBuscadaId !== null ? (string) $enfermeraBuscadaId : "";
        $this->viewData["hayBusqueda"] = $enfermeraBuscadaId !== null || $search !== "";
        $this->viewData["errorEliminarEnfermera"] =
            trim((string) ($_GET["errorEliminar"] ?? ""));

        $filtrosEnfermerasUrl = "index.php?page=EnfermerasController&action=index";
        if ($enfermeraBuscadaId !== null) {
            $filtrosEnfermerasUrl .= "&enfermera_id=" . $enfermeraBuscadaId;
        } elseif ($search !== "") {
            $filtrosEnfermerasUrl .= "&search=" . urlencode($search);
        }
        $this->viewData["urlPaginaAnterior"] = $paginacion["paginaActual"] > 1
            ? $filtrosEnfermerasUrl . "&pagina=" . ($paginacion["paginaActual"] - 1)
            : "";
        $this->viewData["urlPaginaSiguiente"] = $paginacion["paginaActual"] < $paginacion["totalPaginas"]
            ? $filtrosEnfermerasUrl . "&pagina=" . ($paginacion["paginaActual"] + 1)
            : "";

        Site::addEndScript('public/js/kardex-autocomplete.js');

        Renderer::render("enfermeras", $this->viewData);
    }

    private function create(): void
    {
        $this->authorizeCrud();
        $data = $this->emptyNurseData();
        $assignments = [];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = $this->readNurseData();
            $assignmentResult = $this->readAssignments();
            $assignments = $assignmentResult["items"];

            if (!Security::validateCsrfPost()) {
                $this->renderCreate(
                    $data,
                    $assignments,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente."
                );
                return;
            }

            $error = $this->validateNurseData($data);
            if ($error === null) {
                $error = $assignmentResult["error"];
            }

            if ($error !== null) {
                $this->renderCreate($data, $assignments, $error);
                return;
            }

            try {
                $newId = DaoEnfermeras::insertEnfermeraConCentros(
                    $data["nombres"],
                    $data["apellidos"],
                    $data["num_colegiatura"],
                    $data["telefono"],
                    $assignments
                );

                AuditLogger::log(
                    "crear",
                    "Enfermeras",
                    "Enfermera creada: " . $data["nombres"] . " " . $data["apellidos"],
                    [
                        "enfermera_id" => $newId,
                        "centro_salud_ids" => array_column($assignments, "centro_salud_id")
                    ]
                );
            } catch (\Throwable $error) {
                error_log("No se pudo crear la enfermera con sus centros: " . $error->getMessage());
                $this->renderCreate(
                    $data,
                    $assignments,
                    "No fue posible guardar la enfermera. Verifique los datos e intente nuevamente."
                );
                return;
            }

            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }

        $this->renderCreate($data, $assignments);
    }

    private function edit(): void
    {
        $this->authorizeCrud();
        $id = Validators::sanitizeId($_GET["id"] ?? 0);
        $enfermera = $id !== null ? DaoEnfermeras::getEnfermeraById($id) : false;

        if (!$enfermera) {
            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }

        $assignments = DaoEnfermeraCentroSalud::getActivosByEnfermera($id);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $enfermera = array_merge($enfermera, $this->readNurseData());
            $assignmentResult = $this->readAssignments();
            $assignments = $assignmentResult["items"];

            if (!Security::validateCsrfPost()) {
                $this->renderEdit(
                    $enfermera,
                    $assignments,
                    "Solicitud inválida o expirada. Recargue la página e intente nuevamente.",
                    $id
                );
                return;
            }

            $error = $this->validateNurseData($enfermera, $id);
            if ($error === null) {
                $error = $assignmentResult["error"];
            }

            if ($error !== null) {
                $this->renderEdit($enfermera, $assignments, $error, $id);
                return;
            }

            try {
                DaoEnfermeras::updateEnfermeraConCentros(
                    $id,
                    $enfermera["nombres"],
                    $enfermera["apellidos"],
                    $enfermera["num_colegiatura"],
                    $enfermera["telefono"],
                    $assignments
                );

                AuditLogger::log(
                    "editar",
                    "Enfermeras",
                    "Enfermera actualizada: " . $enfermera["nombres"] . " " . $enfermera["apellidos"],
                    [
                        "enfermera_id" => $id,
                        "centro_salud_ids" => array_column($assignments, "centro_salud_id")
                    ]
                );
            } catch (\Throwable $error) {
                error_log("No se pudo actualizar la enfermera con sus centros: " . $error->getMessage());
                $this->renderEdit(
                    $enfermera,
                    $assignments,
                    "No fue posible actualizar la enfermera. Verifique los datos e intente nuevamente.",
                    $id
                );
                return;
            }

            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }

        $this->renderEdit($enfermera, $assignments, null, $id);
    }

    /**
     * Desactiva una enfermera (no la borra): deja de estar disponible
     * para asignaciones nuevas, pero conserva toda su información e
     * historial. Mismo patrón que MedicosController::desactivar().
     */
    private function desactivar(): void
    {
        $this->authorizeCrud();

        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $enfermera = DaoEnfermeras::getEnfermeraById($id);
            DaoEnfermeras::disable($id);
            AuditLogger::log(
                "eliminar",
                "Enfermeras",
                "Enfermera desactivada: "
                    . (($enfermera["nombres"] ?? "") . " " . ($enfermera["apellidos"] ?? "")),
                ["enfermera_id" => $id]
            );
        }

        Site::redirectTo("index.php?page=EnfermerasController&action=index");
        exit;
    }

    /**
     * Reactiva una enfermera que se había desactivado con desactivar().
     */
    private function activar(): void
    {
        $this->authorizeCrud();

        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $enfermera = DaoEnfermeras::getEnfermeraById($id);
            DaoEnfermeras::enable($id);
            AuditLogger::log(
                "activar",
                "Enfermeras",
                "Enfermera reactivada: "
                    . (($enfermera["nombres"] ?? "") . " " . ($enfermera["apellidos"] ?? "")),
                ["enfermera_id" => $id]
            );
        }

        Site::redirectTo("index.php?page=EnfermerasController&action=index");
        exit;
    }

    /**
     * Borrado DEFINITIVO de una enfermera. Se verifica ANTES si la
     * enfermera tuvo alguna asignación a un centro de salud: si tuvo
     * aunque sea una, ni se intenta — se le pide usar "Desactivar" en su
     * lugar, para no depender de que la base rechace el borrado.
     */
    private function eliminar(): void
    {
        $this->authorizeCrud();

        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !Security::validateCsrfPost()) {
            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        if ($id !== null) {
            $enfermera = DaoEnfermeras::getEnfermeraById($id);
            $nombreEnfermera = trim(
                (string) (($enfermera["nombres"] ?? "") . " " . ($enfermera["apellidos"] ?? ""))
            );

            if (DaoEnfermeras::tieneAsignaciones($id)) {
                AuditLogger::log(
                    "error",
                    "Enfermeras",
                    "No se pudo eliminar (tiene centros asignados): " . $nombreEnfermera,
                    ["enfermera_id" => $id]
                );
                Site::redirectTo(
                    "index.php?page=EnfermerasController&action=index&errorEliminar="
                        . urlencode($nombreEnfermera)
                );
                exit;
            }

            try {
                DaoEnfermeras::deleteEnfermera($id);
                AuditLogger::log(
                    "eliminar",
                    "Enfermeras",
                    "Enfermera eliminada definitivamente: " . $nombreEnfermera,
                    ["enfermera_id" => $id]
                );
            } catch (\PDOException $ex) {
                AuditLogger::log(
                    "error",
                    "Enfermeras",
                    "No se pudo eliminar: " . $nombreEnfermera,
                    ["enfermera_id" => $id]
                );
                Site::redirectTo(
                    "index.php?page=EnfermerasController&action=index&errorEliminar="
                        . urlencode($nombreEnfermera)
                );
                exit;
            }
        }

        Site::redirectTo("index.php?page=EnfermerasController&action=index");
        exit;
    }

    /**
     * Recorta un listado ya filtrado a la página pedida. Mismo criterio
     * que MedicosController::paginar().
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

    /**
     * Quita acentos y pasa a minúsculas. Mismo criterio que
     * MedicosController::normalizarBusqueda().
     */
    private function normalizarBusqueda(string $texto): string
    {
        $texto = trim($texto);
        $sinAcentos = [
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'
        ];
        return strtolower(strtr($texto, $sinAcentos));
    }

    private function authorizeCrud(): void
    {
        if (!Security::isAuthorized(Security::getUserId(), "EnfermerasController", "CTR")) {
            Site::redirectTo("index.php?page=EnfermerasController&action=index");
            exit;
        }
    }

    private function emptyNurseData(): array
    {
        return [
            "nombres" => "",
            "apellidos" => "",
            "num_colegiatura" => "",
            "telefono" => ""
        ];
    }

    private function readNurseData(): array
    {
        return [
            "nombres" => Validators::sanitizeString($_POST["nombres"] ?? "", 100),
            "apellidos" => Validators::sanitizeString($_POST["apellidos"] ?? "", 100),
            "num_colegiatura" =>
                Validators::sanitizeString($_POST["num_colegiatura"] ?? "", 50),
            "telefono" => Validators::sanitizeString($_POST["telefono"] ?? "", 20)
        ];
    }

    private function validateNurseData(array $data, int $excludeId = 0): ?string
    {
        if ($data["nombres"] === "" || $data["apellidos"] === "") {
            return "Los nombres y apellidos son obligatorios.";
        }
        if ($data["num_colegiatura"] === "" || $data["telefono"] === "") {
            return "El número de colegiatura y el teléfono son obligatorios.";
        }
        if (DaoEnfermeras::existsNumColegiatura($data["num_colegiatura"], $excludeId)) {
            return "Ya existe una enfermera con ese número de colegiatura.";
        }

        return null;
    }

    private function readAssignments(): array
    {
        $selectedIds = $_POST["centro_ids"] ?? [];
        $areas = $_POST["areas"] ?? [];
        $activeCenters = DaoCentroSalud::getActivos();
        $allowedIds = array_fill_keys(
            array_map("intval", array_column($activeCenters, "id")),
            true
        );
        $items = [];
        $seen = [];

        if (!is_array($selectedIds) || !is_array($areas)) {
            return [
                "items" => [],
                "error" => "La selección de centros de salud no es válida."
            ];
        }

        foreach ($selectedIds as $rawId) {
            $centerId = Validators::sanitizeId($rawId);

            if ($centerId === null || !isset($allowedIds[$centerId])) {
                return [
                    "items" => $items,
                    "error" => "Uno de los centros seleccionados no está activo o no existe."
                ];
            }
            if (isset($seen[$centerId])) {
                continue;
            }

            $areaRaw = trim(strval($areas[$centerId] ?? ""));
            $length = function_exists("mb_strlen")
                ? mb_strlen($areaRaw, "UTF-8")
                : strlen($areaRaw);

            if ($areaRaw === "") {
                return [
                    "items" => $items,
                    "error" => "Indique el área o turno de cada centro seleccionado."
                ];
            }
            if ($length > 50) {
                return [
                    "items" => $items,
                    "error" => "El área/turno no puede exceder 50 caracteres."
                ];
            }

            $area = Validators::sanitizeString($areaRaw, 50);
            if ($area === "") {
                return [
                    "items" => $items,
                    "error" => "El área/turno contiene un valor inválido."
                ];
            }

            $items[] = [
                "centro_salud_id" => $centerId,
                "area" => $area
            ];
            $seen[$centerId] = true;
        }

        if (count($items) === 0) {
            $message = count($activeCenters) === 0
                ? "Primero debe registrar al menos un centro de salud activo."
                : "Seleccione al menos un centro de salud e indique su área/turno.";

            return ["items" => [], "error" => $message];
        }

        return ["items" => $items, "error" => null];
    }

    private function buildCentros(array $assignments): array
    {
        $assignmentMap = [];
        foreach ($assignments as $assignment) {
            $assignmentMap[(int) $assignment["centro_salud_id"]] =
                (string) $assignment["area"];
        }

        return array_map(
            function (array $centro) use ($assignmentMap): array {
                $id = (int) $centro["id"];
                $centro["selected"] = array_key_exists($id, $assignmentMap);
                $centro["area"] = $assignmentMap[$id] ?? "";
                return $centro;
            },
            DaoCentroSalud::getActivos()
        );
    }

    private function renderCreate(
        array $data,
        array $assignments,
        ?string $error = null
    ): void {
        $this->renderForm("enfermera_create", $data, $assignments, $error, 0);
    }

    private function renderEdit(
        array $data,
        array $assignments,
        ?string $error = null,
        int $id = 0
    ): void {
        $this->renderForm("enfermera_edit", $data, $assignments, $error, $id);
    }

    private function renderForm(
        string $view,
        array $data,
        array $assignments,
        ?string $error,
        int $excludeEnfermeraId
    ): void {
        $centros = $this->buildCentros($assignments);
        $viewData = array_merge($data, [
            "id" => $excludeEnfermeraId,
            "centros" => $centros,
            "sinCentros" => count($centros) === 0,
            "puedeGuardar" => count($centros) > 0,
            "error" => $error
        ]);

        Renderer::render($view, $viewData);
    }
}
