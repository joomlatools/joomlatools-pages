<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherBehaviorConfigurable extends KControllerBehaviorAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_HIGHEST,
            'routes'   => array(),
        ));

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        $router = $this->getObject('com://site/pages.dispatcher.router.site', ['request' => $context->request]);

        if(false === $route = $router->resolve()) {
            throw new KHttpExceptionNotFound('Site Not Found');
        }

        //Bootstrap the site configuration
        $this->getObject('com://site/pages.config')->bootstrap($route->getPath());
    }
}