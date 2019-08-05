<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewBehaviorPageable extends KViewBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'template_functions' => [
                'page'        => [$this, 'getPage'],
                'collection'  => [$this, 'getCollection'],
                'state'       => [$this, 'getState'],
                'direction'   => [$this, 'getDirection'],
                'language'    => [$this, 'getLanguage'],
            ],
        ]);

        parent::_initialize($config);
    }

    protected function _beforeRender(KViewContext $context)
    {
        //Register template functions
        $template  = $context->subject->getTemplate();
        $functions = KObjectConfig::unbox($this->getConfig()->template_functions);

        foreach($functions as $name => $function) {
            $template->registerFunction($name, $function);
        }

        //Render the page
        if($page = $this->getPage())
        {
            //Create template (add parameters BEFORE cloning)
            $template = clone $template->setParameters($context->parameters);

            //Add the filters
            $template->addFilters($page->process->filters);

            //Load the page
            $template->loadFile('page://pages/'.$page->path);

            //Render page
            $content = $template->render(KObjectConfig::unbox($context->data->append($template->getData())));

            //Set the content in the object
            $this->getPage()->content = $content;

            //Set the rendered page in the view to allow for view decoration
            $this->setContent($content);
        }
    }

    public function getPage($path = null)
    {
        if(!is_null($path)) {
            $result = $this->getObject('com:pages.model.factory')->createPage($path);
        } else {
            $result = $this->getModel()->getPage();
        }

        return $result;
    }

    public function getCollection($model = '', $state = array())
    {
        if($model) {
            $result = $this->getObject('com:pages.model.factory')->createCollection($model, $state)->fetch();
        } else {
            $result = $this->getModel()->fetch();
        }

        return $result;
    }


    public function getState()
    {
        return $this->getModel()->getState();
    }

    public function getDirection()
    {
        $result = 'auto';
        if($page = $this->getPage()) {
            $result = $page->direction ?: 'auto';
        }

        return $result;
    }

    public function getLanguage()
    {
        $result = 'en-GB';
        if($page = $this->getPage()) {
            $result = $page->language ?: 'en-GB';
        }

        return $result;
    }

    public function isSupported()
    {
        return $this->getMixer() instanceof KViewTemplate;
    }
}