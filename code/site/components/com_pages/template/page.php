<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplatePage extends ComPagesTemplateLayout
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'functions' => [
                'route' => [$this, 'createRoute'],
            ],
        ));

        parent::_initialize($config);
    }

    public function loadString($source, $type = null, $url = null)
    {
        if(parse_url($url, PHP_URL_SCHEME) == 'page')
        {
            ////Add filters
            if(isset($this->_data['process']['filters']))
            {
                if($filters = (array) $this->_data['process']['filters'])
                {
                    foreach (KObjectConfig::unbox($filters) as $key => $value)
                    {
                        if (is_numeric($key)) {
                            $this->addFilter($value);
                        } else {
                            $this->addFilter($key, $value);
                        }
                    }
                }
            }
        }

        return parent::loadString($source, $type, $url);
    }

    public function createRoute($route)
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

        return 'route://'.http_build_query($query, '', '&');
    }

    public function render(array $data = array())
    {
        unset($data['layout']);

        return parent::render($data);
    }
}