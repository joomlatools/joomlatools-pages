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
            //Set the metadata
            foreach($this->getView()->getMetadata() as $name => $content)
            {
                if($content)
                {
                    $content =  is_array($content) ? implode(', ', $content) : $content;
                    $content =  htmlspecialchars($content, ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', false);

                    if(strpos($name, 'og:') === 0) {
                        $tag = sprintf('<meta property="%s" content="%s" />', $name, $content);
                    } else {
                        $tag = sprintf('<meta name="%s" content="%s" />', $name, $content);
                    }

                    JFactory::getDocument()->addCustomTag($tag);
                }
            }

            //Set the title
            if($title = $this->getView()->getTitle()) {
                JFactory::getDocument()->setTitle($title);
            }

            //Set the direction
            if($direction = $this->getView()->getDirection()) {
                JFactory::getDocument()->setDirection($direction);
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