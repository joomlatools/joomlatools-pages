<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

return function($path, $cache = true)
{
    $result = false;
    if(is_array($path))
    {
        if(is_numeric(key($path)))
        {
            foreach($path as $directory)
            {
                if (!$result instanceof ComPagesDataObject) {
                    $result = $this->getObject('data.registry')->fromPath($directory);
                } else {
                    $result->append($this->getObject('data.registry')->fromPath($directory));
                }
            }
        }
        else
        {
            $class = $this->getObject('manager')->getClass('com://site/pages.data.object');
            $result = new $class($path);
        }

    }
    else
    {
        $namespace = parse_url($path, PHP_URL_SCHEME);

        if(!in_array($namespace, ['http', 'https'])) {
            $result = $this->getObject('data.registry')->fromPath($path);
        } else {
            $result = $this->getObject('data.registry')->fromUrl($path, $cache);
        }
    }

    return $result;
};