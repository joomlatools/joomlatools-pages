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
        if($route instanceof KModelEntityInterface)
        {
            $entity = $route;
            $route = 'path='.$this->getModel()->getState()->path;

            if($collection = $this->getPage()->isCollection())
            {
                if($collection['route'])
                {
                    $parts    = explode('/', $collection['route']);
                    $segments = array();
                    foreach($parts as $key => $name)
                    {
                        if($name[0] == ':') {
                            $segments[] = $entity->getProperty(ltrim($name, ':'));
                        } else {
                            $segments[] = $name;
                        }
                    }

                    $route = '&route='.implode('/', $segments);
                }
            }
        }

        return parent::getRoute($route, $fqr, $escape);
    }

}