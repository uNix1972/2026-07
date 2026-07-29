<?php

namespace Controllers;

use Dao\ContactoMensaje;
use Utilities\Security;
use Utilities\Validators;

class Contacto extends PublicController
{
    private const ASUNTOS_VALIDOS = [
        "Consulta sobre el sistema",
        "Reporte de error",
        "Solicitud de acceso",
        "Colaboración académica",
        "Otro"
    ];

    public function run(): void
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->processContactMessage();
            return;
        }

        \Utilities\Context::setContext("navDark", true);
        \Utilities\Context::setContext(
            "login",
            Security::isLogged()
        );
        \Utilities\Site::addLink("public/css/contacto.css");
        \Utilities\Site::addEndScript("public/js/contacto.js");
        \Views\Renderer::render("contacto", []);
    }

    private function processContactMessage(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        if (!Security::validateCsrfPost()) {
            $this->jsonResponse(
                false,
                "Solicitud inválida o expirada. Recargue la página e "
                    . "intente nuevamente.",
                403
            );
            return;
        }

        $nombre = Validators::sanitizeString(
            $_POST["nombre"] ?? "",
            100
        );
        $email = Validators::sanitizeEmail($_POST["email"] ?? "");
        $asunto = Validators::sanitizeString(
            $_POST["asunto"] ?? "",
            120
        );
        $mensaje = Validators::sanitizeText(
            $_POST["mensaje"] ?? "",
            2000
        );

        if (
            $nombre === ""
            || $email === null
            || !in_array($asunto, self::ASUNTOS_VALIDOS, true)
            || $mensaje === ""
        ) {
            $this->jsonResponse(
                false,
                "Complete todos los campos con información válida.",
                422
            );
            return;
        }

        $ipOrigen = filter_var(
            $_SERVER["REMOTE_ADDR"] ?? "",
            FILTER_VALIDATE_IP
        );

        try {
            $messageId = ContactoMensaje::insert(
                $nombre,
                $email,
                $asunto,
                $mensaje,
                is_string($ipOrigen) ? $ipOrigen : null
            );
        } catch (\Throwable $ex) {
            error_log(
                "Contact message persistence failed: "
                    . $ex->getMessage()
            );
            $this->jsonResponse(
                false,
                "No fue posible registrar el mensaje. Intente nuevamente.",
                500
            );
            return;
        }

        if ($messageId <= 0) {
            $this->jsonResponse(
                false,
                "No fue posible confirmar el registro del mensaje.",
                500
            );
            return;
        }

        $this->jsonResponse(
            true,
            "Mensaje recibido correctamente. Quedó registrado para "
                . "seguimiento."
        );
    }

    private function jsonResponse(
        bool $ok,
        string $message,
        int $status = 200
    ): void {
        http_response_code($status);
        echo json_encode(
            [
                "ok" => $ok,
                "message" => $message
            ],
            JSON_UNESCAPED_UNICODE
        );
    }
}
