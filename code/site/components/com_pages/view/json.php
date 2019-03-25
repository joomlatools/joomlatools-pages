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
        if(!is_null($path)) {
            $result = $this->getObject('com:pages.model.factory')->createPage($path);
        } else {
            $result = $this->getModel()->getPage();
        }

        return $result;
    }

    public function getCollection($source = '', $state = array())
    {
        if($source) {
            $result = $this->getObject('com:pages.model.factory')->createCollection($source, $state)->fetch();
        } else {
            $result = $this->getModel()->fetch();
        }

        return $result;
    }

    public function getRoute($page, $query = array(), $escape = false)
    {
        if(!is_array($query)) {
            $query = array();
        }

        if($route = $this->getObject('dispatcher')->getRouter()->generate($page, $query)) {
            $route->setEscape($escape)->toString(KHttpUrl::FULL);
        }

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