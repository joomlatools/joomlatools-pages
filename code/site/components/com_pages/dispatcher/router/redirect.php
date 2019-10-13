<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterRedirect extends ComPagesDispatcherRouterAbstract
{
    private $__resolver;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'routes' => $this->getObject('page.registry')->getRedirects(),
        ));

        parent::_initialize($config);
    }

    public function getResolver($route)
    {
        if(!$this->__resolver) {
            $this->__resolver =  $this->getObject('com://site/pages.dispatcher.router.resolver.regex', ['routes' => $this->getConfig()->routes]);
        }

        return $this->__resolver;
    }
}