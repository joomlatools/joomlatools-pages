<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerBehaviorBreadcrumbable extends KControllerBehaviorAbstract
{
    protected function _beforeRender(KControllerContextInterface $context)
    {
        if($context->request->getFormat() == 'html')
        {
            //Set the path in the pathway to allow for module injection
            $page_route = $this->getObject('dispatcher')->getRoute()->getPath(false);

            if($menu = JFactory::getApplication()->getMenu()->getActive()) {
                $menu_route = $menu->route;
            } else {
                $menu_route = '';
            }

            if($path = ltrim(str_replace($menu_route, '', $page_route), '/'))
            {
                $pathway = JFactory::getApplication()->getPathway();
                $router = $this->getObject('router');

                $segments = array();
                foreach(explode('/', $path) as $segment)
                {
                    $segments[] = $segment;

                    if($route = $router->generate('pages:'.implode('/', $segments)))
                    {
                        $page = $route->getPage();

                        if(!$page->name) {
                            $name = ucwords(str_replace(array('_', '-'), ' ', $page->slug));
                        } else {
                            $name = ucfirst($page->name);
                        }

                        $route = $router->qualify($route);
                        $url   = $route->toString(KHttpUrl::PATH);

                        $pathway->addItem($name, (string) $url);
                    }
                }
            }
        }
    }

    public function isSupported()
    {
        return $this->getMixer()->isDispatched() &&  $this->getObject('dispatcher')->getRoute();
    }
}