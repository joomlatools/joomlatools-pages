<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewDecoratorHtml extends ComPagesViewHtml
{
    protected function _actionRender(KViewContext $context)
    {
        $context->layout = 'page://pages/'.$this->getPage()->path;

        return parent::_actionRender($context);
    }
}