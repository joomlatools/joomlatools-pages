<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherHttp extends ComKoowaDispatcherHttp
{
    protected $_router;

    public function __construct( KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_router = $config->router;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors' => ['cacheable'],
            'router'    => 'com:pages.dispatcher.router',
        ]);

        parent::_initialize($config);
    }

    public function setRouter(KDispatcherRouterInterface $router)
    {
        $this->_router = $router;
        return $this;
    }

    public function getRouter()
    {
        if(!$this->_router instanceof KDispatcherRouterInterface)
        {
            $this->_router = $this->getObject($this->_router, array(
                'request' => $this->getRequest(),
            ));

            if(!$this->_router instanceof KDispatcherRouterInterface)
            {
                throw new UnexpectedValueException(
                    'Router: '.get_class($this->_router).' does not implement KDispatcherRouterInterface'
                );
            }
        }

        return $this->_router;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        //Throw 404 if the page cannot be found
        if(!$this->getRouter()->match()) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }
    }

    protected function _actionDispatch(KDispatcherContextInterface $context)
    {
        //Throw 405 if the method is not allowed
        $method = strtolower($context->request->getMethod());
        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        //Execute the component method
        $this->execute($method, $context);

        KDispatcherAbstract::_actionDispatch($context);
    }

    public function getContext()
    {
        $context = new ComPagesDispatcherContext();
        $context->setSubject($this);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());
        $context->setRouter($this->getRouter());

        return $context;
    }
}