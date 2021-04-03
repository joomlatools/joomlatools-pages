<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewHtml extends ComKoowaViewHtml
{
    use ComPagesViewTraitPage, ComPagesViewTraitUrl, ComPagesViewTraitRoute;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors'   => ['layoutable'],
            'auto_fetch'  => false,
            'template_filters'   => ['asset', 'meta'],
            'template_functions' => [
                'page'        => [$this, 'getPage'],
                'layout'      => [$this, 'getLayout'],
                'collection'  => [$this, 'getCollection'],
                'state'       => [$this, 'getState'],
                'direction'   => [$this, 'getDirection'],
                'language'    => [$this, 'getLanguage'],
            ],
        ]);

        parent::_initialize($config);
    }

    public function getLayout()
    {
        return $this->getPage()->get('layout');
    }

    protected function _actionRender(KViewContext $context)
    {
        //Disable filters
        if($filters = $this->getPage()->get('process/filters'))
        {
            $filters = (array) KObjectConfig::unbox($filters);

            //Disable filters
            foreach ($filters as $key => $value)
            {
                if (is_numeric($key))
                {
                    if ($value[0] == '-') {
                        $this->getTemplate()->addFilter(substr($value, 1), ['enabled' => false]);
                        unset($filters[$key]);
                    }
                }
                else
                {
                    if ($key[0] == '-') {
                        $this->getTemplate()->addFilter(substr($key, 1), $value['enabled'] = false);
                        unset($filters[$key]);
                    }
                }
            }
        }

        $template = clone $this->getTemplate()->setParameters($context->parameters);

        //Add page filters
        if($filters) {
            $template->addFilters($filters);
        }

        //Load the page
        $template->loadFile('page://pages/'.$this->getPage()->path);

        //Render page
        $content = $template->render(KObjectConfig::unbox($context->data->append($template->getData())));

        //Set the rendered page in the view to allow for view decoration
        $this->setContent($content);

        //Set the content in the object
        $this->getPage()->content = $content;

        return trim($content);
    }

    protected function _fetchData(KViewContext $context)
    {
        $context->parameters = $this->getState()->getValues();
    }
}