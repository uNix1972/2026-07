<?php

namespace Controllers;

use Dao\ClinicaAvanzada as Clinica;
use Views\Renderer;

class BIController extends PrivateController
{
    public function run(): void
    {
        $metricas = Clinica::getMetricasBI();
        Renderer::render('bi_dashboard', [
            'citasPorEstado' => $metricas['citasPorEstado'],
            'citasPorMes' => $metricas['citasPorMes'],
            'ingresos' => $metricas['ingresos'],
            'cargaMedicos' => $metricas['cargaMedicos'],
        ]);
    }
}
