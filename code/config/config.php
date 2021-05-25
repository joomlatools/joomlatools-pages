<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesConfig extends KObject implements KObjectSingleton
{
    protected $_site_path;
    protected $_cache_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_site_path  = $config->site_path;
        $this->_cache_path = $config->cache_path;

        //Load the site specific options
        $this->_loadOptions();
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'site_path'  => Koowa::getInstance()->getRootPath().'/joomlatools-pages',
        ))->append(array(
            'cache_path'  => $config->site_path.'/cache',
            'debug'       => JFactory::getConfig()->get('debug'),
        ))->append(array(
            'page_cache'            => true,
            'page_cache_path'       =>  $config->cache_path,
            'page_cache_validation' => true,

            'data_namespaces'       => array(),
            'data_cache'            => true,
            'data_cache_path'       => $config->cache_path ? $config->cache_path.'/data' : false,
            'data_cache_validation' => true,

            'template_debug'            => $config->template_debug ?? $config->debug,
            'template_cache'            => true,
            'template_cache_path'       => $config->cache_path ? $config->cache_path.'/templates' : false,
            'template_cache_validation' => true,

            'http_cache'                 => false,
            'http_cache_path'            => $config->cache_path ? $config->cache_path.'/pages': false,
            'http_cache_time'            => false,
            'http_cache_time_browser'    => null,
            'http_cache_validation'      => true,
            'http_cache_control'         => array(),
            'http_cache_control_private' => array('private', 'no-cache'),

            'http_static_cache'         => getenv('PAGES_STATIC_ROOT') ? true : false,
            'http_static_cache_path'    => getenv('PAGES_STATIC_ROOT') ? getenv('PAGES_STATIC_ROOT') : false,

            'http_client_cache'       => JFactory::getConfig()->get('caching'),
            'http_client_cache_path'  => $config->cache_path ? $config->cache_path.'/responses' : false,
            'http_client_cache_debug' => $config->http_client_cache_debug ?? $config->debug,

            'collections' => array(),
            'redirects'   => array(),
            'page'        => array(),
            'sites'       => array('[*]' => JPATH_ROOT.'/joomlatools-pages'),
            'headers'     => array(),

            'composer_path' => $config->site_path.'/vendor',
        ));
    }

    public function getSitePath($path = null)
    {
        //If the site path is empty do not try to return a path
        if($this->_site_path && $path) {
            $result = $this->_site_path.'/'.$path;
        } else {
            $result = $this->_site_path;
        }

        return $result;
    }

    public function getCachePath()
    {
        return $this->_cache_path;
    }

    public function get($option, $default = null)
    {
        return $this->getConfig()->get($option, $default);
    }

    public function getOptions()
    {
        return KObjectConfig::unbox($this->getConfig());
    }

    protected function _loadOptions()
    {
        $options = array();

        //Load site config
        if($path = $this->getSitePath())
        {
            //Get the defaults
            $options = KObjectConfig::unbox($this->getConfig());

            //Load default config options
            $files = glob(JPATH_CONFIGURATION.'/*pages.php');
            if(!empty($files) && file_exists($files[0]))
            {
                $config = (array) include $files[0];
                $options = array_replace_recursive($options, $config);
            }

            //Register the composer class locator
            if(isset($options['composer_path']) && file_exists($options['composer_path']))
            {
                $this->getObject('manager')->getClassLoader()->registerLocator(
                    new KClassLocatorComposer(array(
                            'vendor_path' => $options['composer_path']
                        )
                    ));
            }

            //Load site config options
            if(file_exists($path.'/config.php'))
            {
                $config   = $this->getObject('object.config.factory')->fromFile($path.'/config.php', false);
                $options = array_replace_recursive($options, $config);
            }

            $this->getConfig()->merge($options);
        }

        return $options;
    }

    final public function __get($key)
    {
        return $this->get($key);
    }
}