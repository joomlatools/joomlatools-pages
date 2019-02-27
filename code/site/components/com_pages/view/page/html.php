<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPageHtml extends ComPagesViewHtml
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('after.render' , '_processPlugins');
    }

    protected function _processPlugins(KViewContextInterface $context)
    {
        $page = $this->getModel()->getPage();

        if($page->process->plugins)
        {
            $content = new stdClass;
            $content->text = $context->result;

            $params = (object)$page->getProperties();

            //Trigger onContentBeforeDisplay
            $results = array();
            $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                'name'         => 'onContentBeforeDisplay',
                'import_group' => 'content',
                'attributes'   => array('com_pages.page', &$content, &$params)
            ));

            //Trigger onContentPrepare
            $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                'name'         => 'onContentPrepare',
                'import_group' => 'content',
                'attributes'   => array('com_pages.page', &$content, &$params)
            ));

            //Trigger onContentAfterDisplay
            $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                'name'         => 'onContentAfterDisplay',
                'import_group' => 'content',
                'attributes'   => array('com_pages.page', &$content, &$params)
            ));

            $context->result = trim(implode("\n", $results));;
        }
    }
}