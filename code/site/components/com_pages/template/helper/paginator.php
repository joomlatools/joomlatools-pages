<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateHelperPaginator extends KTemplateHelperPaginator
{
    protected function _link($page, $title)
    {
        $query = (array(
            'limit'  => $page->limit,
            'offset' => $page->offset,
        ));

        $route = $this->getTemplate()->route($this->getTemplate()->page(), $query);

        if ($page->active && !$page->current) {
            $html = '<a href="'.$route.'">'.$this->getObject('translator')->translate($title).'</a>';
        } else {
            $html = '<a>'.$this->getObject('translator')->translate($title).'</a>';
        }

        return $html;
    }
}
