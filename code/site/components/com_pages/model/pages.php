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
            ->insert('slug', 'cmd', '', true, array('path'));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'identity_key' => 'path',
            'behaviors'    => array('sortable', 'categorizable', 'accessible', 'paginatable')
        ));

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
                $page->content = $registry->getContent($page->path);
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

        if (!$this->getState()->isUnique()) {
            $path = $this->getState()->path;
        } else {
            $path = $this->getState()->path.'/'.$this->getState()->slug;
        }

        $context->pages = $registry->getData($path);

        return $context;
    }
}