<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesXml extends ComPagesViewXml
{
    protected function _fetchData(KViewContext $context)
    {
        $context->data->append(array(
            'pages'  => $this->getModel()->limit(0)->fetch(),
            'total'  => $this->getModel()->count(),
        ));

        parent::_fetchData($context);
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
}