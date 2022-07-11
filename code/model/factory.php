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
    private $__models;

    public function createModel($name, $state = [], $filter = [], $replace = true)
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

            if(!$model instanceof KControllerModellable && !$model instanceof KModelInterface)
            {
                throw new UnexpectedValueException(
                    'Collection: '.get_class($model).' does not implement KModelInterface or KControllerModellable'
                );
            }

            if($model instanceof KControllerModellable || !$model instanceof ComPagesModelInterface) {
                $model = $this->getObject('com:pages.model.decorator', ['delegate' => $model]);
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

            //Set the model filter
            if(isset($collection->filter))
            {
                $default_filter = (array) KObjectConfig::unbox($collection->filter);

                if($replace) {
                    $filter = array_replace_recursive($default_filter, $filter);
                } else {
                    $filter = array_replace_recursive($filter, $default_filter);
                }

                $state['filter'] = $filter;
            }

            $model->setState($state);

            //Store the collection
            $hash = hash('crc32b', $name.serialize($model->getHashState()));
            $this->__models[$hash] = $model;
        }
        else throw new UnexpectedValueException('Collection: '.$name.' cannot be found');

        return $model;
    }

    public function getModels()
    {
        return (array) $this->__models;
    }
}