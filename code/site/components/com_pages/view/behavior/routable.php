<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Routable View Behavior
 *
 * @author  Johan Janssens <http://github.com/johanjanssens>
 * @package Kodekit\Library\View\Behavior
 */
class ComPagesViewBehaviorRoutable extends KViewBehaviorAbstract
{
    /**
     * Register a route() function in the template
     *
     * @param KViewContext $context	A view context object
     * @return void
     */
    protected function _beforeRender(KViewContext $context)
    {
        //Register 'route' method in template
        if($context->subject instanceof KViewTemplate)
        {
            $context->subject
                ->getTemplate()
                ->registerFunction('route', array($this, 'getRoute'));
        }
    }

    /**
     * Get a route based on a page path and query
     *
     * In templates, use route()
     *
     * @param   string $page   The page to generate a route for
     * @param   array  $query  The query string or array used to create the route
     * @param   boolean      $escpae    If TRUE  escapes '&' to '&amp;' for xml compliance
     * @return  KHttpUrl The route
     */
    public function getRoute($page = null, $query = array(), $escape = false)
    {
        if(!is_array($query)) {
            $query = array();
        }

        if($route = $this->getObject('dispatcher')->getRouter()->generate($page, $query))
        {
            if($this->getFormat() == 'html') {
                $parts = KHttpUrl::PATH + KHttpUrl::QUERY;
            } else {
                $parts = KHttpUrl::FULL;
            }

            $route = $route->setEscape($escape)->toString($parts);
        }

        return $route;
    }
}