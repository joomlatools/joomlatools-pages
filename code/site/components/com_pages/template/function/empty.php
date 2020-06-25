<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($var)
{
    if($var instanceof Traversable) {
        $var = iterator_count($var);
    } elseif($var instanceof Countable) {
        $var = count($var);
    }

    return empty($var);
};