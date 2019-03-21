<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelFactory extends KObject implements KObjectSingleton
{
    public function createPage($path)
    {
        $entity = false;

        if ($page = $this->getObject('page.registry')->getPage($path))
        {
            $entity = $this->getObject('com:pages.model.entity.page',
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

            if(!$model instanceof KModelInterface)
            {
                throw new UnexpectedValueException(
                    'Collection: '.get_class($model).' does not implement KModelInterface'
                );
            }

            //Set the state
            if(isset($collection->state)) {
                $state  = $collection->state->merge($state);
            }

            $model->setState(KObjectConfig::unbox($state));
        }

        return $model;
    }
}