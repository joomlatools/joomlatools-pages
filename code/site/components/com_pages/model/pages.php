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
            ->insert('slug', 'cmd', '', true, array('path'))
            ->insert('tree', 'boolean');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'path',
            'behaviors'    => ['sortable', 'categorizable', 'accessible', 'paginatable', 'recursable']
        ]);

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        if(!$context->entity) {
            $context->entity = KObjectConfig::unbox($context->pages);
        }

        if($result = parent::_actionCreate($context))
        {
            $registry = $this->getObject('page.registry');

            foreach($result as $page) {
                $page->content = $registry->getPage($page->path)->content;
            }
        }

        return $result;
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
        $registry = $this->getObject('page.registry');

        //Make sure we have a valid path
        if($path = $this->getState()->path)
        {
            if (!$this->getState()->isUnique())
            {
                if($this->getState()->tree) {
                    $pages = $registry->getPages($path);
                } else {
                    $pages = $registry->getCollection($path);
                }
            }
            else $pages = $registry->getPage($path.'/'.$this->getState()->slug)->toArray();

            $context->pages = $pages;
        }

        return $context;
    }
}