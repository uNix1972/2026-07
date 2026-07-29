<?php

namespace Controllers;

use Dao\ContactoMensaje;
use Utilities\AuditLogger;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

class ContactoMensajesController extends PrivateController
{
    private const ESTADOS = ["PEN", "LEI", "RES"];

    public function run(): void
    {
        $action = trim((string) ($_GET["action"] ?? "index"));

        if ($action === "status") {
            $this->status();
            return;
        }

        $this->index();
    }

    private function index(): void
    {
        $estado = strtoupper(
            Validators::sanitizeString($_GET["estado"] ?? "", 3)
        );
        if (!in_array($estado, self::ESTADOS, true)) {
            $estado = "";
        }
        $search = Validators::sanitizeString(
            $_GET["search"] ?? "",
            100
        );
        $mensajes = ContactoMensaje::getAll($estado, $search);
        $csrfToken = Security::getCsrfToken();

        foreach ($mensajes as &$mensaje) {
            // El motor cambia el contexto dentro de foreach; cada fila
            // necesita su propio token para que los formularios POST lo vean.
            $mensaje["csrf_token"] = $csrfToken;
            $mensaje["estado_pendiente"] = $mensaje["estado"] === "PEN";
            $mensaje["estado_leido"] = $mensaje["estado"] === "LEI";
            $mensaje["estado_resuelto"] = $mensaje["estado"] === "RES";
            $mensaje["estado_texto"] = $this->getStatusLabel(
                $mensaje["estado"]
            );
            $mensaje["fecha_creacion_texto"] = date(
                "d/m/Y H:i",
                strtotime($mensaje["fecha_creacion"])
            );
        }
        unset($mensaje);

        $counts = ContactoMensaje::getCounts();
        $success = (string) (
            $_SESSION["contacto_mensajes_success"] ?? ""
        );
        $error = (string) (
            $_SESSION["contacto_mensajes_error"] ?? ""
        );
        unset(
            $_SESSION["contacto_mensajes_success"],
            $_SESSION["contacto_mensajes_error"]
        );

        Renderer::render("contacto_mensajes", [
            "mensajes" => $mensajes,
            "searchValue" => $search,
            "filterAll" => $estado === "",
            "filterPending" => $estado === "PEN",
            "filterRead" => $estado === "LEI",
            "filterResolved" => $estado === "RES",
            "totalMensajes" => (int) ($counts["total"] ?? 0),
            "totalPendientes" => (int) ($counts["pendientes"] ?? 0),
            "totalLeidos" => (int) ($counts["leidos"] ?? 0),
            "totalResueltos" => (int) ($counts["resueltos"] ?? 0),
            "success" => $success,
            "error" => $error
        ]);
    }

    private function status(): void
    {
        if (
            $_SERVER["REQUEST_METHOD"] !== "POST"
            || !Security::validateCsrfPost()
        ) {
            $_SESSION["contacto_mensajes_error"] =
                "Solicitud inválida o expirada.";
            $this->redirectToInbox();
        }

        $id = Validators::sanitizeId($_POST["id"] ?? 0);
        $estado = strtoupper(
            Validators::sanitizeString($_POST["estado"] ?? "", 3)
        );
        $mensaje = $id !== null
            ? ContactoMensaje::getById($id)
            : null;

        if (
            $mensaje === null
            || !in_array($estado, self::ESTADOS, true)
        ) {
            $_SESSION["contacto_mensajes_error"] =
                "No fue posible actualizar el mensaje seleccionado.";
            $this->redirectToInbox();
        }

        ContactoMensaje::setStatus(
            $id,
            $estado,
            (int) Security::getUserId()
        );
        AuditLogger::log(
            "actualizar estado",
            "Mensajes de contacto",
            "Mensaje #" . $id . " actualizado a "
                . $this->getStatusLabel($estado),
            [
                "contacto_mensaje_id" => $id,
                "estado_anterior" => $mensaje["estado"],
                "estado_nuevo" => $estado
            ]
        );

        $_SESSION["contacto_mensajes_success"] =
            "El mensaje fue actualizado correctamente.";
        $this->redirectToInbox();
    }

    private function getStatusLabel(string $estado): string
    {
        return [
            "PEN" => "Pendiente",
            "LEI" => "Leído",
            "RES" => "Resuelto"
        ][$estado] ?? "Desconocido";
    }

    private function redirectToInbox(): void
    {
        Site::redirectTo(
            "index.php?page=ContactoMensajesController"
        );
        exit;
    }
}
