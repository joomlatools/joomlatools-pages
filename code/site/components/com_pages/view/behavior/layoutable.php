<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewBehaviorLayoutable extends KViewBehaviorAbstract
{
    protected function _beforeRender(KViewContext $context)
    {
        //Render the page
        if($page = $this->getPage())
        {
            //Register template functions
            $template = $context->subject->getTemplate();


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

    protected function _afterRender(KViewContext $context)
    {
        $data       = $context->data;
        $parameters = $context->parameters;

        if($layout = $this->_getLayout())
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
                $this->_getLayoutData()->append($template->getData());

                //Merge the page layout data
                //
                //Allow the layout data to be modified during template rendering
                $data->merge($this->_getLayoutData());

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

        $context->result = $this->getContent();
    }

    protected function _getLayout()
    {
        if($layout = $this->getModel()->getPage()->layout) {
            $layout = $layout->path;
        }

        return $layout;
    }

    protected function _getLayoutData()
    {
        $data = array();
        if($layout = $this->getModel()->getPage()->layout)
        {
            unset($layout->path);
            $data = $layout;
        }

        return $data;
    }

    public function isSupported()
    {
        return $this->getMixer() instanceof KViewTemplate;
    }
}