<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelBehaviorCollectable extends KModelBehaviorAbstract
{
    public function getCollection()
    {
        $collection = $this->getObject('com:pages.model.pages')
            ->path($this->path)
            ->fetch();

        return $collection;
    }

    public function getMixableMethods($exclude = array())
    {
        $methods = array();
        if($this->getMixer() instanceof KModelEntityInterface) {
            $methods = parent::getMixableMethods($exclude);
        }

        return $methods;
    }

    public function isSupported()
    {
        $mixer = $this->getMixer();

        if($mixer instanceof ComPagesModelPages)
        {
            if($mixer->getState()->isUnique()) {
                return false;
            }
        }

        return true;
    }

    protected function _afterFetch(KModelContext $context)
    {
        $pages = KObjectConfig::unbox($context->entity);

        if ($pages instanceof KModelEntityInterface)
        {
            foreach ($pages as $key => $page)
            {
                //Force mixin the behavior into each page
                if($page->isCollection()) {
                    $page->mixin($this);
                }
            }
        }
    }
}