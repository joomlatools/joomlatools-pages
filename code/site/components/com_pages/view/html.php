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
            'template_filters' => ['asset'], //Redefine asset to run before the script filter
            'template_functions' => [
                'page'       => [$this, 'getPage'],
                'pages'      => [$this, 'getPages'],
            ],
        ]);

        parent::_initialize($config);
    }

    protected function _actionRender(KViewContext $context)
    {
        $data       = $context->data;
        $parameters = $context->parameters;

        //Render the page if it hasn't been rendered yet
        if(empty($this->getPage()->content))
        {
            //Create template (add parameters BEFORE cloning)
            $page = clone $this->getTemplate()->setParameters($parameters);
            $page->addFilters($this->getPage()->process->filters)
                ->loadFile('page://pages/'.$this->getPage()->route);

            //Render page
            $content = $page->render(KObjectConfig::unbox($data->append($page->getData())));
            $this->getPage()->content = $content;
        }
        else $content = $this->getPage()->content;

        //Set the rendered page in the view to allow for view decoration
        $this->setContent($content);

        if($layout = $this->getLayout())
        {
            //Render the layout
            $renderLayout = function($layout, $data, $parameters) use(&$renderLayout)
            {
                $template = $this->getTemplate()
                    ->setParameters($parameters)
                    ->loadFile($layout);

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
                $this->getLayoutData()->append($template->getData());

                //Merge the page layout data
                //
                //Allow the layout data to be modified during template rendering
                $data->merge($this->getLayoutData());

                //Render the template
                $this->setContent($template->render(KObjectConfig::unbox($data)));

                //Handle recursive layout
                if($layout = $template->getLayout()) {
                    $renderLayout($layout, $data, $parameters);
                }
            };

            Closure::bind($renderLayout, $this, get_class());
            $renderLayout($layout, $data, $parameters);
        }

        return KViewAbstract::_actionRender($context);
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

    public function getPage($path = null)
    {
        $result   = array();
        $registry = $this->getObject('page.registry');

        if (is_null($path))
        {
            if (!isset($this->_page))
            {
                $state = $this->getModel()->getState();

                if (!$state->isUnique())
                {
                    $data = $registry->getPage($state->path);
                    $page = $this->getObject('com:pages.model.pages')->create($data->toArray());

                    $this->_page = $page;
                }
                else $this->_page = $this->getModel()->fetch();
            }

            $result = $this->_page;
        }
        else
        {
            if ($data = $registry->getPage($path)) {
                $result = $this->getObject('com:pages.model.pages')->create($data->toArray());
            }
        }

        return $result;
    }

    public function getPages($path = '.', $state = array())
    {
        $result = array();

        if ($path && $this->getObject('page.registry')->isPage($path))
        {
            if(is_string($state)) {
                $state = json_decode('{'.preg_replace('/(\w+)/', '"$1"', $state).'}', true);
            }

            $result = $this->getObject('com:pages.model.pages')
                ->setState($state)
                ->path($path)
                ->fetch();
        }

        return $result;
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

        //Build the route
        return $this->getObject('com:pages.dispatcher.router.route',  array('escape'  => $escape))->build($query);
    }
}