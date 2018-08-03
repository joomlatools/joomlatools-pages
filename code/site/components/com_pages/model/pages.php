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
            ->insert('path', 'url', '.')
            ->insert('slug', 'cmd', '', true, array('path'))
            ->insert('recurse', 'boolean', 0, false, array(), true) //internal state
            ->insert('level', 'int', 0, false, array(), true);      //internal state

        $this->addCommandCallback('before.fetch', '_prepareContext');
        $this->addCommandCallback('before.count', '_prepareContext');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'path',
            'behaviors'    => [
                'recursable',
                'sortable',
                'categorizable',
                'accessible',
                'crawlable',
                'paginatable',
            ]
        ]);

        parent::_initialize($config);
    }

    public function getPages()
    {
        if(!isset($this->_pages))
        {
            $state    = $this->getState();
            $registry = $this->getObject('page.registry');

            //Make sure we have a valid path
            $pages = array();
            if($path = $state->path)
            {
                if (!$this->getState()->isUnique()) {
                    $pages = array_values($registry->getPages($path, $state->recurse, $state->level - 1));
                } else {
                    $pages = $registry->getPage($path.'/'.$this->getState()->slug)->toArray();
                }
            }

            $this->_pages = $pages;
        }

        return $this->_pages;
    }

    protected function _prepareContext(KModelContext $context)
    {
        $pages = $this->getPages();

        $context->pages  = $pages;
        $context->entity = $pages;
    }

    protected function _actionFetch(KModelContext $context)
    {
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
}