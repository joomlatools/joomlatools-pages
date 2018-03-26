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
            'base_path' =>  Koowa::getInstance()->getRootPath().'/pages',
        ));

        parent::_initialize($config);
    }

    /**
     * Set the base path
     *
     * @param string $path The base path
     * @return KTemplateLocatorAbstract
     */
    public function setBasePath($path)
    {
        //Prevent base path from being reset
        if(!isset($this->_base_path)){
            parent::setBasePath($path);
        }

        return $this;
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
        //Qualify partial templates.
        if(dirname($info['url']) === '.')
        {
            if(empty($info['base'])) {
                throw new RuntimeException('Cannot qualify partial template path');
            }

            $path = dirname($info['base']);
        }
        else $path = dirname($info['url']);

        $file   = pathinfo($info['url'], PATHINFO_FILENAME);
        $format = pathinfo($info['url'], PATHINFO_EXTENSION);

        $path = str_replace(parse_url($info['url'], PHP_URL_SCHEME).'://', '', $info['url']);
        $path = $info['base'].'/'.$path;

        if($this->realPath($path)) {
           $path .= '/index.html';
       } else {
           $path .= '.html';
       }

        //Try to find the file
        $result = false;
        if ($results = glob($path.'*'))
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