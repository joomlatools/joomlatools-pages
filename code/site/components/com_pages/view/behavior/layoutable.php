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
        //Register template functions
        $template = $context->subject->getTemplate();

        //Create template (add parameters BEFORE cloning)
        $template = clone $template->setParameters($context->parameters);

        //Add the filters
        if($process = $template->get('process') && isset($process['filters'])) {
            $template->addFilters((array) $process['filters']);
        }

        //Load the page
        $template->loadFile($context->subject->getLayout());

        //Render page
        $content = $template->render(KObjectConfig::unbox($context->data->append($template->getData())));

        //Set the rendered page in the view to allow for view decoration
        $this->setContent($content);

        //Set the template in the context
        $context->template = $template;

    }

    protected function _afterRender(KViewContext $context)
    {
        if($layout = $context->template->getLayout())
        {
            //Render the layout
            $renderLayout = function($context, $layout) use(&$renderLayout)
            {
                $template = $this->getTemplate()
                    ->setParameters($context->parameters)
                    ->loadFile($layout);

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
                $context->template->getLayoutData()->append($template->getData());

                //Merge the page layout data
                //
                //Allow the layout data to be modified during template rendering
                $context->data->merge($context->template->getLayoutData());

                //Render the template
                $this->setContent($template->render(KObjectConfig::unbox($context->data)));

                //Handle recursive layout
                if($layout = $template->getLayout()) {
                    $renderLayout($context, $layout);
                }
            };

            Closure::bind($renderLayout, $this, get_class());
            $renderLayout($context, $layout);
        }

        $context->result = $this->getContent();
    }

    public function isSupported()
    {
        return $this->getMixer() instanceof KViewTemplate;
    }
}