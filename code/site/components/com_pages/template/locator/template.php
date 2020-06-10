<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateLocatorTemplate extends KTemplateLocatorFile
{
    protected static $_name = 'template';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path' => $this->getObject('com://site/pages.config')->getSitePath('extensions')
        ]);

        parent::_initialize($config);
    }

    public function find(array $info)
    {
        $path = str_replace(parse_url($info['url'], PHP_URL_SCHEME).'://', '', $info['url']);

        $file   = pathinfo($path, PATHINFO_FILENAME);
        $format = pathinfo($path, PATHINFO_EXTENSION);

        if($path = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.')) {
            $path = explode('/', $path);
        } else {
            $path = [];
        }

        if($extension = parse_url($info['url'], PHP_URL_HOST))
        {
            $base_path = $this->getBasePath();
            if(file_exists($base_path.'/'.$extension))
            {
                $base = $base_path.'/'.$extension.'/resources/templates';
                array_shift($path);

            }
            else $base = JPATH_SITE.'/components/com_pages/resources/templates';
        }

        $parts = array();

        //Add the base path
        $parts[] = $base;

        //Add the file path
        if($path) {
            $parts[] += $path;
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
            $pattern = $path.'.*';
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

    public function realPath($file)
    {
        return KTemplateLocatorAbstract::realPath($file);
    }
}