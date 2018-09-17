<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesHtml extends ComPagesViewHtml
{
    protected function _fetchData(KViewContext $context)
    {
        parent::_fetchData($context);

        //Auto-assign the data from the model
        if($this->_auto_fetch)
        {
            $model = $this->getModel();

            //Set the pages entity
            $context->data->pages = $model->fetch();

            //Set the parameters
            $context->parameters->total = $model->count();
        }
    }

    protected function _actionRender(KViewContext $context)
    {
        $data       = $context->data;
        $layout     = $context->layout;
        $parameters = $context->parameters;

        //Render the page
        $template = $this->getObject('com:pages.template.page')
            ->setParameters($parameters)
            ->loadFile('page://pages/'.$context->layout);

        $data->append($template->getData());

        $this->setContent($template->render(KObjectConfig::unbox($data)));

        //Setup the layout
        $context->layout     = $template->getParent();
        $context->data->page = $this->getPage();

        return parent::_actionRender($context);
    }
}