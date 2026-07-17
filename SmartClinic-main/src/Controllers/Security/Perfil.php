<?php

namespace Controllers\Security;

use Controllers\PrivateController;
use Dao\Security\Security as DaoSecurity;
use Views\Renderer;

class Perfil extends PrivateController
{
    private $viewData = [];
    private $profileError = '';
    private $profileSuccess = '';

    public function run(): void
    {
        $userId = \Utilities\Security::getUserId();

        if ($this->isPostBack()) {
            $this->processProfileUpdate($userId);
        }

        $userData = DaoSecurity::getUsuarioById($userId);
        if (!$userData) {
            throw new \Exception('No se pudo cargar la información del usuario');
        }

        $this->viewData['userNombre'] = $userData['username'] ?? '';
        $this->viewData['userEmail'] = $userData['useremail'] ?? '';
        $this->viewData['userStatus'] = $userData['userest'] ?? '';
        $this->viewData['userTipo'] = $userData['usertipo'] ?? '';
        $this->viewData['profileError'] = $this->profileError;
        $this->viewData['profileSuccess'] = $this->profileSuccess;
        $this->viewData['hasProfileError'] = ($this->profileError !== '');
        $this->viewData['hasProfileSuccess'] = ($this->profileSuccess !== '');

        Renderer::render('security/perfil', $this->viewData);
    }

    private function processProfileUpdate(int $userId): void
    {
        if (!\Utilities\Security::validateCsrfPost()) {
            $this->profileError = 'Solicitud inválida o expirada. Recargue la página e intente nuevamente.';
            return;
        }

        $newName = \Utilities\Validators::sanitizeString($_POST['userNombre'] ?? '');

        if (\Utilities\Validators::IsEmpty($newName)) {
            $this->profileError = 'El nombre no puede quedar vacío';
            return;
        }

        if (mb_strlen($newName) > 80) {
            $this->profileError = 'El nombre no puede exceder 80 caracteres';
            return;
        }

        \Utilities\Security::updateUserName($userId, $newName);
        $this->profileSuccess = 'Nombre actualizado correctamente';
    }
}
