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
        if(!is_array($query)) {
            $query = array();
        }

        if($route = $this->getObject('dispatcher')->getRouter()->generate($page, $query)) {
            $route = $route->setEscape($escape)->toString(KHttpUrl::FULL);
        }

        return $route;
    }
}