<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewCsv extends KViewCsv
{
    public function getRoute($page, $query = array(), $escape = false)
    {
        return $this->getBehavior('routable')->getRoute($page, $query, $escape);
    }
}