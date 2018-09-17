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
    protected $_page;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'decorator' => 'joomla',
            'template'  => 'layout',
            'behaviors' => ['routable'],
        ]);

        parent::_initialize($config);
    }

    public function getLayout()
    {
        if($layout = $this->getPage()->layout) {
            $layout = $layout->path;
        }

        return $layout;
    }

    public function getLayoutData()
    {
        $data = array();
        if($layout = $this->getPage()->layout)
        {
            unset($layout->path);
            $data = $layout;
        }

        return $data;
    }

    public function getPage()
    {
        if(!isset($this->_page))
        {
            $registry = $this->getObject('page.registry');
            $state    = $this->getModel()->getState();

            if ($state->isUnique()) {
                $data = $registry->getPage($state->path.'/'.$state->slug);
            } else {
                $data = $registry->getPage($state->path);
            }

            $this->_page = $this->getObject('com:pages.model.pages')->create($data->toArray());
        }

        return $this->_page;
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
        $context->parameters = $this->getModel()->getState()->getValues();
    }

    protected function _actionRender(KViewContext $context)
    {
        if($layout = $context->layout)
        {
            $data       = $context->data;
            $parameters = $context->parameters;

            //Set the layout data
            $data->layout = $this->getLayoutData();

            //Render the layout
            $renderLayout = function($layout, $data, $parameters) use(&$renderLayout)
            {
                $template = $this->getTemplate()
                    ->setParameters($parameters)
                    ->loadFile($layout);

                //Merge the page layout data
                //
                //Allow the layout data to be modified during template rendering
                $data->merge($this->getLayoutData());

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
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
        }

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