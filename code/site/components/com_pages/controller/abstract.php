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
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'model' => 'pages',
        ]);

        parent::_initialize($config);
    }

    public function getFormats()
    {
        if($this->getObject('dispatcher')->getRouter()->resolve()) {
            $formats = array($this->getRequest()->getFormat());
        }  else {
            $formats = array();
        }

        return $formats;
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
}