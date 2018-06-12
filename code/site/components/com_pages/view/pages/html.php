<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesHtml extends ComPagesViewHtml
{
    public function getLayout()
    {
        return 'page://pages/'. $this->getPage()->path;
    }

    public function getRoute($route = '', $fqr = true, $escape = true)
    {
        $query = array();

        if($route instanceof KModelEntityInterface)
        {
            $query['path'] = $route->path;
            $query['slug'] = $route->slug;
        }
        else if(is_string($route))
        {
            if(strpos($route, '=')) {
                parse_str(trim($route), $query);
            } else {
                $query['path'] = $route;
            }
        }
        else $query = $route;

        //Add add if the query is not unique
        if(!$query['slug'])
        {
            if($collection = $this->getPage()->collection)
            {
                $states = array();
                foreach ($this->getModel()->getState() as $name => $state)
                {
                    if ($state->default != $state->value && !$state->internal) {
                        $states[$name] = $state->value;
                    }

                    $query = array_merge($states, $query);
                }
            }
        }

        return parent::getRoute($query, $fqr, $escape);
    }

}