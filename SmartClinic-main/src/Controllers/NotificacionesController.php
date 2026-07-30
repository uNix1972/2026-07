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
            // Antes esto era un simple link GET sin token CSRF y sin
            // verificar dueño: cualquiera con sesión podía marcar como
            // leída la notificación de otro usuario solo cambiando el id
            // en la URL. Ahora exige POST + CSRF, y el UPDATE en el DAO
            // ya filtra por usuario_destino_id.
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Security::validateCsrfPost()) {
                Site::redirectTo(
                    'index.php?page=NotificacionesController&msg='
                    . urlencode('Solicitud inválida o expirada.')
                );
                return;
            }

            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                Clinica::marcarNotificacionLeida($id, intval(Security::getUserId()));
            }
            Site::redirectTo(
                'index.php?page=NotificacionesController&msg='
                . urlencode('Notificación marcada como leída.')
            );
            return;
        }
        // "Ver leídas" muestra el historial de lo ya atendido; por defecto
        // ("pendientes") se ven solo las que faltan por marcar.
        $verLeidas = ($_GET['ver'] ?? '') === 'leidas';
        $filtro = $verLeidas ? 'leidas' : 'no_leidas';

        $notificaciones = Clinica::getNotificaciones(intval(Security::getUserId()), $filtro);
        // Estilo semáforo: rojo cuando el producto ya está en 0 unidades,
        // amarillo cuando solo está por debajo del mínimo, y verde para
        // todo lo demás (citas, sala de espera, pagos, etc.) — cualquier
        // "tipo" que no sea uno de los dos avisos de stock cae en verde.
        foreach ($notificaciones as &$notificacion) {
            $tipo = (string) ($notificacion['tipo'] ?? '');
            if ($tipo === 'Stock vacío') {
                $notificacion['colorSemaforo'] = '#DC2626';
            } elseif ($tipo === 'Stock bajo') {
                $notificacion['colorSemaforo'] = '#D97706';
            } else {
                $notificacion['colorSemaforo'] = '#16A34A';
            }
            // En la vista "leídas" no tiene sentido ofrecer "Marcar
            // leída" de nuevo; se usa este flag en vez de confiar en el
            // valor crudo de "leida" que viene de la base de datos.
            $notificacion['estaLeida'] = intval($notificacion['leida'] ?? 0) === 1;
        }
        unset($notificacion);

        Renderer::render('notificaciones', [
            'notificaciones' => $notificaciones,
            'verLeidas' => $verLeidas,
            'msg' => $_GET['msg'] ?? '',
            'csrf_token' => Security::getCsrfToken(),
        ]);
    }
}
