<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDataLocator extends KTemplateLocatorFile
{
    protected static $_name = 'data';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path' =>  $this->getObject('pages.config')->getSitePath('data'),
        ]);

        parent::_initialize($config);
    }

    public function setBasePath($path)
    {
        $this->_base_path = rtrim($path, '/');
        return $this;
    }

    public function locate($url)
    {
        $base_path = $this->getBasePath();

        if(!isset($this->_locations[$base_path.'/'.$url]))
        {
            $info = array(
                'url'   => $url,
                'path'  => '',
            );

            $this->_locations[$base_path.'/'.$url] = $this->find($info);
        }

        return $this->_locations[$base_path.'/'.$url];
    }

    /**
     * Override KTemplateLocatorFile::find() to allow limiting find() to only files by providing a path
     * that contains a glob wildcard as extension. {@see ComPagesDataRegistry::fromFile()}
     */
    public function find(array $info)
    {
        $path = str_replace(parse_url($info['url'], PHP_URL_SCHEME).'://', '', $info['url']);

        $file   = pathinfo($path, PATHINFO_FILENAME);
        $format = pathinfo($path, PATHINFO_EXTENSION);
        $path   = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.');

        $parts = array();

        //Add the base path
        if($base = $this->getBasePath()) {
            $parts[] = $base;
        }

        //Add the file path
        if($path) {
            $parts[] = $path;
        }

        //Add the file
        $parts[] = $file;

        //Create the path
        $path = implode('/', $parts);

        //Append the format
        if($format) {
            $path = $path.'.'.$format;
        }

        if(!$result = $this->realPath($path))
        {
            $pattern = $format ? $path : $path.'.*';
            $results = glob($pattern);

            //Try to find the file
            if ($results)
            {
                foreach($results as $file)
                {
                    if($result = $this->realPath($file)) {
                        break;
                    }
                }
            }
        }

        return $result;
    }
}