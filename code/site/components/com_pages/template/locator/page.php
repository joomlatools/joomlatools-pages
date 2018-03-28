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
     * The root path
     *
     * @var string
     */
    protected $_base_path;

    /**
     * Constructor
     *
     * Prevent creating instances of this class by making the constructor private
     *
     * @param KObjectConfig $config   An optional ObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_base_path = rtrim($config->base_path, '/');
    }

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
     * Get the root path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->_base_path;
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
        $file   = pathinfo($info['url'], PATHINFO_FILENAME);
        $format = pathinfo($info['url'], PATHINFO_EXTENSION);
        $path   = pathinfo($info['url'], PATHINFO_DIRNAME);

        $path = ltrim(str_replace(parse_url($path, PHP_URL_SCHEME).'://', '', $path), '/');
        $path = $this->getBasePath().'/'.$path;

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

    /**
     * Prevent directory traversal attempts outside of the base path
     *
     * @param  string $file The file path
     * @return string The real file path
     */
    public function realPath($file)
    {
        $path = parent::realPath($file);

        if(!strpos($file, $this->getBasePath())) {
            return false;
        }
    }
}