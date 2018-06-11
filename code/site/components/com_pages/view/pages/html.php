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

        if($route instanceof KModelEntityInterface)
        {
            $query['path'] = $route->path;
            $query['slug'] = $route->slug;
        }
        else if(is_string($route))
        {
            if(strpos($route, '=')) {
                parse_str(trim($route), $query);
            } else {
                $query['path'] = $route;
            }
        }
        else $query = $route;

        //Add add if the query is not unique
        if(!$query['slug'])
        {
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

        return parent::getRoute($query, $fqr, $escape);
    }

}