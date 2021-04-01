<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorRecursable extends KModelBehaviorAbstract
{
    private $__children = array();
    private $__copy = null;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'priority' => self::PRIORITY_HIGH,
            'key'      => null,
        ]);

        parent::_initialize($config);
    }

    public function hasChildren()
    {
        $result = false;
        $parent = $this->getMixer()->getHandle();

        if(isset($this->__children[$parent])) {
            $result = (boolean) count($this->__children[$parent]);
        }

        return $result;
    }

    public function getChildren()
    {
        $result = array();

        if($this->hasChildren())
        {
            $parent = $this->getMixer()->getHandle();
            $result = $this->__children[$parent];
        }

        return $result;
    }

    public function getEntityCopy()
    {
        return $this->__copy;
    }

    public function getMixableMethods($exclude = array())
    {
        $methods = array();
        if($this->getMixer() instanceof KModelEntityInterface) {
            $methods = parent::getMixableMethods($exclude);
        }

        return $methods;
    }

    protected function _afterFetch(KModelContext $context)
    {
        $state = $context->state;

        if (!$state->isUnique() && $state->recurse && ($state->level == 0 || $state->level > 1))
        {
            $entities = clone $context->entity;
            $key      = $this->getConfig()->key ?? $context->getIdentityKey();

            //Filter children
            foreach ($entities as $entity)
            {
                //Mixin the behavior
                $entity->mixin($this);

                //Get the handle for the parent
                $parent = $entity->getHandle();

                if($parent && $children = $entities->find([$key => $parent]))
                {
                    //Store the nodes by parent
                    $this->__children[$parent] = $children;

                    foreach($children as $child) {
                        $context->entity->remove($child);
                    }
                }
            }

            //Store original entities
            $this->__copy = $entities;

            //Re-order entities
            $ordering = 0;
            $reorder = function($entities) use (&$reorder, &$ordering, $key)
            {
                foreach($entities as $entity)
                {
                    $entity->ordering = ++$ordering;

                    $this->__copy->find($entity->getHandle())->ordering = $entity->ordering;

                    if($entity->hasChildren()) {
                        $reorder($entity->getChildren());
                    }
                }
            };

            $reorder($context->entity);

            //Mixin the behavior
            $context->entity->mixin($this);
        }
    }
}