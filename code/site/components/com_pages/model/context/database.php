<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

class ComPagesModelContextDatabase extends KModelContext
{
    public function setQuery($query)
    {
        return KObjectConfig::set('query', $query);
    }

    public function getQuery()
    {
        return KObjectConfig::get('query');
    }
}