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

    private $__metadata;

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
                'canonical'   => [$this, 'getCanonical'],
                'metadata'    => [$this, 'getMetadata'],
            ],
        ]);

        parent::_initialize($config);
    }

    public function getLayout()
    {
        return $this->getPage()->get('layout');
    }

    public function getCanonical()
    {
        $canonical = '';
        if($page = $this->getPage())
        {
            $canonical = $page->canonical ?: $this->getRoute();

            if($page->isCollection() && $this->getState()->isUnique()) {
                $canonical = $this->getCollection()->get('canonical', $canonical);
            }

            $canonical = $this->getUrl($this->getRoute($canonical));
        }

        return $canonical;
    }

    public function getMetadata()
    {
        $metadata = array();
        if(!isset($this->__metadata))
        {
            $page = $this->getPage();

            if($page && $page->metadata)
            {
                if($page->isCollection() && $this->getState()->isUnique())
                {
                    if($metadata = $this->getCollection()->metadata) {
                        $metadata->append($page->metadata);
                    } else {
                        $metadata = $page->metadata;
                    }
                }
                else $metadata = $page->metadata;

                $metadata = KObjectConfig::unbox($metadata);

                if(isset($metadata['og:type']) && $metadata['og:type'])
                {
                    if (strpos($metadata['og:image'], 'http') === false)
                    {
                        $this->getTemplate()->getFilter('asset')->filter($metadata['og:image']);
                        $metadata['og:image'] = (string)$this->getUrl($metadata['og:image']);
                    }

                    if (!$metadata['og:url']) {
                        $metadata['og:url'] = (string)$this->getRoute($page);
                    }

                    if (strpos($metadata['og:url'], 'http') === false) {
                        $metadata['og:url'] = (string)$this->getUrl($metadata['og:url']);
                    }
                }
            }

            $this->__metadata = new ComPagesObjectConfig($metadata);
        }

        return $this->__metadata;
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