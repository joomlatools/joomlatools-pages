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
    private $__state;

    public function hasCollection()
    {
       return $this->isCollection();
    }

    public function getCollection()
    {
        $result = array();
        if($this->hasCollection())
        {
            $state = $this->__state;

            //Remove states
            unset($state->offset);
            unset($state->limit);
            unset($state->path);
            unset($state->page);

            $result = $this->getObject('com:pages.model.pages')
                ->setState($state->getValues())
                ->path($this->path)
                ->fetch();
        }

        return $result;
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
            $this->__state = $context->getSubject()->getState();

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