<?php

namespace Utilities;

class MessageNotifier
{
    private const DEFAULT_API_URL = 'https://wpssyr.com:9001/send-message';

    public static function sendAppointmentSaved(
        array $paciente,
        array $medico,
        array $ubicacion,
        string $fechaHora,
        int $citaId
    ): bool {
        if (!self::isEnabled()) {
            return false;
        }

        $number = self::normalizePhone((string)($paciente['telefono'] ?? ''));
        if ($number === '') {
            error_log('MessageNotifier: cita #' . $citaId . ' no enviada porque el paciente no tiene telefono valido.');
            return false;
        }

        $message = self::buildAppointmentMessage(
            $paciente,
            $medico,
            $ubicacion,
            $fechaHora
        );
        return self::sendMessage($number, $message);
    }

    /**
     * Notifica que una cita futura fue trasladada a otro consultorio.
     */
    public static function sendAppointmentRoomChanged(array $appointment): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $citaId = intval($appointment['id'] ?? 0);
        $number = self::normalizePhone(
            (string)($appointment['paciente_telefono'] ?? '')
        );
        if ($number === '') {
            error_log(
                'MessageNotifier: cambio de consultorio de cita #'
                . $citaId
                . ' no enviado porque el paciente no tiene telefono valido.'
            );
            return false;
        }

        $dateTime = self::parseDateTime(
            (string)($appointment['fecha_hora'] ?? '')
        );
        $patientName = trim(
            (string)($appointment['paciente_nombres'] ?? '')
            . ' '
            . (string)($appointment['paciente_apellidos'] ?? '')
        );
        $doctorName = trim(
            (string)($appointment['medico_nombres'] ?? '')
            . ' '
            . (string)($appointment['medico_apellidos'] ?? '')
        );
        $centerName = trim(
            (string)($appointment['centro_nombre'] ?? '')
        );
        $previousRoom = trim(
            (string)($appointment['consultorio_anterior'] ?? '')
        );
        $newRoom = trim((string)($appointment['consultorio'] ?? ''));

        $message = sprintf(
            "Buen día %s.\n\n"
            . "SmartClinic informa un cambio de consultorio para su cita "
            . "con el Dr. %s.\n\n"
            . "Día: %s\n"
            . "Hora: %s\n"
            . "Centro de salud: %s\n"
            . "Consultorio anterior: %s\n"
            . "Nuevo consultorio: %s",
            $patientName !== '' ? $patientName : 'paciente',
            $doctorName !== '' ? $doctorName : 'médico asignado',
            self::formatSpanishDate($dateTime),
            $dateTime->format('h:i A'),
            $centerName !== '' ? $centerName : 'Por confirmar',
            $previousRoom !== '' ? $previousRoom : 'Por confirmar',
            $newRoom !== '' ? $newRoom : 'Por confirmar'
        );

        return self::sendMessage($number, $message);
    }

    private static function isEnabled(): bool
    {
        $enabled = strtolower(self::config('WHATSAPP_ENABLED', '0'));
        return in_array($enabled, ['1', 'true', 'yes', 'on'], true);
    }

    private static function sendMessage(string $number, string $message): bool
    {
        $url = self::config('WHATSAPP_API_URL', self::DEFAULT_API_URL);
        $token = self::config('WHATSAPP_API_TOKEN', '');

        if ($token === '') {
            error_log('MessageNotifier: WHATSAPP_API_TOKEN no esta configurado.');
            return false;
        }

        $payload = http_build_query([
            'number' => $number,
            'message' => $message,
            'token' => $token,
        ]);

        if (function_exists('curl_init')) {
            return self::sendWithCurl($url, $payload);
        }

        return self::sendWithStream($url, $payload);
    }

    private static function sendWithCurl(string $url, string $payload): bool
    {
        $ch = curl_init($url);
        if ($ch === false) {
            error_log('MessageNotifier: no se pudo inicializar cURL.');
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            error_log('MessageNotifier: fallo envio HTTP ' . $httpCode . ' ' . $error);
            return false;
        }

        return true;
    }

    private static function sendWithStream(string $url, string $payload): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            error_log('MessageNotifier: fallo envio con stream HTTP.');
            return false;
        }

        return true;
    }

    private static function buildAppointmentMessage(
        array $paciente,
        array $medico,
        array $ubicacion,
        string $fechaHora
    ): string {
        $patientName = self::fullName($paciente, 'paciente');
        $doctorName = self::fullName($medico, 'medico');
        $dateTime = self::parseDateTime($fechaHora);
        $centroNombre = trim((string)($ubicacion['centro_nombre'] ?? ''));
        $consultorio = self::getConsultorio($ubicacion);

        return sprintf(
            "¡Buen día %s!\n\n" .
            "👩🏽‍⚕️👨🏽‍⚕️ Soy tu asistente virtual de SmartClinic.\n\n" .
            "Su cita médica con el Dr. %s ha sido agendada con éxito, puede revisar la siguiente información:\n\n" .
            "☀️ Día: %s\n" .
            "⏱️ Hora: %s\n" .
            "Centro de salud: %s\n" .
            "Consultorio: %s",
            $patientName,
            $doctorName,
            self::formatSpanishDate($dateTime),
            $dateTime->format('h:i A'),
            $centroNombre !== '' ? $centroNombre : 'Por confirmar',
            $consultorio
        );
    }

    private static function formatSpanishDate(\DateTime $dateTime): string
    {
        $months = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $month = $months[intval($dateTime->format('n'))] ?? $dateTime->format('m');
        return intval($dateTime->format('j')) . ' de ' . $month . ' del ' . $dateTime->format('Y');
    }

    private static function getConsultorio(array $ubicacion): string
    {
        $consultorio = trim((string)($ubicacion['consultorio'] ?? ''));
        if ($consultorio !== '') {
            return $consultorio;
        }

        return 'Por confirmar';
    }

    private static function fullName(array $data, string $fallback): string
    {
        $name = trim((string)($data['nombres'] ?? '') . ' ' . (string)($data['apellidos'] ?? ''));
        return $name !== '' ? $name : $fallback;
    }

    private static function normalizePhone(string $phone): string
    {
        $number = preg_replace('/\D+/', '', $phone) ?? '';
        if (substr($number, 0, 2) === '00') {
            $number = substr($number, 2);
        }

        $countryCode = self::config('WHATSAPP_DEFAULT_COUNTRY_CODE', '504');
        if (strlen($number) === 8 && $countryCode !== '') {
            return $countryCode . $number;
        }

        return strlen($number) >= 8 ? $number : '';
    }

    private static function parseDateTime(string $fechaHora): \DateTime
    {
        try {
            return new \DateTime($fechaHora);
        } catch (\Exception $ex) {
            return new \DateTime();
        }
    }

    private static function config(string $key, string $default): string
    {
        $value = Context::getContextByKey($key);
        return $value === '' ? $default : trim((string)$value);
    }
}
