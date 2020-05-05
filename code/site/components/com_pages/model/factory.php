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

    public function createCollection($name, $state = array(), $replace = true)
    {
        $model = null;

        if($collection = $this->getObject('page.registry')->getCollection($name))
        {
            //Create the model
            $model = KHttpUrl::fromString($collection->model);

            $identifier = $model->toString(KHttpUrl::BASE);
            $config     = $model->query;

            if(is_string($identifier) && strpos($identifier, '.') === false )
            {
                $temp = $this->getIdentifier()->toArray();
                $temp['name'] = $identifier;

                $identifier = $temp;
            }

            //Set the name
            $config['name'] = $name;

            //Set the type
            if($collection->has('type')) {
                $config['type'] = $collection->type;
            }

            //Add additional config
            if($collection->has('config')) {
                $config = array_merge($config, KObjectConfig::unbox($collection->config));
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
            if($collection->has('schema'))
            {
                $schema = (array) KObjectConfig::unbox($collection->schema);

                foreach($schema as $field => $constraints)
                {
                    if(in_array('unique', $constraints))
                    {
                        $filters = array_diff($constraints, ['unique', 'required']);

                        //Do not add a filter if it already exists
                        if(!$model->getState()->has($field)) {
                            $model->getState()->insert($field, $filters, null, true);
                        }
                    }
                }
            }

            //Set the model state
            if(isset($collection->state))
            {
                //Remove states with 'null' values
                $default_state = KObjectConfig::unbox($collection->state);
                foreach($default_state as $k => $v)
                {
                    if(is_null($v)) {
                        unset($default_state[$k]);
                    }
                }

                if($replace) {
                    $state = array_replace_recursive($default_state, $state);
                } else {
                    $state = array_replace_recursive($state, $default_state);
                }
            }

            $model->setState($state);

            //Store the collection
            $this->__collections[$name] = $model;
        }
        else
        {
            throw new UnexpectedValueException(
                'Collection: '.get_class($name).' cannot be found'
            );
        }

        return $model;
    }

    public function getCollections()
    {
        return (array) $this->__collections;
    }
}