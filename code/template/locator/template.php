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
            'base_path' => $this->getObject('com://site/pages.config')->getSitePath('templates')
        ]);

        parent::_initialize($config);
    }

    public function find(array $info)
    {
        $scheme = parse_url($info['url'], PHP_URL_SCHEME);
        $path   = str_replace([$scheme.'://', $scheme.':'], '', $info['url']);

        $file   = pathinfo($path, PATHINFO_FILENAME);
        $format = pathinfo($path, PATHINFO_EXTENSION) ?: 'html';

        if($path = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.')) {
            $path = explode('/', $path);
        } else {
            $path = [];
        }

        $base = $this->getBasePath();
        if($extension = parse_url($info['url'], PHP_URL_HOST))
        {
            if($extension != 'pages')
            {
                $base = $this->getObject('manager')
                    ->getClassLoader()
                    ->getLocator('extension')
                    ->getNamespace(ucfirst($extension));
            }
            else $base = $this->getObject('object.bootstrapper')->getComponentPath('pages');

            array_shift($path);

            $base .= '/resources/templates';
        }

        $parts = array();

        //Add the base path
        $parts[] = $base;

        //Add the file path
        if($path) {
            $parts = array_merge($parts, $path);
        }

        //Create the path
        $path = implode('/', $parts);

        if($this->realPath($path.'/'.$file)) {
            $pattern = $path.'/'.$file.'/index.'.$format.'*';
        } else {
            $pattern = $path.'/'.$file.'.'.$format.'*';
        }

        //Try to find the file
        $result = false;
        if ($results = glob($pattern))
        {
            foreach($results as $file)
            {
                if($result = $this->realPath($file)) {
                    break;
                }
            }
        }

        return $result;
    }

    public function normalise($url)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $path   = str_replace(array('/', '\\', $scheme.'://', $scheme.':'), DIRECTORY_SEPARATOR, $url);

        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');

        $absolutes = array();
        foreach ($parts as $part)
        {
            if ('.' == $part) {
                continue;
            }

            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        $path = implode(DIRECTORY_SEPARATOR, $absolutes);
        $url  = $scheme ? $scheme.':'.$path : $path;

        return $url;
    }

    public function realPath($file)
    {
        return KTemplateLocatorAbstract::realPath($file);
    }
}