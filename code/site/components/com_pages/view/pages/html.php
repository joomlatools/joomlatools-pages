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
    public function getPage()
    {
        $path = $this->getModel()->getState()->path;
        $page = $this->getObject('page.registry')->getPage($path);

        return $page;
    }

    public function getTitle()
    {
        $page = $this->getPage();

        return $page->title ? $page->title :  parent::getTitle();
    }

    public function getMetadata()
    {
        $page     = $this->getPage();
        $metadata = (array) $page->metadata;

        //Set the description into the metadata if it doesn't exist.
        if($page->summary && !isset($metadata['description'])) {
            $metadata['description'] = $page->summary;
        }

        return $metadata;
    }

    public function getLayout()
    {
        $path = $this->getModel()->getState()->path;
        return 'page://pages/'.$path;
    }

    public function getRoute($route = '', $fqr = true, $escape = true)
    {
        $query = array();
        $path  = $this->getModel()->getState()->path;
        if($route instanceof KModelEntityInterface)
        {
            $query = array();
            $query['path'] = $path;

            if($collection = $this->getPage()->isCollection())
            {
                if($collection['route'])
                {
                    $parts    = explode('/', $collection['route']);
                    $segments = array();
                    foreach($parts as $key => $name)
                    {
                        if($name[0] == ':') {
                            $segments[] = $route->getProperty(ltrim($name, ':'));
                        } else {
                            $segments[] = $name;
                        }
                    }

                    $query['route'] = implode('/', $segments);
                }
            }
        }
        else if(is_array($route))
        {
            $query = $route;

            if($collection = $this->getPage()->isCollection())
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
        else $query['path'] = $route;

        return parent::getRoute($query, $fqr, $escape);
    }

}