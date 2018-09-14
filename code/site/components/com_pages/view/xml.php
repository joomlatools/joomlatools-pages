<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewXml extends KViewTemplate
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'mimetype'   => 'text/xml',
            'auto_fetch' => false,
            'template'   => 'layout',
            'behaviors'  => ['routable'],
        ]);

        parent::_initialize($config);
    }

    public function getPage()
    {
        $registry = $this->getObject('page.registry');
        $state    = $this->getModel()->getState();

        if ($state->isUnique()) {
            $page = $registry->getPage($state->path.'/'.$state->slug);
        } else {
            $page = $registry->getPage($state->path);
        }

        return $page;
    }

    public function getRoute($route = '', $fqr = true, $escape = true)
    {
        //Parse route
        $query = array();

        if(is_string($route))
        {
            if(strpos($route, '=')) {
                parse_str(trim($route), $query);
            } else {
                $query['path'] = $route;
            }
        }
        else
        {
            if($route instanceof KModelEntityInterface)
            {
                $query['path'] = $route->path;
                $query['slug'] = $route->slug;
            }
            else $query = $route;
        }

        if(!$query['slug'])
        {
            if($collection = $this->getPage()->collection)
            {
                $states = array();
                foreach ($this->getModel()->getState() as $name => $state)
                {
                    if ($state->default != $state->value && !$state->internal) {
                        $states[$name] = $state->value;
                    }

                    $query = array_merge($states, $query);
                }
            }
        }

        //Create the route
        $route = $this->getObject('lib:dispatcher.router.route', array('escape' =>  $escape))->setQuery($query);

        //Add host, schema and port for fully qualified routes
        if ($fqr === true)
        {
            $route->scheme = $this->getUrl()->scheme;
            $route->host   = $this->getUrl()->host;
            $route->port   = $this->getUrl()->port;
        }

        return $route;
    }


    protected function _actionRender(KViewContext $context)
    {
        //Prepend the xml prolog
        $result  = '<?xml version="1.0" encoding="utf-8" ?>';
        $result .=  parent::_actionRender($context);

        return $result;
    }
}