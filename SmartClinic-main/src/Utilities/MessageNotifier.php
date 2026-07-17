<?php

namespace Utilities;

class MessageNotifier
{
    private const DEFAULT_API_URL = 'https://wpssyr.com:9001/send-message';

    public static function sendAppointmentSaved(array $paciente, array $medico, string $fechaHora, int $citaId): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $number = self::normalizePhone((string)($paciente['telefono'] ?? ''));
        if ($number === '') {
            error_log('MessageNotifier: cita #' . $citaId . ' no enviada porque el paciente no tiene telefono valido.');
            return false;
        }

        $message = self::buildAppointmentMessage($paciente, $medico, $fechaHora);
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

    private static function buildAppointmentMessage(array $paciente, array $medico, string $fechaHora): string
    {
        $patientName = self::fullName($paciente, 'paciente');
        $doctorName = self::fullName($medico, 'medico');
        $dateTime = self::parseDateTime($fechaHora);
        $consultorio = self::getConsultorio($medico);

        return sprintf(
            "¡Buen día %s!\n\n" .
            "👩🏽‍⚕️👨🏽‍⚕️ Soy tu asistente virtual de SmartClinic.\n\n" .
            "Su cita médica con el Dr. %s ha sido agendada con éxito, puede revisar la siguiente información:\n\n" .
            "☀️ Día: %s\n" .
            "⏱️ Hora: %s\n" .
            "Consultorio: %s",
            $patientName,
            $doctorName,
            self::formatSpanishDate($dateTime),
            $dateTime->format('h:i A'),
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

    private static function getConsultorio(array $medico): string
    {
        $consultorio = trim((string)($medico['consultorio'] ?? ''));
        if ($consultorio !== '') {
            return $consultorio;
        }

        return self::config('WHATSAPP_DEFAULT_CONSULTORIO', '11');
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
