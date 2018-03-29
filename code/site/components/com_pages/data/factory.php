<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

final class ComPagesDataFactory extends KObject implements KObjectSingleton
{
    /**
     * The data object cache
     *
     * @var string
     */
    private static $__cache = array();

    /**
     * Read data from a file, folder or url and return a data object
     *
     * @param  string  $path
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return ComPagesDataObject
     */
    public function createObject($path)
    {
        if(!isset($this->__cache[$path]))
        {
            if(!parse_url($path, PHP_URL_SCHEME) == 'http')
            {
                //Locate the data file
                if (!$path = $this->getObject('template.locator.factory')->locate('data://'.$path)) {
                    throw new InvalidArgumentException(sprintf('The data file "%s" cannot be located.', $path));
                }
            }

            if(!parse_url($path, PHP_URL_SCHEME) == 'http') {
                $result = $this->fromPath($path);
            } else {
                $result = $this->fromUrl($path);
            }

            //Create the data object
            $this->__cache[$path] = $result;
        }

        return $this->__cache[$path];
    }

    /**
     * Read a data from a file or folder
     *
     * @param  string  $path
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return ComPagesDataObject
     */
    public function fromPath($path)
    {
        //Get the data
        $result = array();
        foreach((array) $path as $file)
        {
            $data = $this->getObject('object.config.factory')->fromFile($file, false);
            $result = array_merge_recursive($result, $data);
        }

        return new ComPagesDataObject($result);
    }

    /**
     * Read a data from a file or folder
     *
     * @param  string  $path
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return ComPagesDataObject
     */
    public function fromUrl($url)
    {
        if(!ini_get('allow_url_fopen')) {
            throw new RuntimeException(sprintf('Cannot open url: "%s".', $url));
        }

        if(!$format = pathinfo($url, PATHINFO_EXTENSION)) {
            throw new InvalidArgumentException(sprintf('Cannot determine data type of "%s".', $url));
        }

        if(!$content = file_get_contents($url)) {
            throw new RuntimeException(sprintf('Cannot get url content of "%s".', $url));
        }

        $result = $this->getObject('object.config.factory')->fromString($format, $content, false);
        return new ComPagesDataObject($result);
    }
}