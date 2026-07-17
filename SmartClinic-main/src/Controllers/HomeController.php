<?php

namespace Controllers;

use Dao\Medicos as DaoMedicos;
use Dao\Pacientes as DaoPacientes;
use Dao\Citas as DaoCitas;
use Utilities\Security;

// Dashboard principal para usuarios autenticados
class HomeController extends PrivateController
{
    // =============================
    // RUN
    // =============================
    public function run(): void
    {
        if (isset($_GET['action']) && $_GET['action'] === 'calendarPartial') {
            $this->calendarPartial();
            return;
        }
        if (isset($_GET['action']) && $_GET['action'] === 'dayView') {
            $this->dayView();
            return;
        }

        $medicos = DaoMedicos::getAllMedicos();
        $pacientes = DaoPacientes::getAllPacientes();
        $citas = DaoCitas::getAllCitas();

        $userId = Security::getUserId();
        $canManageMedicos = Security::isAuthorized($userId, 'MedicosController', 'CTR');
        $canManagePacientes = Security::isAuthorized($userId, 'PacientesController', 'CTR');
        $citasPorFecha = [];
        $hoy = date('Y-m-d');
        $citasHoy = 0;
        $citasPendientes = 0;
        
        foreach ($citas as $cita) {
            if (!empty($cita['fecha_hora'])) {
                $fecha = date('Y-m-d', strtotime($cita['fecha_hora']));
                if (!isset($citasPorFecha[$fecha])) {
                    $citasPorFecha[$fecha] = [];
                }
                $citasPorFecha[$fecha][] = [
                    'id' => $cita['id'],
                    'hora' => date('H:i', strtotime($cita['fecha_hora'])),
                    'paciente' => ($cita['paciente_nombres'] ?? '') . ' ' . ($cita['paciente_apellidos'] ?? ''),
                    'medico' => ($cita['medico_nombres'] ?? '') . ' ' . ($cita['medico_apellidos'] ?? ''),
                    'estado' => $cita['nombre_estado'] ?? 'Pendiente',
                    'estado_id' => $cita['estado_id'] ?? 1,
                ];
                if ($fecha === $hoy) {
                    $citasHoy++;
                }
                if (($cita['estado_id'] ?? 1) === 1) {
                    $citasPendientes++;
                }
            }
        }

        $mesActual = isset($_GET['cal_mes']) ? intval($_GET['cal_mes']) : intval(date('m'));
        $anioActual = isset($_GET['cal_anio']) ? intval($_GET['cal_anio']) : intval(date('Y'));
        
        if ($mesActual < 1) { $mesActual = 12; $anioActual--; }
        if ($mesActual > 12) { $mesActual = 1; $anioActual++; }

        $prevMes = $mesActual - 1;
        $prevAnio = $anioActual;
        if ($prevMes < 1) { $prevMes = 12; $prevAnio--; }
        
        $nextMes = $mesActual + 1;
        $nextAnio = $anioActual;
        if ($nextMes > 12) { $nextMes = 1; $nextAnio++; }

        $cal_semanas = $this->generarCalendarioSemanas($anioActual, $mesActual, $citasPorFecha, date('Y-m-d'));
        $diasEnMes = $this->diasEnMes($anioActual, $mesActual);

        $totalPacientes = count(DaoPacientes::getAllPacientes());
        $totalMedicos = count(DaoMedicos::getAllMedicos());
        
        $pacientesRecientes = array_slice(array_reverse(DaoPacientes::getAllPacientes()), 0, 5);
        $medicosRecientes = array_slice(array_reverse(DaoMedicos::getAllMedicos()), 0, 5);

        $dataView = [
            'userName' => Security::getUser()['userName'] ?? 'Usuario',
            'fecha_hoy' => date('d/m/Y'),
            'total_medicos' => count(DaoMedicos::getAllMedicos()),
            'total_pacientes' => $totalPacientes,
            'total_citas' => count($citas),
            'citas_hoy' => $citasHoy,
            'citas_pendientes' => $citasPendientes,
            'medicos' => $medicosRecientes,
            'pacientes' => $pacientesRecientes,
            'citas' => array_slice($citas, 0, 5),
            'canManageMedicos' => $canManageMedicos,
            'canManagePacientes' => $canManagePacientes,
            'canSchedule' => Security::isLogged() && !$canManageMedicos,
            'citasPorFecha' => $citasPorFecha,
            'cal_mes' => $mesActual,
            'cal_anio' => $anioActual,
            'cal_nombre_mes' => $this->getNombreMes($mesActual),
            'cal_dias_mes' => $diasEnMes,
            'cal_primer_dia_semana' => intval(date('w', strtotime("{$anioActual}-{$mesActual}-01"))), // 0=Domingo
            'hoy' => date('Y-m-d'),
            'prev_mes' => $prevMes,
            'prev_anio' => $prevAnio,
            'next_mes' => $nextMes,
            'next_anio' => $nextAnio,
            'cal_semanas' => $cal_semanas,
        ];

        \Views\Renderer::render("dashboard", $dataView);
    }

    private function getNombreMes(int $mes): string
    {
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $nombres[$mes] ?? '';
    }

    private function generarCalendarioSemanas(int $anio, int $mes, array $citasPorFecha, string $hoy): array
    {
        $diasEnMes = $this->diasEnMes($anio, $mes);
        $primerDiaSemana = intval(date('w', strtotime("{$anio}-{$mes}-01")));
        
        $mesAnterior = $mes - 1;
        $anioAnterior = $anio;
        if ($mesAnterior < 1) { $mesAnterior = 12; $anioAnterior--; }
        $diasMesAnterior = $this->diasEnMes($anioAnterior, $mesAnterior);
        
        $semanas = [];
        $diaActual = 1;
        $diaMesAnterior = $diasMesAnterior - $primerDiaSemana + 1;
        
        for ($semana = 0; $semana < 6; $semana++) {
            $dias = [];
            for ($diaSemana = 0; $diaSemana < 7; $diaSemana++) {
                if ($semana === 0 && $diaSemana < $primerDiaSemana) {
                    $fecha = sprintf('%04d-%02d-%02d', $anioAnterior, $mesAnterior, $diaMesAnterior);
                    $dias[] = [
                        'dia' => $diaMesAnterior,
                        'fecha' => $fecha,
                        'other_month' => true,
                        'today' => false,
                        'has_events' => false,
                        'eventos' => [],
                        'more_events' => false,
                        'more_count' => 0,
                    ];
                    $diaMesAnterior++;
                } elseif ($diaActual <= $diasEnMes) {
                    $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $diaActual);
                    $eventosDia = $citasPorFecha[$fecha] ?? [];
                    $eventosMostrar = array_slice($eventosDia, 0, 3);
                    $moreCount = count($eventosDia) - 3;
                    
                    $eventosFormateados = [];
                    foreach ($eventosMostrar as $evt) {
                        $eventosFormateados[] = [
                            'hora' => $evt['hora'],
                            'paciente' => $evt['paciente'],
                            'estado_id' => $evt['estado_id'],
                        ];
                    }
                    
                    $dias[] = [
                        'dia' => $diaActual,
                        'fecha' => $fecha,
                        'other_month' => false,
                        'today' => ($fecha === $hoy),
                        'has_events' => count($eventosDia) > 0,
                        'eventos' => $eventosFormateados,
                        'more_events' => $moreCount > 0,
                        'more_count' => $moreCount,
                    ];
                    $diaActual++;
                } else {
                    $mesSiguiente = $mes + 1;
                    $anioSiguiente = $anio;
                    if ($mesSiguiente > 12) { $mesSiguiente = 1; $anioSiguiente++; }
                    $diaSiguiente = $diaActual - $diasEnMes;
                    $fecha = sprintf('%04d-%02d-%02d', $anioSiguiente, $mesSiguiente, $diaSiguiente);
                    $dias[] = [
                        'dia' => $diaSiguiente,
                        'fecha' => $fecha,
                        'other_month' => true,
                        'today' => false,
                        'has_events' => false,
                        'eventos' => [],
                        'more_events' => false,
                        'more_count' => 0,
                    ];
                    $diaActual++;
                }
            }
            $semanas[] = ['dias' => $dias];
            if ($diaActual > $diasEnMes && $semana >= 4) {
                $soloMesSiguiente = true;
                foreach ($dias as $d) {
                    if (!$d['other_month']) { $soloMesSiguiente = false; break; }
                }
                if ($soloMesSiguiente) break;
            }
        }
        
        return $semanas;
    }
    private function diasEnMes(int $anio, int $mes): int
    {
        return (int)date('t', strtotime("{$anio}-{$mes}-01"));
    }
    public function calendarPartial(): void
    {
        $mes = \Utilities\Validators::sanitizeInt($_GET['mes'] ?? 0, 1, 12) ?? intval(date('m'));
        $anio = \Utilities\Validators::sanitizeInt($_GET['anio'] ?? 0, 2000, 2100) ?? intval(date('Y'));

        $citas = DaoCitas::getAllCitas();
        $citasPorFecha = [];
        foreach ($citas as $cita) {
            if (!empty($cita['fecha_hora'])) {
                $fecha = date('Y-m-d', strtotime($cita['fecha_hora']));
                if (!isset($citasPorFecha[$fecha])) {
                    $citasPorFecha[$fecha] = [];
                }
                $citasPorFecha[$fecha][] = [
                    'id' => $cita['id'],
                    'hora' => date('H:i', strtotime($cita['fecha_hora'])),
                    'paciente' => ($cita['paciente_nombres'] ?? '') . ' ' . ($cita['paciente_apellidos'] ?? ''),
                    'medico' => ($cita['medico_nombres'] ?? '') . ' ' . ($cita['medico_apellidos'] ?? ''),
                    'estado' => $cita['nombre_estado'] ?? 'Pendiente',
                    'estado_id' => $cita['estado_id'] ?? 1,
                ];
            }
        }

        $prevMes = $mes - 1;
        $prevAnio = $anio;
        if ($prevMes < 1) { $prevMes = 12; $prevAnio--; }

        $nextMes = $mes + 1;
        $nextAnio = $anio;
        if ($nextMes > 12) { $nextMes = 1; $nextAnio++; }

        $cal_semanas = $this->generarCalendarioSemanas($anio, $mes, $citasPorFecha, date('Y-m-d'));

        $dataView = [
            'cal_mes' => $mes,
            'cal_anio' => $anio,
            'cal_nombre_mes' => $this->getNombreMes($mes),
            'prev_mes' => $prevMes,
            'prev_anio' => $prevAnio,
            'next_mes' => $nextMes,
            'next_anio' => $nextAnio,
            'cal_semanas' => $cal_semanas,
            'hoy' => date('Y-m-d'),
        ];

        \Views\Renderer::render("calendar_partial", $dataView);
    }

    public function dayView(): void
    {
        $fecha = \Utilities\Validators::sanitizeDate($_GET['fecha'] ?? '');
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }
        
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt) {
            $fecha = date('Y-m-d');
            $dt = new \DateTime($fecha);
        }
        
        $citas = DaoCitas::getAllCitas();
        $citasDelDia = [];
        
        foreach ($citas as $cita) {
            if (!empty($cita['fecha_hora'])) {
                $fechaCita = date('Y-m-d', strtotime($cita['fecha_hora']));
                if ($fechaCita === $fecha) {
                    $citasDelDia[] = [
                        'id' => $cita['id'],
                        'hora' => date('H:i', strtotime($cita['fecha_hora'])),
                        'paciente' => ($cita['paciente_nombres'] ?? '') . ' ' . ($cita['paciente_apellidos'] ?? ''),
                        'paciente_id' => $cita['paciente_id'] ?? 0,
                        'medico' => ($cita['medico_nombres'] ?? '') . ' ' . ($cita['medico_apellidos'] ?? ''),
                        'medico_id' => $cita['medico_id'] ?? 0,
                        'especialidad' => $cita['nombre_especialidad'] ?? '',
                        'estado' => $cita['nombre_estado'] ?? 'Pendiente',
                        'estado_id' => $cita['estado_id'] ?? 1,
                    ];
                }
            }
        }
        
        usort($citasDelDia, function ($a, $b) {
            return strcmp($a['hora'], $b['hora']);
        });
        
        $citasPendientes = 0;
        $citasCompletadas = 0;
        $citasCanceladas = 0;
        foreach ($citasDelDia as $c) {
            if (($c['estado_id'] ?? 1) === 1) $citasPendientes++;
            elseif (($c['estado_id'] ?? 1) === 2) $citasCanceladas++;
            elseif (($c['estado_id'] ?? 1) === 3) $citasCompletadas++;
        }
        
        $fechaFormateada = $dt->format('d/m/Y');
        $diaSemana = $this->getNombreDiaSemana($dt->format('w'));
        
        $dataView = [
            'fecha' => $fecha,
            'fecha_formateada' => $fechaFormateada,
            'dia_semana' => $diaSemana,
            'citas' => $citasDelDia,
            'total_citas' => count($citasDelDia),
            'citas_pendientes' => $citasPendientes,
            'citas_completadas' => $citasCompletadas,
            'citas_canceladas' => $citasCanceladas,
            'has_citas' => count($citasDelDia) > 0,
        ];
        
        \Views\Renderer::render("day_view", $dataView);
    }
    
    private function getNombreDiaSemana(int $dia): string
    {
        $nombres = [
            0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
            4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
        ];
        return $nombres[$dia] ?? '';
    }
}
