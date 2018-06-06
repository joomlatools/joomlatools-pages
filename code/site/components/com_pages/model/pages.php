<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelPages extends KModelAbstract
{
    protected $_pages;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);
        $this->getState()
            ->insert('path', 'url')
            ->insert('file', 'cmd', '', true, array('path'));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'identity_key' => 'path',
            'behaviors'    => array('sortable', 'categorizable', 'paginatable')
        ));

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        if(!$context->entity) {
            $context->entity = KObjectConfig::unbox($context->pages);
        }

        return parent::_actionCreate($context);
    }

    protected function _actionCount(KModelContext $context)
    {
        return count($context->pages);
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->_pages = null;

        parent::_actionReset($context);
    }

    public function getContext()
    {
        $context = parent::getContext();

        if (!$this->getState()->isUnique()) {
            $context->pages = $this->_fetchPages();
        } else {
            $context->pages = $this->_fetchPage();
        }

        return $context;
    }

    protected function _fetchPages()
    {
        if(!$this->_pages)
        {
            $pages = array();
            $path  = $this->getState()->path;
            $page  = $this->getObject('com:pages.template.page')->loadFile($path);

            if($page->isCollection())
            {
                $file = $page->getFilename();

                $iterator = new FilesystemIterator(dirname($file));
                while($iterator->valid())
                {
                    $file = pathinfo($iterator->current()->getRealpath(), PATHINFO_FILENAME);

                    if($file != 'index') {
                        $pages[] = $this->_fetchPage($path.'/'.$file);
                    }

                    $iterator->next();
                }
            }

            $this->_pages = $pages;
        }

        return $this->_pages;
    }

    protected function _fetchPage($path = null)
    {
        $path = $path ?: $this->getState()->path.'/'.$this->getState()->file;
        $page = $this->getObject('com:pages.template.page')->loadFile($path);

        //Get the properties
        $properties = $page->getData();
        $properties['path']    = $path;
        $properties['file']    = basename($path);

        //If no date is defined use the file last modified time
        if(!isset($properties['date'])) {
            $properties['date'] = filemtime($page->getFilename());
        }

        $properties['content'] = $page->render($properties);

        return $properties;
    }
}