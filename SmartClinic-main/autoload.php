<?php
/**
 * SmartClinic autoload bootstrap.
 * Usa Composer cuando vendor/autoload.php existe y, si no existe, aplica un
 * autoload PSR-4 local para que el proyecto funcione en Docker o en entregas
 * academicas sin ejecutar composer install.
 */
$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
    return;
}

spl_autoload_register(function (string $class): void {
    $class = ltrim($class, '\\');
    $file = __DIR__ . '/src/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
