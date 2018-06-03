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
        $config->append(array(
            'behaviors' => array(
                'redirectable'
            ),
        ));
        parent::_initialize($config);
    }

    protected function _beforeRender(KControllerContextInterface $context)
    {
        $entity = $this->getModel()->fetch();

        //Set the entity content in the response to allow for view decoration
        $context->response->setContent($entity->content);
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        $entity = $this->getModel()->fetch();

        if($context->request->getFormat() == 'html')
        {
            //Set the metadata
            foreach($entity->metadata as $name => $content) {
                JFactory::getDocument()->setMetaData($name, $content);
            }

            //Set the title
            JFactory::getDocument()->setTitle($entity->title);
        }
    }
}