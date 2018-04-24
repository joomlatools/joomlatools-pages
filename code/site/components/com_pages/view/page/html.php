<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesViewPageHtml extends ComKoowaViewPageHtml
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('after.render', '_processPlugins');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'template_filters' => ['markdown'],
        ]);

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        parent::_fetchData($context);

        //Find the layout
        if($layout = $context->data->page->layout)
        {
            $path = 'page://layouts/'.$layout;

            if (!$this->getObject('template.locator.factory')->locate($path)) {
                throw new RuntimeException(sprintf("Layout '%s' cannot be found", $layout));
            }
        }
        else $path = 'com://site/pages.page.default.html';

        $context->layout = $path;
    }

    /**
     * Return the views output
     *
     * @param KViewContext  $context A view context object
     * @return string  The output of the view
     */
    protected function _actionRender(KViewContext $context)
    {
        $data = KObjectConfig::unbox($context->data);

        //Render the template
        $this->_content = $this->getTemplate()
            ->loadFile($context->layout)
            ->setParameters($context->parameters)
            ->render($data);

        return KViewAbstract::_actionRender($context);
    }

    protected function _processPlugins(KViewContextInterface $context)
    {
        $page = $context->data->page;

        if($page->process->plugins)
        {
            $content = $context->result;

            //Trigger onContentBeforeDisplay
            $results = array();
            $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                'name'         => 'onContentBeforeDisplay',
                'import_group' => 'content',
                'attributes'   => array('com_pages.page', &$content, (object)$page->getProperties())
            ));

            //Trigger onContentPrepare
            $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                'name'         => 'onContentPrepare',
                'import_group' => 'content',
                'attributes'   => array('com_pages.page', &$content, (object)$page->getProperties())
            ));

            //Trigger onContentAfterDisplay
            $results[] = $this->getTemplate()->createHelper('event')->trigger(array(
                'name'         => 'onContentAfterDisplay',
                'import_group' => 'content',
                'attributes'   => array('com_pages.page', &$content, (object)$page->getProperties())
            ));

            $context->result = trim(implode("\n", $results));;
        }
    }
}