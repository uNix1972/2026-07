<?php

namespace Controllers;

class Contacto extends PublicController
{
    public function run(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processContactMessage();
            return;
        }

        \Utilities\Context::setContext('navDark', true);
        \Utilities\Context::setContext('login', \Utilities\Security::isLogged());
        \Utilities\Site::addLink('public/css/contacto.css');
        \Utilities\Site::addEndScript('public/js/contacto.js');
        \Views\Renderer::render('contacto', []);
    }

    private function processContactMessage(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!\Utilities\Security::validateCsrfPost()) {
            echo json_encode([
                'ok' => false,
                'message' => 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.',
            ]);
            return;
        }

        $nombre = \Utilities\Validators::sanitizeString($_POST['nombre'] ?? '', 100);
        $email = \Utilities\Validators::sanitizeEmail($_POST['email'] ?? '');
        $asunto = \Utilities\Validators::sanitizeString($_POST['asunto'] ?? '', 120);
        $mensaje = \Utilities\Validators::sanitizeText($_POST['mensaje'] ?? '', 2000);

        if ($nombre === '' || $email === null || $asunto === '' || $mensaje === '') {
            echo json_encode([
                'ok' => false,
                'message' => 'Complete todos los campos con información válida.',
            ]);
            return;
        }

        $entry = [
            'fecha' => date('Y-m-d H:i:s'),
            'nombre' => $nombre,
            'email' => $email,
            'asunto' => $asunto,
            'mensaje' => $mensaje,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        $dir = __DIR__ . '/../../data';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . '/contact_messages.json';
        $messages = [];
        if (is_file($file)) {
            $content = file_get_contents($file);
            $decoded = json_decode($content ?: '[]', true);
            $messages = is_array($decoded) ? $decoded : [];
        }
        $messages[] = $entry;
        file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        echo json_encode([
            'ok' => true,
            'message' => 'Mensaje recibido correctamente. Quedó registrado para seguimiento.',
        ]);
    }
}
