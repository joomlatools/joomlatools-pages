<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewHtml extends ComKoowaViewPageHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'decorator' => 'joomla',
            'template'  => 'layout',
            'behaviors' => ['routable'],
        ]);

        parent::_initialize($config);
    }

    public function getPage()
    {
        $registry = $this->getObject('page.registry');
        $state    = $this->getModel()->getState();

        if ($state->isUnique()) {
            $page = $registry->getPage($state->path.'/'.$state->slug);
        } else {
            $page = $registry->getPage($state->path);
        }

        return $page;
    }

    public function getTitle()
    {
        $result = '';
        if($page = $this->getPage()) {
            $result = $page->title ? $page->title :  '';
        }

        return $result;
    }

    public function getMetadata()
    {
        $metadata = array();
        if($data = $this->getPage())
        {
            if(isset($data->metadata)) {
                $metadata = KObjectConfig::unbox($data->metadata);
            }

            //Set the description into the metadata if it doesn't exist.
            if(!empty($data->summary) && !isset($data->metadata->description)) {
                $metadata['description'] = $data->summary;
            }
        }

        return $metadata;
    }

    protected function _fetchData(KViewContext $context)
    {
        $model = $this->getModel();

        $context->parameters = $model->getState()->getValues();

        //Auto-assign the data from the model
        if($this->_auto_fetch)
        {
            $data = $this->getPage();

            //Remove the content
            unset($data['content']);

            //Remove the state
            foreach($context->parameters as $name => $value) {
                unset($data[$name]);
            }

            //Set the page properties
            $context->data->append($this->getPage());

            //Set the parameters
            if($this->isCollection())
            {
                //Set the data
                $context->data->pages = $model->fetch();
                $context->parameters->total = $model->count();
            }
            else $context->parameters->total = 1;
        }
    }

    protected function _actionRender(KViewContext $context)
    {
        $data       = $context->data;
        $layout     = $context->layout;
        $parameters = $context->parameters;

        //Render the layout
        $renderLayout = function($layout, $data, $parameters) use(&$renderLayout)
        {
            $template = $this->getTemplate()
                ->setParameters($parameters)
                ->loadFile($layout);

            //Merge the data
            $data->append($template->getData());

            //Render the template
            $this->setContent($template->render(KObjectConfig::unbox($data)));

            //Handle recursive layout
            if($layout = $template->getParent()) {
                $renderLayout($layout, $data, $parameters);
            }
        };

        Closure::bind($renderLayout, $this, get_class());
        $renderLayout($layout, $data, $parameters);

        return KViewAbstract::_actionRender($context);
    }

    public function getRoute($route = '', $fqr = true, $escape = true)
    {
        //Parse route
        $query = array();

        if(is_string($route))
        {
            if(strpos($route, '=')) {
                parse_str(trim($route), $query);
            } else {
                $query['path'] = $route;
            }
        }
        else
        {
            if($route instanceof KModelEntityInterface)
            {
                $query['path'] = $route->path;
                $query['slug'] = $route->slug;
            }
            else $query = $route;
        }

        //Add add if the query is not unique
        if(!isset($query['slug']))
        {
            if($collection = $this->getPage()->collection)
            {
                $states = array();
                foreach ($this->getModel()->getState() as $name => $state)
                {
                    if ($state->default != $state->value && !$state->internal) {
                        $states[$name] = $state->value;
                    }

                    $query = array_merge($states, $query);
                }
            }
        }


        //Create the route
        $route = $this->getObject('lib:dispatcher.router.route', array('escape' =>  $escape))->setQuery($query);

        //Add host, schema and port for fully qualified routes
        if ($fqr === true)
        {
            $route->scheme = $this->getUrl()->scheme;
            $route->host   = $this->getUrl()->host;
            $route->port   = $this->getUrl()->port;
        }

        return $route;
    }
}