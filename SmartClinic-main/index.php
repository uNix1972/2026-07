<?php
session_start();

if (!isset($_GET['page']) || trim($_GET['page']) === '') {
    $_GET['page'] = 'Landing';
}

// Entrada principal: enruta al MVC cuando llega ?page=...

// Router MVC: si viene page=..., ejecuta el controlador
if (isset($_GET['page']) && trim($_GET['page']) !== '') {
    require __DIR__ . '/autoload.php';

    try {
        Utilities\Site::configure();
        $pageRequest = Utilities\Site::getPageRequest();
        $instance = new $pageRequest();
        $instance->run();
        exit;
    } catch (Controllers\PrivateNoAuthException $ex) {
        $instance = new Controllers\NoAuth();
        $instance->run();
        exit;
    } catch (Controllers\PrivateNoLoggedException $ex) {
        $redirTo = urlencode(Utilities\Context::getContextByKey('request_uri'));
        Utilities\Site::redirectTo('index.php?page=Sec_Login&redirto=' . $redirTo);
        exit;
    } catch (Exception $ex) {
        Utilities\Site::logError($ex, 500);
        $instance = new Controllers\Error();
        $instance->run();
        exit;
    } catch (Error $ex) {
        Utilities\Site::logError($ex, 500);
        $instance = new Controllers\Error();
        $instance->run();
        exit;
    }
}
