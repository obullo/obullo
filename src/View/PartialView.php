<?php

namespace Obullo\View;

use Obullo\View\ViewModel;
use Laminas\View\Model\ModelInterface;
use Psr\Http\Message\ResponseInterface as Response;

class PartialView extends AbstractView
{
    /**
     * Initialize view model
     */
    public function init()
    {
        $this->view = new ViewModel;
    }

    /**
     * Render view
     *
     * @param  ModelInterface $model model
     * @return string
     */
    public function render(ModelInterface $model)
    {
        $defaultTemplate = $this->view->getTemplate();
        $class = get_class($this);
        $class = str_replace('\\', '/', $class);
        $templateName = substr($class, 0, -5); // Remove "Model" word from end

        $model->setOption('has_parent', true);
        if ($defaultTemplate == '') {
            $this->view->setTemplate($templateName);
        }
        return $this->getView()->render($model);
    }
}
