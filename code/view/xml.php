<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewXml extends KViewTemplate
{
    use ComPagesViewTraitPage, ComPagesViewTraitUrl, ComPagesViewTraitRoute;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'template'   => 'com:pages.template',
            'behaviors'  => ['layoutable'],
            'auto_fetch' => false,
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
        //Parse and disable filters
        if($filters = $this->getPage()->get('process/filters'))
        {
            $filters = (array) KObjectConfig::unbox($filters);

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
                    $this->getTemplate()->addFilter(substr($filter, 1), $config);
                }
                else $filters[$filter] = $config;
            }
        }

        $template = clone $this->getTemplate()->setParameters($context->parameters);

        //Add page filters
        if($filters) {
            $template->addFilters($filters);
        }

        //Load the page
        $template->loadLayout('page:'.$this->getPage()->path);

        //Render page
        $content  = '<?xml version="1.0" encoding="utf-8" ?>'."\n";
        $content = $template->render($template->getData());

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