<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

final class ComPagesDataFactory extends KObject implements KObjectSingleton
{
    private $__cache = array();

    public function createObject($path, $format = '')
    {
        if(!isset($this->__cache[$path]))
        {
            if(!parse_url($path, PHP_URL_SCHEME) == 'http')
            {
                //Locate the data file
                if (!$file = $this->getObject('template.locator.factory')->locate('data://'.$path)) {
                    throw new InvalidArgumentException(sprintf('The data file "%s" cannot be located.', $path));
                }

                if(is_dir($file)) {
                    $result = $this->fromDirectory($file);
                } else {
                    $result = $this->fromFile($file);
                }
            }
            else $result = $this->fromUrl($path, $format);

            //Create the data object
            $this->__cache[$path] = new ComPagesDataObject($result);
        }

        return $this->__cache[$path];
    }

    public function fromFile($file)
    {
        //Get the data
        $result = array();

        $url = trim(fgets(fopen($file, 'r')));
        if(strpos($file, '://') !== false) {
            $result = $this->fromUrl($url, pathinfo($file, PATHINFO_EXTENSION));
        } else {
            $result = $this->getObject('object.config.factory')->fromFile($file, false);
        }

        return $result;
    }

    public function fromDirectory($path)
    {
        $data  = array();
        $files = array();
        $dirs  = array();

        $basepath = $this->getObject('com:pages.data.locator')->getBasePath();
        foreach (new DirectoryIterator($path) as $node)
        {
            if ($node->isFile()) {
                $files[] = ltrim(str_replace($basepath, '', $node->getPathname()), '/');
            }

            if($node->isDir() && !$node->isDot()) {
                $dirs[] =  ltrim(str_replace($basepath, '', $node->getPathname()), '/');
            }
        }

        //Handle Files
        foreach($files as $file)
        {
            if(count($files) > 1) {
                $data[] = $this->createObject($file);
            } else {
                $data = $this->createObject($file);
            }
        }

        //Handle Directories
        foreach($dirs as $dir) {
            $data[basename($dir)] = $this->createObject($dir);
        }

        return $data;
    }

    public function fromUrl($url, $format = '')
    {
        if(!ini_get('allow_url_fopen')) {
            throw new RuntimeException(sprintf('Cannot open url: "%s".', $url));
        }

        if(empty($format))
        {
            if(!$format = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) {
                throw new InvalidArgumentException(sprintf('Cannot determine data type of "%s".', $url));
            }
        }

        //Set the user agents
        $version = $this->getObject('com:pages.version');
        $context = stream_context_create(array('http' => array(
            'user_agent' => 'Joomlatools/Pages/'.$version,
        )));

        if(!$content = file_get_contents($url, false, $context))
        {
            if($error = error_get_last()) {
                throw new RuntimeException(sprintf('Cannot get content from url error: "%s"', trim($error['message'])));
            } else {
                throw new RuntimeException(sprintf('Cannot get content from url: "%s"', $url));
            }
        }

        $result = $this->getObject('object.config.factory')->fromString($format, $content, false);
        return $result;
    }
}