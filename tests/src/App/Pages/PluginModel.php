<?php

namespace App\Pages;

use Obullo\View\View;
use Laminas\Diactoros\Response\HtmlResponse;

class PluginModel extends View
{
    public function onGet()
    {
        return new HtmlResponse($this->render($this->view));
    }
}
