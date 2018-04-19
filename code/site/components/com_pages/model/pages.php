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
            ->insert('path', 'url');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'identity_key' => 'path',
            'state'        => 'com:pages.model.state',
        ));

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        if ($path = $this->getState()->path)
        {
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
        else throw new UnexpectedValueException("The state 'path' is required.");
    }
}