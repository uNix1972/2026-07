<?php
namespace Controllers\Sec;
class Login extends \Controllers\PublicController
{
    private $txtEmail = "";
    private $txtPswd = "";
    private $errorEmail = "";
    private $errorPswd = "";
    private $generalError = "";
    private $hasError = false;

    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;
    private const ATTEMPTS_FILE = __DIR__ . '/../../../data/login_attempts.json';

    public function run(): void
    {
        \Utilities\Context::setContext('layoutFile', 'authlayout');

        if ($this->isPostBack()) {
            if (!\Utilities\Security::validateCsrfPost()) {
                $this->generalError = "Solicitud inválida o expirada. Recargue la página e intente nuevamente.";
                $this->hasError = true;
            }
            $clientIp = $this->getClientIp();
            if (!$this->hasError && !$this->checkRateLimit($clientIp)) {
                $this->generalError = "Demasiados intentos fallidos. Intente nuevamente en 15 minutos.";
                $this->hasError = true;
            } else {
                $this->txtEmail = \Utilities\Validators::sanitizeEmail($_POST["txtEmail"] ?? "");
                $this->txtPswd = trim($_POST["txtPswd"] ?? "");

                if ($this->txtEmail === null || \Utilities\Validators::IsEmpty($this->txtEmail)) {
                    $this->errorEmail = "¡Debe ingresar un correo electrónico válido!";
                    $this->hasError = true;
                } elseif (!\Utilities\Validators::IsValidEmail($this->txtEmail)) {
                    $this->errorEmail = "¡Correo no tiene el formato adecuado!";
                    $this->hasError = true;
                }

                if (\Utilities\Validators::IsEmpty($this->txtPswd)) {
                    $this->errorPswd = "¡Debe ingresar una contraseña!";
                    $this->hasError = true;
                } elseif (strlen($this->txtPswd) > 72) {
                    $this->errorPswd = "La contraseña es demasiado larga.";
                    $this->hasError = true;
                }

                if (!$this->hasError) {
                    $dbUser = \Dao\Security\Security::getUsuarioByEmail($this->txtEmail);
                    if ($dbUser) {
                        if ($dbUser["userest"] != \Dao\Security\Estados::ACTIVO) {
                            $this->generalError = "¡Credenciales son incorrectas!";
                            $this->hasError = true;
                            error_log(
                                sprintf(
                                    "ERROR: %d %s tiene cuenta con estado %s",
                                    $dbUser["usercod"],
                                    $dbUser["useremail"],
                                    $dbUser["userest"]
                                )
                            );
                        }
                        if (!\Dao\Security\Security::verifyPassword($this->txtPswd, $dbUser["userpswd"])) {
                            $this->generalError = "¡Credenciales son incorrectas!";
                            $this->hasError = true;
                            $this->recordFailedAttempt($clientIp);
                            error_log(
                                sprintf(
                                    "ERROR: %d %s contraseña incorrecta",
                                    $dbUser["usercod"],
                                    $dbUser["useremail"]
                                )
                            );
                        }
                        if (!$this->hasError) {
                            $this->clearAttempts($clientIp);
                            \Utilities\Security::login(
                                $dbUser["usercod"],
                                $dbUser["username"],
                                $dbUser["useremail"]
                            );
                            \Utilities\AuditLogger::log('login', 'Seguridad', 'Inicio de sesión correcto: ' . $dbUser["useremail"]);
                            if (\Utilities\Context::getContextByKey("redirto") !== "") {
                                \Utilities\Site::redirectTo(
                                    \Utilities\Context::getContextByKey("redirto")
                                );
                            } else {
                                if (\Utilities\Security::isInRol($dbUser["usercod"], 1)) {
                                    \Utilities\Site::redirectTo("index.php?page=HomeController");
                                } else {
                                    \Utilities\Site::redirectTo("index.php");
                                }
                            }
                        }
                    } else {
                        $this->recordFailedAttempt($clientIp);
                        error_log(
                            sprintf(
                                "ERROR: %s trato de ingresar",
                                $this->txtEmail
                            )
                        );
                        $this->generalError = "¡Credenciales son incorrectas!";
                    }
                }
            }
        }
        $dataView = get_object_vars($this);
        \Views\Renderer::render("security/login", $dataView);
    }

    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        return 'unknown';
    }

    private function checkRateLimit(string $ip): bool
    {
        $attempts = $this->loadAttempts();
        if (!isset($attempts[$ip])) {
            return true;
        }

        $now = time();
        $windowStart = $now - (self::WINDOW_MINUTES * 60);

        $recentAttempts = array_filter($attempts[$ip], function ($timestamp) use ($windowStart) {
            return $timestamp >= $windowStart;
        });

        return count($recentAttempts) < self::MAX_ATTEMPTS;
    }

    private function recordFailedAttempt(string $ip): void
    {
        $attempts = $this->loadAttempts();
        $now = time();

        if (!isset($attempts[$ip])) {
            $attempts[$ip] = [];
        }

        $attempts[$ip][] = $now;

        $hourAgo = $now - 3600;
        $attempts[$ip] = array_filter($attempts[$ip], function ($timestamp) use ($hourAgo) {
            return $timestamp >= $hourAgo;
        });

        $this->saveAttempts($attempts);
    }

    private function clearAttempts(string $ip): void
    {
        $attempts = $this->loadAttempts();
        if (isset($attempts[$ip])) {
            unset($attempts[$ip]);
            $this->saveAttempts($attempts);
        }
    }

    private function loadAttempts(): array
    {
        $file = self::ATTEMPTS_FILE;
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function saveAttempts(array $attempts): void
    {
        $file = self::ATTEMPTS_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, json_encode($attempts), LOCK_EX);
    }
}