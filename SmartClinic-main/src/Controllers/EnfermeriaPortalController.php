<?php

namespace Controllers;

use Dao\EnfermeriaPortal as DaoEnfermeriaPortal;
use Utilities\AuditLogger;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

/**
 * Operational workspace for a nurse's patient queue.
 *
 * It presents today's appointments and permits one narrowly scoped action:
 * confirming arrival for a confirmed appointment in an assigned center.
 */
class EnfermeriaPortalController extends PrivateController
{
    private const CONFIRMAR_LLEGADA_FEATURE = "ConfirmarLlegadaEnfermeria";
    private const REGISTRAR_PRECLINICA_FEATURE =
        "RegistrarPreclinicaEnfermeria";
    private const PATIENT_STATUS_OPTIONS = [
        "confirmada" => "Confirmada",
        "en_espera" => "En espera",
        "preclinica_pendiente" => "Preclínica pendiente",
        "preclinica_completada" => "Preclínica completada",
        "en_atencion" => "En atención"
    ];

    public function run(): void
    {
        $action = trim((string) ($_GET["action"] ?? "index"));
        switch ($action) {
            case "confirmarLlegada":
                $this->confirmarLlegada();
                return;
            case "preclinica":
                $this->preclinica();
                return;
            case "guardarPreclinica":
                $this->guardarPreclinica();
                return;
        }

        $this->index();
    }

    private function index(): void
    {
        Site::addLink("public/css/nursing-portal.css?v=20260730-2");

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
        $centroFiltro = $this->getAllowedId("centro_id", $centroIds);
        $areaFiltro = $this->getAllowedString("area", $areas);
        $medicoFiltro = $this->getAllowedId("medico_id", $medicoIds);
        $estadoPacienteFiltro = $this->getAllowedString(
            "estado_paciente",
            array_keys(self::PATIENT_STATUS_OPTIONS)
        );

        $cola = array_values(array_filter(
            $colaCompleta,
            static function (array $cita) use (
                $centroFiltro,
                $areaFiltro,
                $medicoFiltro,
                $estadoPacienteFiltro
            ): bool {
                return ($centroFiltro === 0
                        || (int) $cita["centro_salud_id"] === $centroFiltro)
                    && ($areaFiltro === ""
                        || (string) $cita["enfermera_area"] === $areaFiltro)
                    && ($medicoFiltro === 0
                        || (int) $cita["medico_id"] === $medicoFiltro)
                    && self::matchesPatientStatus(
                        $cita,
                        $estadoPacienteFiltro
                    );
            }
        ));

        $counts = $this->buildCounts($cola);
        $puedeConfirmarLlegada = Security::isAuthorized(
            $usuarioId,
            self::CONFIRMAR_LLEGADA_FEATURE
        );
        $puedeRegistrarPreclinica = Security::isAuthorized(
            $usuarioId,
            self::REGISTRAR_PRECLINICA_FEATURE
        );
        $this->prepareQueueRows(
            $cola,
            $puedeConfirmarLlegada,
            $puedeRegistrarPreclinica
        );
        $feedback = $this->getFeedback();

        Renderer::render("enfermeria_portal", [
            "enfermera_nombres" => $this->escape($enfermera["nombres"] ?? ""),
            "enfermera_apellidos" => $this->escape($enfermera["apellidos"] ?? ""),
            "enfermera_colegiatura" =>
                $this->escape($enfermera["num_colegiatura"] ?? ""),
            "fecha_hoy" => $this->formatSpanishDate($hoy),
            "cola" => $cola,
            "csrf_token" => Security::getCsrfToken(),
            "mensaje" => $feedback["mensaje"],
            "mensajeExito" => $feedback["exito"],
            "mensajeError" => $feedback["error"],
            "hayCentros" => count($centros) > 0,
            "totalResultados" => $counts["total"],
            "totalConfirmadas" => $counts["confirmadas"],
            "totalEnEspera" => $counts["en_espera"],
            "totalPreclinicaPendiente" => $counts["preclinica_pendiente"],
            "totalPreclinicaCompletada" =>
                $counts["preclinica_completada"],
            "totalEnAtencion" => $counts["en_atencion"],
            "centros" => $this->buildCenterOptions($centros, $centroFiltro),
            "areas" => $this->buildStringOptions($areas, $areaFiltro),
            "medicos" => $this->buildDoctorOptions(
                $colaCompleta,
                $medicoFiltro
            ),
            "estadosPaciente" => $this->buildPatientStatusOptions(
                $estadoPacienteFiltro
            ),
            "centroFiltroValue" => $centroFiltro > 0
                ? (string) $centroFiltro
                : "",
            "areaFiltroValue" => $this->escape($areaFiltro),
            "medicoFiltroValue" => $medicoFiltro > 0
                ? (string) $medicoFiltro
                : "",
            "estadoPacienteFiltroValue" =>
                $this->escape($estadoPacienteFiltro),
            "hayFiltros" => $centroFiltro > 0
                || $areaFiltro !== ""
                || $medicoFiltro > 0
                || $estadoPacienteFiltro !== ""
        ]);
    }

    /**
     * Confirms arrival without accepting a center or nurse identity from POST.
     */
    private function confirmarLlegada(): void
    {
        if (strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "")) !== "POST") {
            http_response_code(405);
            exit("Método no permitido.");
        }

        $usuarioId = (int) Security::getUserId();
        if (!Security::isAuthorized(
            $usuarioId,
            self::CONFIRMAR_LLEGADA_FEATURE
        )) {
            http_response_code(403);
            exit("No tiene permiso para confirmar llegadas.");
        }

        if (!Security::validateCsrfPost()) {
            $this->redirectWithResult("csrf_error");
        }

        $citaId = Validators::sanitizeId($_POST["cita_id"] ?? 0);
        if ($citaId === null) {
            $this->redirectWithResult("arrival_invalid");
        }

        $hoy = new \DateTimeImmutable("today");
        $actualizada = DaoEnfermeriaPortal::confirmarLlegadaEnCentroAsignado(
            $citaId,
            $usuarioId,
            $hoy->format("Y-m-d H:i:s"),
            $hoy->modify("+1 day")->format("Y-m-d H:i:s")
        );

        if (!$actualizada) {
            $this->redirectWithResult("arrival_invalid");
        }

        AuditLogger::log(
            "CONFIRMAR_LLEGADA",
            "Enfermería",
            "Paciente marcado en sala de espera desde el portal de enfermería.",
            ["cita_id" => $citaId]
        );
        $this->redirectWithResult("arrival_ok");
    }

    /**
     * Displays vital signs only for today's waiting appointment in a center
     * assigned to the authenticated nurse.
     */
    private function preclinica(): void
    {
        if (strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "GET")) !== "GET") {
            http_response_code(405);
            exit("Método no permitido.");
        }

        Site::addLink("public/css/nursing-portal.css?v=20260730-2");
        $usuarioId = (int) Security::getUserId();
        if (!Security::isAuthorized(
            $usuarioId,
            self::REGISTRAR_PRECLINICA_FEATURE
        )) {
            http_response_code(403);
            exit("No tiene permiso para registrar preclínica.");
        }

        $citaId = Validators::sanitizeId($_GET["cita_id"] ?? 0);
        if ($citaId === null) {
            $this->redirectWithResult("preclinic_unavailable");
        }

        [$fechaInicio, $fechaFin] = $this->getTodayRange();
        $cita = DaoEnfermeriaPortal::getCitaPreclinicaByUsuario(
            $citaId,
            $usuarioId,
            $fechaInicio,
            $fechaFin
        );
        if (!$cita) {
            $this->redirectWithResult("preclinic_unavailable");
        }

        $fechaHora = strtotime((string) $cita["fecha_hora"]);
        $patientName = trim(
            (string) $cita["paciente_nombres"]
            . " "
            . (string) $cita["paciente_apellidos"]
        );
        $doctorName = trim(
            (string) $cita["medico_nombres"]
            . " "
            . (string) $cita["medico_apellidos"]
        );
        $feedback = $this->getPreclinicFeedback();

        Renderer::render("enfermeria_preclinica", [
            "csrf_token" => Security::getCsrfToken(),
            "cita_id" => (int) $cita["id"],
            "paciente_nombre" => $this->escape($patientName),
            "paciente_iniciales" => $this->getInitials($patientName),
            "paciente_identidad" =>
                $this->escape($cita["paciente_identidad"] ?? ""),
            "paciente_telefono" =>
                $this->escape($cita["paciente_telefono"] ?? ""),
            "medico_nombre" => $this->escape("Dr/a " . $doctorName),
            "nombre_especialidad" =>
                $this->escape($cita["nombre_especialidad"] ?? ""),
            "centro_nombre" => $this->escape($cita["centro_nombre"] ?? ""),
            "enfermera_area" =>
                $this->escape($cita["enfermera_area"] ?? ""),
            "consultorio" => $this->escape($cita["consultorio"] ?? ""),
            "fecha_cita" => $fechaHora !== false
                ? date("d/m/Y", $fechaHora)
                : "",
            "hora_cita" => $fechaHora !== false
                ? date("h:i A", $fechaHora)
                : "",
            "temperatura" => $this->escape($cita["temperatura"] ?? ""),
            "presion_sistolica" =>
                $this->escape($cita["presion_sistolica"] ?? ""),
            "presion_diastolica" =>
                $this->escape($cita["presion_diastolica"] ?? ""),
            "frecuencia_cardiaca" =>
                $this->escape($cita["frecuencia_cardiaca"] ?? ""),
            "frecuencia_respiratoria" =>
                $this->escape($cita["frecuencia_respiratoria"] ?? ""),
            "saturacion_oxigeno" =>
                $this->escape($cita["saturacion_oxigeno"] ?? ""),
            "peso" => $this->escape($cita["peso"] ?? ""),
            "talla" => $this->escape($cita["talla"] ?? ""),
            "signos_notas" => $this->escape(html_entity_decode(
                (string) ($cita["signos_notas"] ?? ""),
                ENT_QUOTES | ENT_HTML5,
                "UTF-8"
            )),
            "esEdicion" => !empty($cita["signos_vitales_id"]),
            "mensaje" => $feedback["mensaje"],
            "mensajeError" => $feedback["error"]
        ]);
    }

    /**
     * Validates and saves preclinical data without trusting a center ID.
     */
    private function guardarPreclinica(): void
    {
        if (strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "")) !== "POST") {
            http_response_code(405);
            exit("Método no permitido.");
        }

        $usuarioId = (int) Security::getUserId();
        if (!Security::isAuthorized(
            $usuarioId,
            self::REGISTRAR_PRECLINICA_FEATURE
        )) {
            http_response_code(403);
            exit("No tiene permiso para registrar preclínica.");
        }

        if (!Security::validateCsrfPost()) {
            $this->redirectWithResult("csrf_error");
        }

        $citaId = Validators::sanitizeId($_POST["cita_id"] ?? 0);
        if ($citaId === null) {
            $this->redirectWithResult("preclinic_unavailable");
        }

        $datos = $this->validateVitalSigns($_POST);
        if ($datos === null) {
            $this->redirectToPreclinic($citaId, "vitals_invalid");
        }

        [$fechaInicio, $fechaFin] = $this->getTodayRange();
        $cita = DaoEnfermeriaPortal::getCitaPreclinicaByUsuario(
            $citaId,
            $usuarioId,
            $fechaInicio,
            $fechaFin
        );
        if (!$cita) {
            $this->redirectWithResult("preclinic_unavailable");
        }

        $actualizada =
            DaoEnfermeriaPortal::guardarSignosVitalesEnCentroAsignado(
                $citaId,
                $usuarioId,
                $fechaInicio,
                $fechaFin,
                $datos
            );
        if (!$actualizada) {
            $this->redirectWithResult("preclinic_unavailable");
        }

        AuditLogger::log(
            empty($cita["signos_vitales_id"])
                ? "REGISTRAR_PRECLINICA"
                : "ACTUALIZAR_PRECLINICA",
            "Enfermería",
            "Signos vitales guardados desde el portal de enfermería.",
            ["cita_id" => $citaId]
        );
        $this->redirectWithResult("preclinic_ok");
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

    private function buildPatientStatusOptions(string $selected): array
    {
        $options = [];
        foreach (self::PATIENT_STATUS_OPTIONS as $value => $name) {
            $options[] = [
                "value" => $value,
                "nombre" => $this->escape($name),
                "selected" => $value === $selected
            ];
        }
        return $options;
    }

    /**
     * Matches the operational status without adding redundant database states.
     *
     * En espera includes every waiting appointment. The two preclinical
     * filters refine that same state according to whether vital signs exist.
     */
    private static function matchesPatientStatus(
        array $appointment,
        string $selected
    ): bool {
        if ($selected === "") {
            return true;
        }

        $statusId = (int) ($appointment["estado_id"] ?? 0);
        $hasVitalSigns = !empty($appointment["signos_vitales_id"]);

        return match ($selected) {
            "confirmada" => $statusId === 2,
            "en_espera" => $statusId === 6,
            "preclinica_pendiente" => $statusId === 6 && !$hasVitalSigns,
            "preclinica_completada" => $statusId === 6 && $hasVitalSigns,
            "en_atencion" => $statusId === 7,
            default => false
        };
    }

    private function buildCounts(array $queue): array
    {
        $counts = [
            "total" => count($queue),
            "confirmadas" => 0,
            "en_espera" => 0,
            "preclinica_pendiente" => 0,
            "preclinica_completada" => 0,
            "en_atencion" => 0
        ];

        foreach ($queue as $row) {
            $estadoId = (int) $row["estado_id"];
            if ($estadoId === 2) {
                $counts["confirmadas"]++;
            }
            if ($estadoId === 6) {
                $counts["en_espera"]++;
            }
            if ($estadoId === 6 && empty($row["signos_vitales_id"])) {
                $counts["preclinica_pendiente"]++;
            }
            if ($estadoId === 6 && !empty($row["signos_vitales_id"])) {
                $counts["preclinica_completada"]++;
            }
            if ($estadoId === 7) {
                $counts["en_atencion"]++;
            }
        }

        return $counts;
    }

    private function prepareQueueRows(
        array &$queue,
        bool $puedeConfirmarLlegada,
        bool $puedeRegistrarPreclinica
    ): void
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
                ? "Preclínica pendiente"
                : "Preclínica completada";
            $row["preclinica_clase"] = empty($row["signos_vitales_id"])
                ? "is-pending"
                : "is-ready";
            $row["muestraEstadoPreclinica"] = $estadoId === 6;
            $row["esPrioritaria"] =
                $estadoId === 6 && empty($row["signos_vitales_id"]);
            $row["puedeConfirmarLlegada"] =
                $puedeConfirmarLlegada && $estadoId === 2;
            $row["puedeRegistrarPreclinica"] =
                $puedeRegistrarPreclinica && $estadoId === 6;
            $row["preclinica_accion"] = empty($row["signos_vitales_id"])
                ? "Registrar preclínica"
                : "Editar preclínica";
            $row["tieneAccion"] = $row["puedeConfirmarLlegada"]
                || $row["puedeRegistrarPreclinica"];
            if ($estadoId === 7) {
                $row["accion_estado"] = "En atención";
            } elseif (!empty($row["signos_vitales_id"])) {
                $row["accion_estado"] = "Preclínica registrada";
            } else {
                $row["accion_estado"] = "Sin acción";
            }
        }
        unset($row);
    }

    private function validateVitalSigns(array $input): ?array
    {
        $datos = [
            "temperatura" => Validators::sanitizeFloat(
                $input["temperatura"] ?? null,
                30,
                45
            ),
            "presion_sistolica" => Validators::sanitizeInt(
                $input["presion_sistolica"] ?? null,
                50,
                260
            ),
            "presion_diastolica" => Validators::sanitizeInt(
                $input["presion_diastolica"] ?? null,
                30,
                180
            ),
            "frecuencia_cardiaca" => Validators::sanitizeInt(
                $input["frecuencia_cardiaca"] ?? null,
                20,
                250
            ),
            "frecuencia_respiratoria" => Validators::sanitizeInt(
                $input["frecuencia_respiratoria"] ?? null,
                5,
                80
            ),
            "saturacion_oxigeno" => Validators::sanitizeFloat(
                $input["saturacion_oxigeno"] ?? null,
                50,
                100
            ),
            "peso" => Validators::sanitizeFloat(
                $input["peso"] ?? null,
                1,
                500
            ),
            "talla" => Validators::sanitizeFloat(
                $input["talla"] ?? null,
                30,
                250
            ),
            "notas" => $this->sanitizeNotes($input["notas"] ?? "")
        ];

        foreach ($datos as $key => $value) {
            if ($key !== "notas" && $value === null) {
                return null;
            }
        }

        if ($datos["presion_sistolica"] <= $datos["presion_diastolica"]) {
            return null;
        }

        return $datos;
    }

    private function sanitizeNotes($value): string
    {
        $value = trim((string) $value);
        return function_exists("mb_substr")
            ? mb_substr($value, 0, 500, "UTF-8")
            : substr($value, 0, 500);
    }

    private function getTodayRange(): array
    {
        $hoy = new \DateTimeImmutable("today");
        return [
            $hoy->format("Y-m-d H:i:s"),
            $hoy->modify("+1 day")->format("Y-m-d H:i:s")
        ];
    }

    private function getFeedback(): array
    {
        $result = (string) ($_GET["result"] ?? "");
        $messages = [
            "arrival_ok" => [
                "mensaje" => "Llegada confirmada. El paciente está en espera.",
                "exito" => true,
                "error" => false
            ],
            "arrival_invalid" => [
                "mensaje" => "No se pudo confirmar la llegada. Verifique que la cita sea de hoy, esté confirmada y pertenezca a uno de sus centros.",
                "exito" => false,
                "error" => true
            ],
            "csrf_error" => [
                "mensaje" => "La solicitud expiró. Recargue la página e intente nuevamente.",
                "exito" => false,
                "error" => true
            ],
            "preclinic_ok" => [
                "mensaje" => "Preclínica guardada correctamente.",
                "exito" => true,
                "error" => false
            ],
            "preclinic_unavailable" => [
                "mensaje" => "La preclínica solo está disponible para citas de hoy, en espera y pertenecientes a uno de sus centros.",
                "exito" => false,
                "error" => true
            ]
        ];

        return $messages[$result] ?? [
            "mensaje" => "",
            "exito" => false,
            "error" => false
        ];
    }

    private function redirectWithResult(string $result): void
    {
        $allowed = [
            "arrival_ok",
            "arrival_invalid",
            "csrf_error",
            "preclinic_ok",
            "preclinic_unavailable"
        ];
        $result = in_array($result, $allowed, true)
            ? $result
            : "arrival_invalid";
        Site::redirectTo(
            "index.php?page=EnfermeriaPortalController&result=" . $result
        );
        exit;
    }

    private function getPreclinicFeedback(): array
    {
        if ((string) ($_GET["result"] ?? "") === "vitals_invalid") {
            return [
                "mensaje" => "Revise los valores. La presión sistólica debe ser mayor que la diastólica y todos los signos deben respetar los rangos indicados.",
                "error" => true
            ];
        }

        return ["mensaje" => "", "error" => false];
    }

    private function redirectToPreclinic(int $citaId, string $result): void
    {
        $result = $result === "vitals_invalid"
            ? $result
            : "vitals_invalid";
        Site::redirectTo(
            "index.php?page=EnfermeriaPortalController"
            . "&action=preclinica"
            . "&cita_id=" . $citaId
            . "&result=" . $result
        );
        exit;
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
