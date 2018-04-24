<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelPages extends KModelAbstract
{
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
        ));

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        if ($this->getState()->isUnique())
        {
            $path = $this->getState()->path.'/'.$this->getState()->file;

            //Locate the template
            if ($file = $this->getObject('template.locator.factory')->locate($path))
            {
                $context->entity =  array(
                    'path' => $path,
                    'file' => $file,
                );
            }

            return parent::_actionCreate($context);
        }
    }
}