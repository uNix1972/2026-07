<?php

namespace Controllers;

class Landing extends PublicController
{
    public function run(): void
    {
        $isLogged = \Utilities\Security::isLogged();
        if ($isLogged) {
            \Utilities\Site::redirectTo('index.php?page=Home');
            return;
        }

        \Utilities\Context::setContext('navDark', true);
        \Utilities\Context::setContext('login', $isLogged);
        \Utilities\Site::addLink('public/css/landing.css');
        \Views\Renderer::render('landing', []);
    }
}
