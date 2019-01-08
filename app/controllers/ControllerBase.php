<?php

class ControllerBase extends \Phalcon\Mvc\Controller
{

    /**
     * Така собі ініціалізація
     */
    public function initialize()
    {
        $this->assets->addCss("css/bootstrap.min.css");
        $this->assets->addCss("css/additional.styles.css");
    }
}