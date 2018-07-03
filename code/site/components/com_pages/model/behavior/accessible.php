<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorAccessible extends ComPagesModelBehaviorFilterable
{
    protected function _accept($page, $context)
    {
        $registry = $this->getObject('page.registry');
        return $registry->isPublished($page['path'].'/'.$page['slug']) && $registry->isAccessible($page['path'].'/'.$page['slug']);
    }
}