<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Views\Renderer;

class PagosController extends PrivateController
{
    public function run(): void
    {
        Renderer::render('pagos', [
            'pagos' => Clinica::getPagos(),
        ]);
    }
}
