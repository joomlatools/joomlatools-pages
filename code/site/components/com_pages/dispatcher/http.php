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
            'router'    => 'com://site/pages.dispatcher.router',
        ]);

        parent::_initialize($config);
    }

    public function setRouter(ComPagesDispatcherRouterInterface $router)
    {
        $this->_router = $router;
        return $this;
    }

    public function getRouter()
    {
        if(!$this->_router instanceof ComPagesDispatcherRouterInterface)
        {
            $this->_router = $this->getObject($this->_router, array(
                'response' => $this->getResponse(),
            ));

            if(!$this->_router instanceof ComPagesDispatcherRouterInterface)
            {
                throw new UnexpectedValueException(
                    'Router: '.get_class($this->_router).' does not implement ComPagesDispatcherRouterInterface'
                );
            }
        }

        return $this->_router;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        //Throw 404 if the page cannot be found
        if(!$route = $context->router->resolve()) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }

        //Send redirect 
        if($context->response->isRedirect()) {
            $this->redirect($route);
        }

        //Get the page from the router
        $page = $context->router->getPage();

        //Set the controller
        $this->setController('page', ['view' => $page->getType()]);

        //Set page in model
        $this->getController()->getModel()->setPage($page, $context->request->query->toArray());
    }

    protected function _actionGet(KDispatcherContextInterface $context)
    {
        //Do not force set a limit for html requests.
        if($context->getRequest()->getFormat() == 'html') {
            $result =  $this->getController()->execute('render', $context);
        } else {
            $result = parent::_actionGet($context);
        }

        return $result;
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