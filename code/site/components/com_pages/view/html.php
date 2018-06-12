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
        $config->append([
            'template'  => 'layout',
            'behaviors' => ['routable'],
        ]);

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

    public function getRoute($route = '', $fqr = true, $escape = true)
    {
        //Parse route
        $parts = array();

        if(is_string($route)) {
            parse_str(trim($route), $parts);
        } else {
            $parts = $route;
        }

        //Create the route
        $route = $this->getObject('lib:dispatcher.router.route', array('escape' =>  $escape))->setQuery($parts);

        //Add host, schema and port for fully qualified routes
        if ($fqr === true)
        {
            $route->scheme = $this->getUrl()->scheme;
            $route->host   = $this->getUrl()->host;
            $route->port   = $this->getUrl()->port;
        }

        return $route;
    }
}