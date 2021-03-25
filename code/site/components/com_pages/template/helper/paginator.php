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
    use ComPagesTemplateTraitFunction;

    public function pagination($config = array())
    {
        $html = '';

        //If the limit is hardcoded in the collection dont allow it to be changed.
        if($collection = $this->page()->collection)
        {
            if($collection->state->limit) {
                $config['show_limit'] = false;
            }

            $html = parent::pagination($config);
        }

        return $html;
    }

    protected function _link($page, $title)
    {
        $query = (array(
            'limit'  => $page->limit,
            'offset' => $page->offset,
        ));

        $route = $this->route($this->getTemplate()->page(), $query);

        if ($page->active && !$page->current) {
            $html = '<a href="'.$route.'">'.$this->getObject('translator')->translate($title).'</a>';
        } else {
            $html = '<a>'.$this->getObject('translator')->translate($title).'</a>';
        }

        return $html;
    }
}
