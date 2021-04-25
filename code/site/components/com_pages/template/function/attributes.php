<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($name, $value = null)
{
    $result = '';

    if(!is_array($name) && $value) {
        $name = array($name => $value);
    }

    if($name instanceof KObjectConfig) {
        $name = KObjectConfig::unbox($name);
    }

    if(is_array($name))
    {
        $output = array();
        foreach($name as $key => $item)
        {
            if(is_array($item))
            {
                foreach($item as $k => $v)
                {
                    if(empty($v)) {
                        unset($item[$k]);
                    }
                }

                $item = implode(' ', $item);
            }

            if (is_bool($item))
            {
                if ($item === false) continue;
                $item = $key;
            }

            $output[] = $key.'="'.str_replace('"', '&quot;', $item).'"';
        }

        $result = ' '.implode(' ', $output);
    }

    return $result;
};