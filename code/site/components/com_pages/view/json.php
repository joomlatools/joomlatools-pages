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

    protected function _getEntity(KModelEntityInterface $entity)
    {
        return $entity->jsonSerialize();
    }

    protected function _getEntityRoute(KModelEntityInterface $entity)
    {
        return $this->getRoute($entity);
    }
}