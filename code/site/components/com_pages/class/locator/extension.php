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
        if (substr($classname, 0, 3) === 'Ext')
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

            $path = '';
            if (!empty($parts)) {
                $path = implode('/', $parts) . '/';
            }

            $paths = [];

            //Namespace paths
            if($basepath = $this->getNamespace($namespace))
            {
                $paths[] = $basepath.'/'.$path . $file.'.php';
                $paths[] = $basepath.'/'.$path . $file.'/'.$file.'.php';
            }

            ///Fallback paths
            $basepath = $this->getNamespace('\\');
            $paths[] = $basepath.'/'.strtolower($namespace) .'/'.$path . $file.'.php';
            $paths[] = $basepath.'/'.strtolower($namespace) .'/'.$path . $file.'/'.$file.'.php';

            foreach($paths as $path)
            {
                if(is_file($path))
                {
                    $result = $path;
                    break;
                }
            }

            return $result;
        }

        return false;

    }
}
