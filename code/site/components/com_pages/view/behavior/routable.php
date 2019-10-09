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
     * Get a route based on route and query
     *
     * In templates, use route()
     *
     * @param   mixed  $route
     * @param   array  $query  The query string or array used to create the route
     * @param   boolean   $escpae    If TRUE  escapes '&' to '&amp;' for xml compliance
     * @return  KHttpUrl The route
     */
    public function getRoute($route = null, $query = array(), $escape = false)
    {
        if($route)
        {
            //Prepend the route with the package
            if(is_string($route) && strpos($route, ':') === false) {
                $route = $this->getIdentifier()->getPackage().':'.$route;
            }

            //Generate the route
            $router = $this->getObject('dispatcher')->getRouter();

            if($route = $router->generate($route, $query)) {
                $route = $router->qualify($route, $this->getFormat() !== 'html', $escape);
            }
        }
        else $route = $this->getObject('dispatcher')->getRoute();

        return $route;
    }
}