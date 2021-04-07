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

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'base_path' => $this->getObject('pages.config')->getSitePath(),
        ]);

        parent::_initialize($config);
    }

    public function find(array $info)
    {
        $path = ltrim(str_replace(parse_url($info['url'], PHP_URL_SCHEME).'://', '', $info['url']), '/');

        $file   = pathinfo($path, PATHINFO_FILENAME);
        $format = pathinfo($path, PATHINFO_EXTENSION) ?: 'html';
        $path   = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.');

        //Prepend the base path
        if($path) {
            $path = $this->getBasePath().'/'.$path;
        } else {
            $path = $this->getBasePath();
        }

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
}