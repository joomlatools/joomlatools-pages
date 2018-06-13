<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesRss extends KViewRss
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'auto_fetch' => false
        ));

        parent::_initialize($config);
    }

    public function getPage()
    {
        $state = $this->getModel()->getState();
        if(!$state->isUnique())
        {
            $path = $state->path.'/'.$state->slug;
            $page = $this->getObject('page.registry')->getPage($path);
        }
        else $page =  $this->getModel()->fetch();

        return $page;
    }

    protected function _fetchData(KViewContext $context)
    {
        $context->data->append(array(
            'sitename'  => JFactory::getApplication()->getCfg('sitename'),
            'language'  => JFactory::getLanguage()->getTag(),
            'pages'     => $this->getModel()->fetch(),
            'total'     => $this->getModel()->count(),
            'description'  => $this->getPage()->summary ?: '',
            'image'        => $this->getPage()->image ?: ''
        ));

        parent::_fetchData($context);
    }

    public function getRoute($route = '', $fqr = true, $escape = false)
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
}