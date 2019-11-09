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
    private $__collections;

    public function createPage($path)
    {
        $entity = false;

        if ($page = $this->getObject('page.registry')->getPage($path))
        {
            $entity = $this->getObject('com://site/pages.model.entity.page',
                array('data'  => $page->toArray())
            );
        }

        return $entity;
    }

    public function createCollection($name, $state = array())
    {
        $model = null;

        if($collection = $this->getObject('page.registry')->getCollection($name))
        {
            //Create the model
            $model = KHttpUrl::fromString($collection->model);

            $name   = $model->toString(KHttpUrl::BASE);
            $config = $model->query;

            if(is_string($name) && strpos($name, '.') === false )
            {
                $identifier = $this->getIdentifier()->toArray();
                $identifier['name'] = $name;
            }
            else $identifier = $name;

            //Set the type
            if($collection->has('type')) {
                $config['type'] = $collection->type;
            }

            $model = $this->getObject($identifier, $config);

            if(!$model instanceof KModelInterface && !$model instanceof KControllerModellable)
            {
                throw new UnexpectedValueException(
                    'Collection: '.get_class($model).' does not implement KModelInterface or KControllerModellable'
                );
            }

            if($model instanceof KControllerModellable) {
                $model = $this->getObject('com://site/pages.model.controller', ['controller' => $model]);
            }

            //Add model filters for unique fields
            if($collection->has('fields'))
            {
                $fields = (array) KObjectConfig::unbox($collection->fields);

                foreach($fields as $field => $filters)
                {
                    if(in_array('unique', $filters))
                    {
                        $filters = array_diff($filters, ['unique', 'required']);

                        //Do not add a filter if it already exists
                        if(!$model->getState()->has($field)) {
                            $model->getState()->insert($field, $filters, null, true);
                        }
                    }
                }
            }

            //Set the model state
            if(isset($collection->state)) {
                $state = KObjectConfig::unbox($collection->state->merge($state));
            }

            $model->setState($state);

            //Store the collection
            $this->__collections[] = $model;
        }

        return $model;
    }

    public function getCollections()
    {
        return $this->__collections;
    }
}