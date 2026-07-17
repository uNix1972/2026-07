<?php

namespace Controllers\Sec;

class Logout extends \Controllers\PublicController
{
    public function run(): void
    {
        \Utilities\AuditLogger::log('logout', 'Seguridad', 'Cierre de sesión');
        \Utilities\Security::logout();
        \Utilities\Site::redirectTo("index.php");
    }
}
