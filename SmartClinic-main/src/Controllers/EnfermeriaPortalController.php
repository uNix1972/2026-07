<?php

namespace Controllers;

use Dao\EnfermeriaPortal as DaoEnfermeriaPortal;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

/**
 * Read-only workspace for a nurse's patient queue.
 *
 * This first increment deliberately contains no POST actions. It presents
 * today's appointments only after the DAO has scoped them to the health
 * centers actively assigned to the authenticated nurse.
 */
class EnfermeriaPortalController extends PrivateController
{
    public function run(): void
    {
        $this->index();
    }

    private function index(): void
    {
        Site::addLink("public/css/nursing-portal.css");

        $usuarioId = (int) Security::getUserId();
        $enfermera = DaoEnfermeriaPortal::getEnfermeraByUsuario($usuarioId);
        if (!$enfermera) {
            http_response_code(403);
            exit("La cuenta no está vinculada con una enfermera activa.");
        }

        $centros = DaoEnfermeriaPortal::getCentrosActivosByUsuario($usuarioId);
        $hoy = new \DateTimeImmutable("today");
        $manana = $hoy->modify("+1 day");
        $colaCompleta = DaoEnfermeriaPortal::getColaDelDiaByUsuario(
            $usuarioId,
            $hoy->format("Y-m-d H:i:s"),
            $manana->format("Y-m-d H:i:s")
        );

        $centroIds = array_values(array_unique(array_map(
            static fn(array $centro): int => (int) $centro["centro_salud_id"],
            $centros
        )));
        $areas = $this->getUniqueStrings($centros, "area");
        $medicoIds = array_values(array_unique(array_map(
            static fn(array $cita): int => (int) $cita["medico_id"],
            $colaCompleta
        )));
        $estadoIds = array_values(array_unique(array_map(
            static fn(array $cita): int => (int) $cita["estado_id"],
            $colaCompleta
        )));

        $centroFiltro = $this->getAllowedId("centro_id", $centroIds);
        $areaFiltro = $this->getAllowedString("area", $areas);
        $medicoFiltro = $this->getAllowedId("medico_id", $medicoIds);
        $estadoFiltro = $this->getAllowedId("estado_id", $estadoIds);

        $cola = array_values(array_filter(
            $colaCompleta,
            static function (array $cita) use (
                $centroFiltro,
                $areaFiltro,
                $medicoFiltro,
                $estadoFiltro
            ): bool {
                return ($centroFiltro === 0
                        || (int) $cita["centro_salud_id"] === $centroFiltro)
                    && ($areaFiltro === ""
                        || (string) $cita["enfermera_area"] === $areaFiltro)
                    && ($medicoFiltro === 0
                        || (int) $cita["medico_id"] === $medicoFiltro)
                    && ($estadoFiltro === 0
                        || (int) $cita["estado_id"] === $estadoFiltro);
            }
        ));

        $counts = $this->buildCounts($cola);
        $this->prepareQueueRows($cola);

        Renderer::render("enfermeria_portal", [
            "enfermera_nombres" => $this->escape($enfermera["nombres"] ?? ""),
            "enfermera_apellidos" => $this->escape($enfermera["apellidos"] ?? ""),
            "enfermera_colegiatura" =>
                $this->escape($enfermera["num_colegiatura"] ?? ""),
            "fecha_hoy" => $this->formatSpanishDate($hoy),
            "cola" => $cola,
            "hayCentros" => count($centros) > 0,
            "totalResultados" => $counts["total"],
            "totalConfirmadas" => $counts["confirmadas"],
            "totalEnEspera" => $counts["en_espera"],
            "totalPreclinicaPendiente" => $counts["preclinica_pendiente"],
            "centros" => $this->buildCenterOptions($centros, $centroFiltro),
            "areas" => $this->buildStringOptions($areas, $areaFiltro),
            "medicos" => $this->buildDoctorOptions(
                $colaCompleta,
                $medicoFiltro
            ),
            "estados" => $this->buildStatusOptions(
                $colaCompleta,
                $estadoFiltro
            ),
            "centroFiltroValue" => $centroFiltro > 0
                ? (string) $centroFiltro
                : "",
            "areaFiltroValue" => $this->escape($areaFiltro),
            "medicoFiltroValue" => $medicoFiltro > 0
                ? (string) $medicoFiltro
                : "",
            "estadoFiltroValue" => $estadoFiltro > 0
                ? (string) $estadoFiltro
                : "",
            "hayFiltros" => $centroFiltro > 0
                || $areaFiltro !== ""
                || $medicoFiltro > 0
                || $estadoFiltro > 0
        ]);
    }

    /**
     * Accepts an integer filter only when it belongs to the server-generated
     * list of values available to the current nurse.
     */
    private function getAllowedId(string $key, array $allowed): int
    {
        $value = Validators::sanitizeId($_GET[$key] ?? 0) ?? 0;
        return in_array($value, $allowed, true) ? $value : 0;
    }

    /**
     * Accepts a text filter only when it is one of the nurse's assigned areas.
     */
    private function getAllowedString(string $key, array $allowed): string
    {
        $value = trim((string) ($_GET[$key] ?? ""));
        return in_array($value, $allowed, true) ? $value : "";
    }

    private function getUniqueStrings(array $rows, string $key): array
    {
        $values = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row[$key] ?? ""));
            if ($value !== "") {
                $values[$value] = true;
            }
        }
        $values = array_keys($values);
        natcasesort($values);
        return array_values($values);
    }

    private function buildCenterOptions(array $centros, int $selectedId): array
    {
        return array_map(function (array $centro) use ($selectedId): array {
            return [
                "id" => (int) $centro["centro_salud_id"],
                "nombre" => $this->escape($centro["centro_nombre"] ?? ""),
                "area" => $this->escape($centro["area"] ?? ""),
                "selected" =>
                    (int) $centro["centro_salud_id"] === $selectedId
            ];
        }, $centros);
    }

    private function buildStringOptions(array $values, string $selected): array
    {
        return array_map(function (string $value) use ($selected): array {
            return [
                "value" => $this->escape($value),
                "label" => $this->escape($value),
                "selected" => $value === $selected
            ];
        }, $values);
    }

    private function buildDoctorOptions(array $queue, int $selectedId): array
    {
        $doctors = [];
        foreach ($queue as $row) {
            $id = (int) $row["medico_id"];
            $doctors[$id] = trim(
                (string) $row["medico_nombres"]
                . " "
                . (string) $row["medico_apellidos"]
            );
        }
        natcasesort($doctors);

        $options = [];
        foreach ($doctors as $id => $name) {
            $options[] = [
                "id" => $id,
                "nombre" => $this->escape("Dr/a " . $name),
                "selected" => $id === $selectedId
            ];
        }
        return $options;
    }

    private function buildStatusOptions(array $queue, int $selectedId): array
    {
        $statuses = [];
        foreach ($queue as $row) {
            $statuses[(int) $row["estado_id"]] =
                (string) $row["nombre_estado"];
        }
        asort($statuses, SORT_NATURAL | SORT_FLAG_CASE);

        $options = [];
        foreach ($statuses as $id => $name) {
            $options[] = [
                "id" => $id,
                "nombre" => $this->escape($name),
                "selected" => $id === $selectedId
            ];
        }
        return $options;
    }

    private function buildCounts(array $queue): array
    {
        $counts = [
            "total" => count($queue),
            "confirmadas" => 0,
            "en_espera" => 0,
            "preclinica_pendiente" => 0
        ];

        foreach ($queue as $row) {
            $estadoId = (int) $row["estado_id"];
            if ($estadoId === 2) {
                $counts["confirmadas"]++;
            }
            if ($estadoId === 6) {
                $counts["en_espera"]++;
            }
            if (
                in_array($estadoId, [2, 6, 7], true)
                && empty($row["signos_vitales_id"])
            ) {
                $counts["preclinica_pendiente"]++;
            }
        }

        return $counts;
    }

    private function prepareQueueRows(array &$queue): void
    {
        foreach ($queue as &$row) {
            $estadoId = (int) $row["estado_id"];
            $patientName = trim(
                (string) $row["paciente_nombres"]
                . " "
                . (string) $row["paciente_apellidos"]
            );
            $doctorName = trim(
                (string) $row["medico_nombres"]
                . " "
                . (string) $row["medico_apellidos"]
            );
            $timestamp = strtotime((string) $row["fecha_hora"]);

            $row["hora"] = $timestamp !== false
                ? date("h:i A", $timestamp)
                : "";
            $row["paciente_nombre"] = $this->escape($patientName);
            $row["paciente_iniciales"] = $this->getInitials($patientName);
            $row["paciente_identidad"] =
                $this->escape($row["paciente_identidad"] ?? "");
            $row["paciente_telefono"] =
                $this->escape($row["paciente_telefono"] ?? "");
            $row["medico_nombre"] = $this->escape("Dr/a " . $doctorName);
            $row["nombre_especialidad"] =
                $this->escape($row["nombre_especialidad"] ?? "Medicina general");
            $row["centro_nombre"] =
                $this->escape($row["centro_nombre"] ?? "");
            $row["centro_codigo"] =
                $this->escape($row["centro_codigo"] ?? "");
            $row["consultorio"] =
                $this->escape($row["consultorio"] ?? "Sin asignar");
            $row["enfermera_area"] =
                $this->escape($row["enfermera_area"] ?? "");
            $row["nombre_estado"] =
                $this->escape($row["nombre_estado"] ?? "");
            $row["estado_clase"] = $this->getStatusClass($estadoId);
            $row["preclinica_estado"] = empty($row["signos_vitales_id"])
                ? "Pendiente"
                : "Registrada";
            $row["preclinica_clase"] = empty($row["signos_vitales_id"])
                ? "is-pending"
                : "is-ready";
            $row["esPrioritaria"] =
                $estadoId === 6 && empty($row["signos_vitales_id"]);
        }
        unset($row);
    }

    private function getStatusClass(int $statusId): string
    {
        return match ($statusId) {
            2 => "is-confirmed",
            3 => "is-completed",
            4, 5 => "is-muted",
            6 => "is-waiting",
            7 => "is-attending",
            default => "is-pending"
        };
    }

    private function getInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = "";
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= function_exists("mb_substr")
                ? mb_substr($part, 0, 1, "UTF-8")
                : substr($part, 0, 1);
        }
        return $this->escape(strtoupper($initials ?: "P"));
    }

    private function formatSpanishDate(\DateTimeImmutable $date): string
    {
        $months = [
            1 => "enero",
            2 => "febrero",
            3 => "marzo",
            4 => "abril",
            5 => "mayo",
            6 => "junio",
            7 => "julio",
            8 => "agosto",
            9 => "septiembre",
            10 => "octubre",
            11 => "noviembre",
            12 => "diciembre"
        ];

        return (int) $date->format("j")
            . " de "
            . $months[(int) $date->format("n")]
            . " de "
            . $date->format("Y");
    }

    private function escape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            "UTF-8"
        );
    }
}
