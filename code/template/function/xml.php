<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($xml = array())
{
    $data = false;

    if(is_string($xml))
    {
        if(trim($xml)[0] != '<') {
            $data = $this->_fetchData($xml);
        }
    }
    else
    {
        if(!$xml instanceof KObjectConfigInterface) {
            $data = $this->_fetchData($xml);
        } else {
            $data = $xml;
        }
    }

    $config = new ComPagesObjectConfigXml();
    if(is_string($xml) && $data === false) {
        $result = $config->fromString($xml, false);
    } else {
        $result = $config->fromArray($data, false);
    }

    return $result;
};