<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelPages extends ComPagesModelCollection
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);
        $this->getState()
            ->insert('path', 'url', '.')
            ->insert('slug', 'cmd', '', true, array('path'))
            ->insert('recurse', 'boolean', true, false, array(), true) //internal state
            ->insert('level', 'int', 0, false, array(), true);         //internal state
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key' => 'path',
            'behaviors'    => [
                'recursable',
                'dateable',
                'sortable',
                'categorizable',
                'accessible',
                'crawlable',
                'visible',
                'collectable',
            ]
        ]);

        parent::_initialize($config);
    }

    public function getData()
    {
        if(!isset($this->_data))
        {
            $state    = $this->getState();
            $registry = $this->getObject('page.registry');

            //Make sure we have a valid path
            $pages = array();
            if($path = $state->path)
            {
                if ($this->getState()->isUnique())
                {
                    $page = $registry->getPage($path.'/'.$this->getState()->slug);

                    if ($page) {
                        $pages = $page->toArray();
                    }
                }
                else $pages = array_values($registry->getPages($path, $state->recurse, $state->level - 1));
            }

            $this->_data = $pages;
        }

        return $this->_data;
    }
}