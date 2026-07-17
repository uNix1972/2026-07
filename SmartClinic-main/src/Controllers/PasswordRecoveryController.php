<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class PasswordRecoveryController extends PublicController
{
    public function run(): void
    {
        $action = $_GET['action'] ?? 'request';
        if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->send();
            return;
        }
        if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->reset();
            return;
        }
        Renderer::render('password_recovery', [
            'csrf_token' => Security::getCsrfToken(),
            'msg' => $_GET['msg'] ?? '',
            'token' => $_GET['token'] ?? '',
            'email' => $_GET['email'] ?? '',
        ], 'authlayout.view.tpl');
    }

    private function send(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=PasswordRecoveryController&msg=Token CSRF inválido');
        }
        $email = trim((string)($_POST['email'] ?? ''));
        $user = Clinica::getUsuarioByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(16));
            Clinica::crearTokenRecuperacion($email, $token);
            $link = 'index.php?page=PasswordRecoveryController&email=' . urlencode($email) . '&token=' . urlencode($token);
            Site::redirectTo($link . '&msg=' . urlencode('Token generado para demostración académica. Copie el token o use esta pantalla para cambiar contraseña.'));
        }
        Site::redirectTo('index.php?page=PasswordRecoveryController&msg=' . urlencode('Si el correo existe, se generará un token de recuperación.'));
    }

    private function reset(): void
    {
        if (!Security::validateCsrfPost()) {
            Site::redirectTo('index.php?page=PasswordRecoveryController&msg=Token CSRF inválido');
        }
        $email = trim((string)($_POST['email'] ?? ''));
        $token = trim((string)($_POST['token'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $tokenRow = Clinica::validarTokenRecuperacion($email, $token);
        if ($tokenRow && strlen($password) >= 8) {
            Clinica::cambiarPassword($email, \Dao\Security\Security::hashPasswordPublic($password));
            Clinica::usarToken(intval($tokenRow['id']));
            Site::redirectTo('index.php?page=Sec_Login&msg=' . urlencode('Contraseña actualizada. Inicie sesión.'));
        }
        Site::redirectTo('index.php?page=PasswordRecoveryController&msg=' . urlencode('Token inválido, expirado o contraseña muy corta.'));
    }
}
