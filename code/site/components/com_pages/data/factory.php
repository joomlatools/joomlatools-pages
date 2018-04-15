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
     * @param  string  $format
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return ComPagesDataObject
     */
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

                $result = $this->fromPath($file);

            }
            else $result = $this->fromUrl($path, $format);

            //Create the data object
            $this->__cache[$path] = new ComPagesDataObject($result);
        }

        return $this->__cache[$path];
    }

    /**
     * Read a data from a file or folder
     *
     * @param  string  $path
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return array
     */
    public function fromPath($path)
    {
        //Get the data
        $result = array();
        foreach((array) $path as $file)
        {
            $url = trim(fgets(fopen($file, 'r')));
            if(parse_url($url, PHP_URL_SCHEME)) {
                $data = $this->fromUrl($url, pathinfo($file, PATHINFO_EXTENSION));
            } else {
                $data = $this->getObject('object.config.factory')->fromFile($file, false);
            }

            $result = array_merge_recursive($result, $data);
        }

        return $result;
    }

    /**
     * Read a data from a file or folder
     *
     * @param  string  $url
     * @param  string  $format
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return array
     */
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