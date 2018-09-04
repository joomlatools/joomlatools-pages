<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesPageLocator extends KTemplateLocatorFile
{
    protected static $_name = 'page';
    protected $_formats;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path' =>  Koowa::getInstance()->getRootPath().'/joomlatools-pages',
        ]);

        parent::_initialize($config);
    }

    public function find(array $info)
    {
        $path = ltrim(str_replace(parse_url($info['url'], PHP_URL_SCHEME).'://', '', $info['url']), '/');

        $file   = pathinfo($path, PATHINFO_FILENAME);
        $format = pathinfo($path, PATHINFO_EXTENSION);
        $path   = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.');

        //Prepend the base path
        if($path) {
            $path = $this->getBasePath().'/'.$path;
        } else {
            $path = $this->getBasePath();
        }

        if($this->realPath($path.'/'.$file))
        {
            if($format) {
                $pattern = $path.'/'.$file.'/index.'.$format.'.*';
            } else {
                $pattern = $path.'/'.$file.'/index.'.'*';
            }
        }
        else
        {
            if($format) {
                $pattern = $path.'/'.$file.'.'.$format.'.*';
            } else {
                $pattern = $path.'/'.$file.'.*';
            }
        }

        //Try to find the file
        $result = false;
        if ($results = glob($pattern))
        {
            //Sort the files
            usort($results, function($a, $b) {
                return strlen($a) <=> strlen($b);
            });

            foreach($results as $file)
            {
                if($result = $this->realPath($file)) {
                    break;
                }
            }
        }

        return $result;
    }

    public function formats($url)
    {
        if(!isset($this->_formats[$url]))
        {
            $path = ltrim(str_replace(parse_url($url, PHP_URL_SCHEME).'://', '', $url), '/');

            $file = pathinfo($path, PATHINFO_FILENAME);
            $path = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.');

            //Prepend the base path
            if($path) {
                $path = $this->getBasePath().'/'.$path;
            } else {
                $path = $this->getBasePath();
            }

            if($this->realPath($path.'/'.$file)) {
                $pattern = $path.'/'.$file.'/index.'.'*';
            } else {
                $pattern = $path.'/'.$file.'.*';
            }

            //Try to find the file
            $formats = array();
            if ($results = glob($pattern))
            {
                $formats = array();
                foreach($results as $result)
                {
                    $file = pathinfo($result, PATHINFO_FILENAME);

                    if($format = pathinfo($file, PATHINFO_EXTENSION)) {
                        $formats[$format] = $format;
                    } else {
                        $formats['html'] = 'html';
                    }
                }
            }

            $this->_formats[$url] = $formats;
        }

        return $this->_formats[$url];
    }
}