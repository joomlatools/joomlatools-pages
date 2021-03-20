<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerAbstract extends KControllerModel
{
    use ComPagesPageTrait;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setPage($config->page);
        $this->setModel($config->model);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'page'  => 'com://site/pages.page',
            'model' => 'com://site/pages.model.pages',
        ]);

        parent::_initialize($config);
    }

    public function getFormats()
    {
        return  array($this->getRequest()->getFormat());
    }

    public function getView()
    {
        if(!$this->_view instanceof KViewInterface)
        {
            //Get the view
            $view = KControllerView::getView();

            //Set the model in the view
            $view->setModel($this->getModel());
        }

        return parent::getView();
    }

    public function getModel()
    {
        if(!$this->_model instanceof KModelInterface)
        {
            //Get the model
            $model = parent::getModel();

            //Set the folder to the active page path if no folder is defined
            if($model->getState()->folder === null) {
                $model->getState()->folder = $this->getPage()->path;
            }
        }

        return $this->_model;
    }

    protected function _actionRender(KControllerContextInterface $context)
    {
        if(!$context->response->isError())
        {
            if($route = $context->router->generate($this->getPage()))
            {
                $location = $context->router->qualify($route);

                /**
                 * If Content-Location is included in a 2xx (Successful) response message and its value refers (after
                 * conversion to absolute form) to a URI that is the same as the effective request URI, then the recipient
                 * MAY consider the payload to be a current representation of that resource at the time indicated by the
                 * message origination date
                 *
                 * See: https://tools.ietf.org/html/rfc7231#section-3.1.4.2
                 */
                $context->response->headers->set('Content-Location', $location);
            }
        }

        return parent::_actionRender($context);
    }


    protected function _actionBrowse(KControllerContextInterface $context)
    {
        $entity = $this->getModel()->fetch();
        return $entity;
    }

    protected function _actionRead(KControllerContextInterface $context)
    {
        if(!$context->result instanceof KModelEntityInterface)
        {
            if($this->getModel()->getState()->isUnique())
            {
                $entity = $this->getModel()->fetch();

                if(!count($entity)) {
                    throw new KControllerExceptionResourceNotFound('Resource Not Found');
                }
            }
            else $entity = $this->getModel()->create();
        }
        else $entity = $context->result;

        return $entity;
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