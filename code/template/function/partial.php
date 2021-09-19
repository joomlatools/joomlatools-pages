<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($url, $data = array())
{
    //Qualify the template
    if(!parse_url($url, PHP_URL_SCHEME)) {
        $url = 'template:/partials/'.trim($url, '/');
    }

    return  $this->loadPartial($url)->render($data);
};