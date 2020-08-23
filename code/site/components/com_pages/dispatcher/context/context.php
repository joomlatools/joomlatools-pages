<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherContext extends KDispatcherContext implements ComPagesDispatcherContextInterface
{
    private $__validators;

    public function getRouter()
    {
        return KObjectConfig::get('router');
    }

    public function setRouter(ComPagesDispatcherRouterInterface $router)
    {
        return KObjectConfig::set('router', $router);
    }

    public function getPage()
    {
        return KObjectConfig::get('page');
    }

    public function setPage(ComPagesPageEntity $page)
    {
        return KObjectConfig::set('page', $page);
    }

    public function setValidators(array $validators)
    {
        return $this->__validators = $validators;
    }

    public function getValidators()
    {
        return (array) $this->__validators;
    }
}