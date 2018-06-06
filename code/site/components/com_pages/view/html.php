<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewHtml extends ComKoowaViewPageHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'template' => 'layout',
        ));

        parent::_initialize($config);
    }

    protected function _actionRender(KViewContext $context)
    {
        $data       = $context->data;
        $layout     = $context->layout;
        $parameters = $context->parameters;

        //Render the layout
        $renderLayout = function($layout, $data, $parameters) use(&$renderLayout)
        {
            $template = $this->getTemplate()
                ->loadFile($layout)
                ->setParameters($parameters);

            //Merge the data
            $data->append($template->getData());

            //Render the template
            $this->_content = $template->render(KObjectConfig::unbox($data));

            //Handle recursive layout
            if($layout = $template->getParent()) {
                $renderLayout($layout, $data, $parameters);
            }
        };

        Closure::bind($renderLayout, $this, get_class());
        $renderLayout($layout, $data, $parameters);

        return KViewAbstract::_actionRender($context);
    }
}