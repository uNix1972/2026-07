<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class NotificacionesController extends PrivateController
{
    public function run(): void
    {
        if (($_GET['action'] ?? '') === 'read') {
            $id = intval($_GET['id'] ?? 0);
            if ($id > 0) {
                Clinica::marcarNotificacionLeida($id);
            }
            Site::redirectTo('index.php?page=NotificacionesController');
        }
        Renderer::render('notificaciones', [
            'notificaciones' => Clinica::getNotificaciones(intval(Security::getUserId())),
        ]);
    }
}
