<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesHtml extends ComPagesViewHtml
{
    protected function _fetchData(KViewContext $context)
    {
        parent::_fetchData($context);

        //Set the layout
        $context->layout = 'page://pages/'.$context->layout;
    }
}