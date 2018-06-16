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

    public function getFormats()
    {
        $formats = parent::getFormats();

        //Only add rss and xml for collections
        if(!$this->getModel()->getState()->isUnique())
        {
            $formats[] = 'rss';
            $formats[] = 'xml';
        }

        return $formats;
    }

    protected function _beforeRender(KControllerContextInterface $context)
    {
        //Set the entity content in the response to allow for view decoration
        if($context->request->getFormat() == 'html')
        {
            $entity = $this->getModel()->fetch();
            $context->response->setContent($entity->content);
        }
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        if($context->request->getFormat() == 'html')
        {
            //Set the metadata
            foreach($this->getView()->getMetadata() as $name => $content) {
                JFactory::getDocument()->setMetaData($name, $content);
            }

            //Set the title
            JFactory::getDocument()->setTitle($this->getView()->getTitle());
        }
    }
}