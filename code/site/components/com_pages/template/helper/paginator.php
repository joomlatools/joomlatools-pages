<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

class ComPagesTemplateHelperPaginator extends KTemplateHelperPaginator
{
    protected function _link($page, $title)
    {
        $query = array();
        $query['limit']  = $page->limit;
        $query['offset'] = $page->offset;

        $url = $this->getTemplate()->route($query);

        if ($page->active && !$page->current) {
            $html = '<a href="'.$url.'">'.$this->getObject('translator')->translate($title).'</a>';
        } else {
            $html = '<a>'.$this->getObject('translator')->translate($title).'</a>';
        }

        return $html;
    }
}
