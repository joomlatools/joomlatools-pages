<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesClassLocatorExtension extends KClassLocatorAbstract
{
    protected static $_name = 'ext';

    public function locate($classname, $basepath = null)
    {
        if (substr($classname, 0, 9) === 'Extension')
        {
            $word  = strtolower(preg_replace('/(?<=\\w)([A-Z])/', ' \\1', $classname));
            $parts = explode(' ', $word);

            array_shift($parts);
            $package   = array_shift($parts);
            $namespace = ucfirst($package);

            if(count($parts)) {
                $file = array_pop($parts);
            } else {
                $file = $package;
            }

            //Switch basepath
            if(!$this->getNamespace($namespace)) {
                $basepath = $this->getNamespace('\\');
            } else {
                $basepath = $this->getNamespace($namespace);
            }

            $path = '';

            if (!empty($parts)) {
                $path = implode('/', $parts) . '/';
            }

            $result = $basepath.'/'.$package.'/'.$path . $file.'.php';

            if(!is_file($result)) {
                $result = $basepath.'/'.$package.'/'.$path . $file.'/'.$file.'.php';
            }

            return $result;
        }

        return false;

    }
}
