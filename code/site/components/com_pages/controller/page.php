<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPage extends KControllerModel
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'formats'   => ['json'],
            'behaviors' => ['redirectable'],
        ]);
        parent::_initialize($config);
    }

    public function getRequest()
    {
        $request = parent::getRequest();

        //Remove the view query parameter
        $request->query->remove('view');

        return $request;
    }

    public function getFormats()
    {
        $formats = parent::getFormats();

        if($page = $this->getRequest()->query->get('page', 'url', false))
        {
            $format = $this->getRequest()->getFormat();
            if($this->getObject('page.registry')->isPageFormat($page, $format)) {
                $formats = array($format);
            } else {
                $formats = array();
            }
        }

        return $formats;
    }

    protected function _beforeRender(KControllerContextInterface $context)
    {
        if($context->request->getFormat() == 'html')
        {
            //Set the entity content in the response to allow for view decoration
            if(!$context->response->getContent())
            {
                $entity = $this->getModel()->fetch();
                $context->response->setContent($entity->content);
            }

            //Set the path in the pathway to allow for module injection
            $page_route = $this->getObject('com:pages.router')->getRoute();
            $menu_route = JFactory::getApplication()->getMenu()->getActive()->route;

            if($path = ltrim(str_replace($menu_route, '', $page_route), '/'))
            {
                $pathway = JFactory::getApplication()->getPathway();

                $segments = array();
                foreach(explode('/', $path) as $segment)
                {
                    $segments[] = $segment;
                    $pathway->addItem(ucfirst($segment), 'index.php?path='.implode('/', $segments));
                }
            }
        }
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        //Set metadata
        if($context->request->getFormat() == 'html')
        {
            //Set the metadata
            foreach($this->getView()->getMetadata() as $name => $content) {
                JFactory::getDocument()->setMetaData($name, $content);
            }

            //Set the title
            if($title = $this->getView()->getTitle()) {
                JFactory::getDocument()->setTitle($title);
            }
        }
    }
}