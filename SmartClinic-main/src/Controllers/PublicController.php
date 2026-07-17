<?php
/**
 * PHP Version 7.2.
 *
 * @category Public
 *
 * @author   Orlando J Betancourth <orlando.betancourth@gmail.com>
 * @license  MIT http://
 *
 * @version  CVS:1.0.0
 *
 * @see     http://
 */

namespace Controllers;

/**
 * Public Access Controller Base Class.
 *
 * @category Public
 *
 * @author   Orlando J Betancourth <orlando.betancourth@gmail.com>
 * @license  MIT http://
 *
 * @see     http://
 */
abstract class PublicController implements IController
{
    protected $name = '';

    /**
     * Public Controller Base Constructor.
     */
    public function __construct()
    {
        $this->name = get_class($this);
        \Utilities\Nav::setPublicNavContext();
        \Utilities\Site::addEndScript('public/js/main.js');
        \Utilities\Context::setContext('csrf_token', \Utilities\Security::getCsrfToken());

        $currentPage = trim($_GET['page'] ?? 'Landing');
        foreach (['Landing', 'Nosotros', 'Servicios', 'Contacto'] as $navPage) {
            \Utilities\Context::setContext('nav_'.$navPage, $currentPage === $navPage);
        }

        // Pasar variables de sesión de usuario al contexto para templates
        if (\Utilities\Security::isLogged()) {
            $userSession = \Utilities\Security::getUser();
            \Utilities\Context::setContext('login', $userSession);
            \Utilities\Context::setContext('userName', $userSession['userName']);
            \Utilities\Context::setContext('userEmail', $userSession['userEmail']);
            // Consider userId 1 as admin as well as members of role 1
            $isAdminFlag = (\Utilities\Security::getUserId() === 1) || \Utilities\Security::isInRol(\Utilities\Security::getUserId(), 1);
            \Utilities\Context::setContext('isAdmin', $isAdminFlag);

            $layoutFile = \Utilities\Context::getContextByKey('PRIVATE_LAYOUT');
            if ($layoutFile !== '') {
                \Utilities\Context::setContext(
                    'layoutFile',
                    $layoutFile
                );
                \Utilities\Nav::setNavContext();
            }
        }
    }

    /**
     * Return name of instantiated class.
     */
    public function toString(): string
    {
        return $this->name;
    }

    /**
     * Returns if http method is a post or not.
     *
     * @return bool
     */
    protected function isPostBack()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }
}
