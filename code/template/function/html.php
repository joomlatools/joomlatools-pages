<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($html = array())
{
    $data = false;

    if(is_string($html))
    {
        if(trim($html)[0] != '<') {
            $data = $this->_fetchData($html);
        }
    }
    else
    {
        if(!$html instanceof KObjectConfigInterface) {
            $data = $this->_fetchData($html);
        } else {
            $data = $html;
        }
    }

    $config = new ComPagesObjectConfigHtml();
    if(is_string($html) && $data === false) {
        $result = $config->fromString($html, false);
    } else {
        $result = $config->fromArray($data, false);
    }

    return $result;

};