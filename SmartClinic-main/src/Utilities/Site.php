<?php

namespace Utilities;

class Site
{
    public static function configure()
    {
        $donenv = new \Utilities\DotEnv("parameters.env");
        \Utilities\Context::setArrayToContext($donenv->load());
        \Utilities\Context::setContext(
            "BASE_DIR",
            self::resolveBaseDir(
                (string)\Utilities\Context::getContextByKey("BASE_DIR"),
                (string)($_SERVER["SCRIPT_NAME"] ?? "")
            )
        );
        date_default_timezone_set(\Utilities\Context::getContextByKey("TIMEZONE"));
        \Utilities\Context::setContext('CURRENT_YEAR', date("Y"));
    }

    /**
     * Uses an explicit BASE_DIR when configured and otherwise derives the
     * application path from the executing front controller.
     */
    private static function resolveBaseDir(
        string $configuredBaseDir,
        string $scriptName
    ): string {
        $configuredBaseDir = trim(str_replace("\\", "/", $configuredBaseDir));
        if ($configuredBaseDir !== "") {
            return $configuredBaseDir === "/"
                ? ""
                : "/" . trim($configuredBaseDir, "/");
        }

        $scriptDirectory = str_replace("\\", "/", dirname($scriptName));
        if ($scriptDirectory === "/" || $scriptDirectory === ".") {
            return "";
        }

        return "/" . trim($scriptDirectory, "/");
    }

    public static function getPageRequest()
    {
        $pageRequest = Context::getContextByKey("PUBLIC_DEFAULT_CONTROLLER");
        if (\Utilities\Security::isLogged()) {
            $pageRequest = Context::getContextByKey("PRIVATE_DEFAULT_CONTROLLER");
        }

        if (isset($_GET["page"]) && trim((string)$_GET["page"]) !== '') {
            $pageRequest = self::normalizePageRequest((string)$_GET["page"]);
        }

        if ($pageRequest === 'Home') {
            $pageRequest = 'HomeController';
        }

        $pageRequest = self::resolveControllerName($pageRequest);
        Context::setArrayToContext($_GET);
        Context::setContext("request_uri", $_SERVER["REQUEST_URI"] ?? 'index.php');

        return "Controllers\\" . $pageRequest;
    }

    private static function normalizePageRequest(string $page): string
    {
        $page = str_replace(["_", "-", "."], "\\", trim($page));
        $page = preg_replace('/[^A-Za-z0-9\\\\]/', '', $page) ?? '';
        $page = trim($page, "\\");

        return $page === '' ? Context::getContextByKey("PUBLIC_DEFAULT_CONTROLLER") : $page;
    }

    private static function resolveControllerName(string $pageRequest): string
    {
        $parts = array_values(array_filter(explode('\\', $pageRequest), 'strlen'));
        if (count($parts) === 0) {
            return Context::getContextByKey("PUBLIC_DEFAULT_CONTROLLER");
        }

        $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Controllers';
        $resolvedParts = [];
        $currentDir = $baseDir;

        foreach ($parts as $idx => $part) {
            $isLast = $idx === count($parts) - 1;
            $resolved = self::matchControllerSegment($currentDir, $part, $isLast);

            if ($resolved === null) {
                // Conserva el texto solicitado si no hay coincidencia; el controlador Error
                // manejara la ruta no encontrada de forma controlada.
                $resolvedParts[] = $part;
                $currentDir .= DIRECTORY_SEPARATOR . $part;
                continue;
            }

            $resolvedParts[] = $resolved['name'];
            $currentDir = $resolved['path'];
        }

        return implode('\\', $resolvedParts);
    }

    private static function matchControllerSegment(string $dir, string $segment, bool $isLast): ?array
    {
        if (!is_dir($dir)) {
            return null;
        }

        $targetLower = strtolower($segment . ($isLast ? '.php' : ''));
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if ($isLast && is_file($path) && strtolower($entry) === $targetLower) {
                return [
                    'name' => pathinfo($entry, PATHINFO_FILENAME),
                    'path' => $path,
                ];
            }

            if (!$isLast && is_dir($path) && strtolower($entry) === strtolower($segment)) {
                return [
                    'name' => $entry,
                    'path' => $path,
                ];
            }
        }

        return null;
    }

    public static function redirectTo($url)
    {
        if (Context::getContextByKey("USE_URLREWRITE") == "1") {
            header("Location:" . \Views\Renderer::rewriteUrl($url));
        } else {
            header("Location:" . $url);
        }

        die();
    }

    public static function redirectToWithMsg($url, $msg)
    {
        $safeMsg = htmlspecialchars((string)$msg, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        echo '<script>alert("' . $safeMsg . '");';
        echo ' window.location.assign("' . $url . '");</script>';
        die();
    }

    public static function addLink($href)
    {
        $tmpLinks = \Utilities\Context::getContextByKey("SiteLinks");
        if ($tmpLinks === "") {
            $tmpLinks = array($href);
        } else {
            $tmpLinks[] = $href;
        }
        \Utilities\Context::setContext("SiteLinks", $tmpLinks);
    }

    public static function addBeginScript($src)
    {
        $tmpSrcs = \Utilities\Context::getContextByKey("BeginScripts");
        if ($tmpSrcs === "") {
            $tmpSrcs = array($src);
        } else {
            $tmpSrcs[] = $src;
        }
        \Utilities\Context::setContext("BeginScripts", $tmpSrcs);
    }

    public static function addEndScript($src)
    {
        $tmpSrcs = \Utilities\Context::getContextByKey("EndScripts");
        if ($tmpSrcs === "") {
            $tmpSrcs = array($src);
        } else {
            $tmpSrcs[] = $src;
        }
        \Utilities\Context::setContext("EndScripts", $tmpSrcs);
    }

    public static function logError($ex, $errorCode)
    {
        error_log((string)$ex);
        error_log(
            '[' . date('Y-m-d H:i:s') . '] ' . (string)$ex . PHP_EOL,
            3,
            sys_get_temp_dir() . '/smartclinic-error.log'
        );
        \Utilities\Context::setContext("ERROR_CODE", $errorCode);

        $showDetails = \Utilities\Context::getContextByKey('DEVELOPMENT') === '1';
        \Utilities\Context::setContext(
            "ERROR_MSG",
            $showDetails
                ? htmlspecialchars((string)$ex, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : 'Error registrado en logs del servidor.'
        );
    }
}
