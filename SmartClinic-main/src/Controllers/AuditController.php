<?php

namespace Controllers;

use Utilities\AuditLogger;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class AuditController extends PrivateController
{
    public function run(): void
    {
        $userId = Security::getUserId();
        $isAdmin = $userId === 1 || Security::isInRol($userId, 1);
        if (!$isAdmin) {
            Site::redirectTo('index.php?page=HomeController');
            exit;
        }

        $records = AuditLogger::readLatest(80);
        Renderer::render('audit_log', [
            'records' => $records,
            'totalRecords' => count(AuditLogger::readAll()),
        ]);
    }
}
