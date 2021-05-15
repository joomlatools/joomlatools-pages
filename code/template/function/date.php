<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */
return function($format = '', $date = 'now')
{
    if(!$date instanceof KDate)
    {
        if(empty($format)) {
            $format = 'd F Y';
        }

        $result = $this->createHelper('date')->format(array('date' => $date, 'format' => $format));
    }
    else $result = $date->format($format);

    return $result;
};