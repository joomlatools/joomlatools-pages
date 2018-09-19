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

    public function getLayout()
    {
        if(!$layout = parent::getLayout())
        {
            if($collection = $this->getObject('page.registry')->isCollection($this->getPage()->path))
            {
                if(isset($collection['layout'])) {
                    $layout = $collection['layout'];
                }
            }
        }

        return $layout;
    }

    protected function _fetchData(KViewContext $context)
    {
        parent::_fetchData($context);

        //Find the layout
        if(!$layout = $context->layout) {
            $layout = 'com://site/pages.page.default.html';
        }

        $context->layout = $layout;
    }

    protected function _actionRender(KViewContext $context)
    {
        //Set the pre-rendered page content in the response to allow for view decoration
        if(!$this->getContent())
        {
            $data       = $context->data;
            $layout     = $context->layout;
            $parameters = $context->parameters;

            //Render the page
            $page = $this->getObject('com:pages.template.page')
                ->setParameters($parameters)
                ->loadFile('page://pages/'.$this->getPage()->route);

            $data->append($page->getData());

            $this->setContent($page->render(KObjectConfig::unbox($data)));

            //Set the layout
            $context->layout = $this->getLayout();
        }

        return parent::_actionRender($context);
    }

    protected function _processPlugins(KViewContextInterface $context)
    {
        $page = $context->data->page;

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