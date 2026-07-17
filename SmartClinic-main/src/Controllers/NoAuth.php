<?php
namespace Controllers;
class NoAuth extends PublicController
{
    public function run() :void
    {
        if (\Utilities\Security::isLogged()) {
            \Utilities\Site::redirectTo("index.php");
        } else {
            \Utilities\Site::redirectTo("index.php?page=Sec_Login");
        }
    }
}
?>