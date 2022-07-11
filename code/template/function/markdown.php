<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($source)
{
    static $engine;

    if(!isset($engine))
    {
        $engine = $this->getObject('template.engine.factory')
            ->createEngine('markdown', array('template' => $this));
    }

    return $engine->loadString($source)->render();
};