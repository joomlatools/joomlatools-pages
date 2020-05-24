<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */
return function($date, $format = '')
{
    if(!$date instanceof KDate)
    {
        if(empty($format)) {
            $format = $this->getObject('translator')->translate('DATE_FORMAT_LC3');
        }

        $result = $this->createHelper('date')->format(array('date' => $date, 'format' => $format));
    }
    else $result = $date->format($format);

    return $result;
};