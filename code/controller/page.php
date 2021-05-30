<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPage extends KControllerView
{
    use ComPagesPageTrait;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setPage($config->page);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'page'  => 'com:pages.page',
        ]);

        parent::_initialize($config);
    }

    public function getFormats()
    {
        return  array($this->getRequest()->getFormat());
    }

    public function getContext()
    {
        $context = new ComPagesControllerContext();
        $context->setSubject($this);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());
        $context->setPage($this->getPage());

        return $context;
    }
}