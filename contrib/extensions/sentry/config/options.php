<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtSentryConfigOptions extends ComPagesConfigOptions
{
    public function toString()
    {
        //Transforms keys to lowerCamelCase and generate json string
        $options = [];
        foreach($this as $key => $value)
        {
            $key = lcfirst(KStringInflector::camelize($key));
            $options[$key] = $value;
        }

        $json = new ComPagesObjectConfigJson($options);
        return $json->toString();
    }
}