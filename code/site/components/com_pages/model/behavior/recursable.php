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
            $key      = $this->getConfig()->key ?? $entities->getIdentityKey();

            //Filter children
            foreach ($entities as $entity)
            {
                //Mixin the behavior
                $entity->mixin($this);

                //Get the handle for the parent
                $parent = $entity->getHandle();

                if($parent && $children = $entities->find([$key => $parent]))
                {
                    foreach($children as $child)
                    {
                        //Store the nodes by parent
                        $this->__children[$parent][] = $child;

                        $context->entity->remove($child);
                    }
                }
            }

            //Mixin the behavior
            $context->entity->mixin($this);
        }
    }
}