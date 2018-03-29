<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesTemplateLocatorPage extends KTemplateLocatorFile
{
    /**
     * The locator name
     *
     * @var string
     */
    protected static $_name = 'page';

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' =>  Koowa::getInstance()->getRootPath().'/joomlatools-pages',
        ));

        parent::_initialize($config);
    }

    /**
     * Find a template path
     *
     * @param array  $info  The path information
     * @throws RuntimeException If the no base path exists while trying to locate a partial.
     * @return string|false The real template path or FALSE if the template could not be found
     */
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