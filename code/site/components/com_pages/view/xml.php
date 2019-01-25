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
        ]);

        parent::_initialize($config);
    }

    protected function _actionRender(KViewContext $context)
    {
        //Prepend the xml prolog
        $result  = '<?xml version="1.0" encoding="utf-8" ?>';
        $result .=  parent::_actionRender($context);

        return $result;
    }

    public function getLayout()
    {
        if($layout = $this->getPage()->layout) {
            $layout = $layout->path;
        }

        return $layout;
    }

    public function getLayoutData()
    {
        $data = array();
        if($layout = $this->getPage()->layout)
        {
            unset($layout->path);
            $data = $layout;
        }

        return $data;
    }

    public function getPage($path = null)
    {
        $result   = array();
        $registry = $this->getObject('page.registry');

        if (!is_null($path))
        {
            if ($data = $registry->getPage($path)) {
                $result = $this->getObject('com:pages.model.pages')->create($data->toArray());
            }

        }
        else $result = $this->getModel()->getPage();

        return $result;
    }

    public function getRoute($page = '', $query = array(), $escape = false)
    {
        if($page instanceof KModelEntityInterface) {
            $page = $page->route;
        }

        if(!is_array($query)) {
            $query = array();
        }

        //Add the model state only for routes to the same page
        if($page == $this->getPage()->route)
        {
            if($collection = $this->getPage($page)->collection)
            {
                $states = array();
                foreach ($this->getModel()->getState() as $name => $state)
                {
                    if ($state->default != $state->value && !$state->internal) {
                        $states[$name] = $state->value;
                    }
                }

                $query = array_merge($states, $query);
            }
        }

        $route = $this->getObject('dispatcher')->getRouter()
            ->generate($page, $query)
            ->setEscape($escape)
            ->toString(KHttpUrl::FULL);

        return $route;
    }
}