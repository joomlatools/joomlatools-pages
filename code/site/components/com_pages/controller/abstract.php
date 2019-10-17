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
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setModel($config->model);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'model' => 'com://site/pages.model.pages',
        ]);

        parent::_initialize($config);
    }

    public function getFormats()
    {
        return  array($this->getRequest()->getFormat());
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        //Set metadata
        if($context->request->getFormat() == 'html')
        {
            //Set the title
            if($title = $this->getView()->getTitle()) {
                JFactory::getDocument()->setTitle($title);
            }

            //Set the direction
            if($direction = $this->getView()->getDirection()) {
                JFactory::getDocument()->setDirection($direction);
            }

            //Set the language
            if($language = $this->getView()->getLanguage()) {
                JFactory::getDocument()->setLanguage($language);
            }
        }
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

    public function setModel($model)
    {
        if($model instanceof ComPagesPageObject)
        {
            if($model->isCollection())
            {
                //Create the collection model
                $this->_model = $this->getObject('com://site/pages.model.factory')
                    ->createCollection($model->path, $this->getRequest()->query->toArray());
            }
            else $this->_model = $this->getObject('com://site/pages.model.pages');

            //Add the pageable behavior to the model
            $this->_model->addBehavior('com://site/pages.model.behavior.pageable', ['page' => $model]);
        }
        else $this->_model = parent::setModel($model);

        return $this->_model;
    }
}