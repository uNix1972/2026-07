<?php

namespace Controllers;

class Servicios extends PublicController
{
    public function run(): void
    {
        \Utilities\Context::setContext('navDark', true);
        \Utilities\Context::setContext('login', \Utilities\Security::isLogged());
        \Utilities\Site::addLink('public/css/servicios.css');
        \Views\Renderer::render('servicios', []);
    }
}
