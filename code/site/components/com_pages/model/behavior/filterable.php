<?php
/**
 * Joomlatools Framework Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

abstract class ComPagesModelBehaviorFilterable extends KModelBehaviorAbstract
{
    protected function _beforeCount(KModelContextInterface $context)
    {
        $this->_beforeFetch($context);
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $context->entity = KObjectConfig::unbox($context->pages);
    }
}