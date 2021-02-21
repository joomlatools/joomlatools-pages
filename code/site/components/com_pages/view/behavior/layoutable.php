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
        if($layout = $context->subject->getLayout())
        {
            //Merge the layout
            $mergeLayout = function($context, $layout) use(&$mergeLayout)
            {
                $template = $this->getTemplate()->loadFile($layout->path);

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
                $context->subject->getLayout()->append($template->getData());

                //Handle recursive layout
                if($layout = $template->getLayout()) {
                    $mergeLayout($context, $layout);
                }
            };

            Closure::bind($mergeLayout, $this, get_class());
            $mergeLayout($context, $layout);
        }
    }

    protected function _afterRender(KViewContext $context)
    {
        if($layout = $context->subject->getLayout())
        {
            //Render the layout
            $renderLayout = function($context, $layout) use(&$renderLayout)
            {
                $template = $this->getTemplate()
                    ->setParameters($context->parameters)
                    ->loadFile($layout->path);

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
                //$context->subject->getLayout()->append($template->getData());

                //Merge the page layout data
                //
                //Allow the layout data to be modified during template rendering
                $context->data->merge($context->subject->getLayout());

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