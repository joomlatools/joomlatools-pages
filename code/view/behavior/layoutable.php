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
                //Qualify the layout path
                if(!parse_url($layout, PHP_URL_SCHEME)) {
                    $layout = 'template:layouts/'.$layout;
                }

                //Locate the layout
                if(!$file = $this->getObject('template.locator.factory')->locate($layout)) {
                    throw new RuntimeException(sprintf('Cannot find layout: "%s"', $layout));
                }

                //Load the template
                $template = (new ComPagesObjectConfigFrontmatter())->fromFile($file);

                //Set the parent layout
                if($layout = $template->get('layout'))
                {
                    if(!is_string($layout)) {
                        $layout = $layout->path;
                    }
                }

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
                $context->subject->getLayout()->append($template->remove('layout'));

                //Handle recursive layout
                if($layout) {
                    $mergeLayout($context, $layout);
                }
            };

            Closure::bind($mergeLayout, $this, get_class());
            $mergeLayout($context, $layout->path);

            //Merge the process (excluding the filters)
            if($process =  $context->subject->getLayout()->get('process'))
            {
                $process = clone $process;

                $context->subject->getPage()->process->append($process->remove('filters'));
                //$context->subject->getLayout()->remove('process');
            }
        }
    }

    protected function _afterRender(KViewContext $context)
    {
        if($layout = $context->subject->getLayout())
        {
            //Render the layout
            $renderLayout = function($context, $layout) use(&$renderLayout)
            {
                $template = clone $this->getTemplate();

                //Qualify the layout path
                if(!parse_url($layout->path, PHP_URL_SCHEME)) {
                    $layout->path = 'template:layouts/'.$layout->path;
                }

                //Load layout
                $template->loadFile($layout->path);

                //Parse and disable filters
                if($process = $template->get('process'))
                {
                    $filters = $process['filters'] ?? array();

                    foreach($filters as $key => $filter)
                    {
                        unset($filters[$key]);

                        if (is_array($filter))
                        {
                            $config = current($filter);
                            $filter  = key($filter);
                        }
                        else $config = array();

                        if ($filter[0] == '-')
                        {
                            $config['enabled'] = false;
                            $template->addFilter(substr($filter, 1), $config);
                        }
                        else $filters[$filter] = $config;
                    }

                    $template->addFilters($filters);
                }

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