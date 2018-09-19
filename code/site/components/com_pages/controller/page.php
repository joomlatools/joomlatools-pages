<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPage extends ComPagesControllerAbstract
{
    protected function _beforeRender(KControllerContextInterface $context)
    {
        if($context->request->getFormat() == 'html')
        {
            //Set the path in the pathway to allow for module injection
            $page_route = $this->getObject('com:pages.dispatcher.router.route')->getRoute();
            $menu_route = JFactory::getApplication()->getMenu()->getActive()->route;

            if($path = ltrim(str_replace($menu_route, '', $page_route), '/'))
            {
                $pathway = JFactory::getApplication()->getPathway();

                $segments = array();
                foreach(explode('/', $path) as $segment)
                {
                    $segments[] = $segment;
                    $pathway->addItem(ucfirst($segment), 'index.php?path='.implode('/', $segments));
                }
            }
        }
    }
}