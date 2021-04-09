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
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'total'      => 0,
            'display'    => 2,
            'offset'     => 0,
            'limit'      => 0,
            'show_limit' => true,
            'show_count' => false
        ))->append(array(
            'show_pages' => $config->count !== 1
        ));

        $this->_initialize($config);

        //See: https://www.a11ymatters.com/pattern/pagination/
        $html = '';
        if($collection = $this->page()->collection)
        {
            $items = $this->_items($config);

            //If the limit is hardcoded in the collection dont allow it to be changed.
            if($collection->state->limit) {
                $config['show_limit'] = false;
            }

            $html = '<nav class="k-pagination" aria-label="pagination">';

            if($config->show_limit) {
                $html .= '<div class="k-pagination__limit">'.$this->limit($config).'</div>';
            }

            if ($config->show_pages)
            {
                $html .= '<ul class="k-pagination__pages" itemscope itemtype="http://schema.org/SiteNavigationElement">';
                $html .=  $this->_pages($items);
                $html .= '</ul>';
            }

            if($config->show_count)
            {
                $current = '<strong class="page-current">'.$config->current.'</strong>';
                $total   = '<strong class="page-total">'.$config->count.'</strong>';

                $html .= '<div class="k-pagination-pages">';
                $html .= sprintf($this->getObject('translator')->translate('Page %s of %s'), $current, $total);
                $html .= '</div>';
            }

            $html .= '</nav>';

            if($items['previous']->active) {
                $html .= $this->_rel($items['previous']);
            }

            if($items['next']->active) {
                $html .= $this->_rel($items['next']);
            }
        }

        return $html;
    }

    protected function _items(KObjectConfig $config)
    {
        $elements  = array();

        // First
        $offset  = 0;
        $active  = $offset != $config->offset;
        $props   = array(
            'page'      => 1,
            'offset'    => $offset,
            'limit'     => $config->limit,
            'current'   => false,
            'active'    => $active,
            'label'     => 'First page',
        );

        $elements['first'] = (object) $props;

        // Previous
        $offset  = max(0, ($config->current - 2) * $config->limit);
        $active  = $offset != $config->offset;
        $props   = array(
            'page'      => $config->current - 1,
            'offset'    => $offset,
            'limit'     => $config->limit,
            'current'   => false,
            'active'    => $active,
            'label'     => 'Previous page',
            'rel'       => 'prev'
        );
        $elements['previous'] = (object) $props;

        // Pages
        $elements['pages'] = array();
        foreach($this->_offsets($config) as $page => $offset)
        {
            $current = $offset == $config->offset;
            $props = array(
                'page'      => $page,
                'offset'    => $offset,
                'limit'     => $config->limit,
                'current'   => $current,
                'active'    => !$current,
                'label'     => $current ? 'Current page, page '.$page : 'Go to page '.$page,
                'rel'       => false
            );
            $elements['pages'][] = (object) $props;
        }

        // Next
        $offset  = min(($config->count-1) * $config->limit, ($config->current) * $config->limit);
        $active  = $offset != $config->offset;
        $props   = array(
            'page' => $config->current + 1,
            'offset' => $offset,
            'limit' => $config->limit,
            'current' => false,
            'active' => $active,
            'label' => 'Next page',
            'rel'   => 'next'
        );
        $elements['next'] = (object) $props;

        // Last
        $offset  = ($config->count - 1) * $config->limit;
        $active  = $offset != $config->offset;
        $props   = array(
            'page'      => $config->count,
            'offset'    => $offset,
            'limit'     => $config->limit,
            'current'   => false,
            'active'    => $active,
            'label'     => 'Last page',
        );
        $elements['last'] = (object) $props;

        return $elements;
    }

    protected function _link($page, $title)
    {
        $query = (array(
            'limit'  => $page->limit,
            'offset' => $page->offset,
        ));

        $route = $this->route($this->getTemplate()->page(), $query);
        $label = $this->getObject('translator')->translate($page->label);
        $title = $this->getObject('translator')->translate($title);

        if ($page->active && !$page->current) {
            $html = '<a href="'.$route.'" aria-label="'.$label.'" itemprop="url"><span itemprop="name">'.$title.'</span></a>';
        } else {
            $html = '<span class="active" aria-current="page" aria-label="'.$label.'" itemprop="name">'.$title.'</span>';
        }

        return $html;
    }

    protected function _rel($page)
    {
        $query = (array(
            'limit'  => $page->limit,
            'offset' => $page->offset,
        ));

        $route = $this->route($this->getTemplate()->page(), $query);

        if($page->rel) {
            $html = '<link href="'.$this->url($route).'" rel="'.$page->rel.'"  />';
        }

        return $html;
    }
}
