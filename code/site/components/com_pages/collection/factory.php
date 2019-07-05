<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

final class ComPagesCollectionFactory extends KObject implements KObjectSingleton
{
    public function createPage($path)
    {
        $entity = false;

        if ($page = $this->getObject('page.registry')->getPage($path))
        {
            $entity = $this->getObject('com:pages.collection.entity.page',
                array('data'  => $page->toArray())
            );
        }

        return $entity;
    }

    public function createCollection($source, $state = array())
    {
        $model = false;

        if($collection = $this->getObject('page.registry')->getCollection($source))
        {
            //Set the state
            if(isset($collection->state)) {
                $state = KObjectConfig::unbox($collection->state->merge($state));
            }

            //Create the model
            $source = KHttpUrl::fromString($collection->source);

            $model  = $source->toString(KHttpUrl::BASE);
            $config = $source->query;

            if(is_string($model) && strpos($model, '.') === false )
            {
                $identifier = $this->getIdentifier()->toArray();
                $identifier['name'] = $model;
            }
            else $identifier = $model;

            $model = $this->getObject($identifier, $config);

            if(!$model instanceof KModelInterface && !$model instanceof KControllerModellable)
            {
                throw new UnexpectedValueException(
                    'Collection: '.get_class($model).' does not implement KModelInterface or KControllerModellable'
                );
            }

            if($model instanceof KControllerModellable)
            {
                $model->getModel()->setState($state);
                $model = $this->getObject('com:pages.collection.controller', ['controller' => $model]);
            }
            else $model->setState($state);
        }

        return $model;
    }
}