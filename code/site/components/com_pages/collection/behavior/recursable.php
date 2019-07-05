<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesCollectionBehaviorRecursable extends ComPagesCollectionBehaviorAbstract
{
    private $__children = array();

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'priority' => self::PRIORITY_HIGH,
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
            $parent = $parent = $this->getMixer()->getHandle();
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
            $key      = $context->getIdentityKey();

            //Filter children
            foreach ($entities as $index => $entity)
            {
                //Mixin the behavior
                $entity->mixin($this);

                if($entities->find($entity->$key))
                {
                    //Get the parent
                    $parent = $entity->$key;

                    //Store the nodes by parent
                    $this->__children[$parent][$index] = $entity;

                    $context->entity->remove($entity);
                }
            }

            //Mixin the behavior
            $context->entity->mixin($this);
        }
    }
}