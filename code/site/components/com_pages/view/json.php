<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewJson extends KViewJson
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'text_fields' => ['description', 'content'] // Links are converted to absolute ones in these fields
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
        else $query = $route;

        //Set the format
        $query['format'] = 'json';

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

        return $this->getObject('com:pages.dispatcher.router.route',  array('escape'  => $escape))->build($query);
    }

    protected function _getEntity(KModelEntityInterface $entity)
    {
        return $entity->jsonSerialize();
    }

    protected function _getEntityRoute(KModelEntityInterface $entity)
    {
        $query = array();
        $query['path'] = $entity->path;
        $query['slug'] = $entity->slug;

        return $this->getRoute($query);
    }
}