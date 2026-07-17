<?php

namespace Controllers\Sec;

use Controllers\PublicController;

class Register extends PublicController
{
    public function run() :void
    {
        // Registro deshabilitado: redirigir a login con mensaje
        \Utilities\Site::redirectToWithMsg("index.php?page=Sec_Login", "El registro de nuevos usuarios está deshabilitado. Contacte a administración.");
    }
}
?>
