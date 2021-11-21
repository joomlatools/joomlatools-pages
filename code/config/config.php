<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesConfig extends KObject implements KObjectSingleton
{
    protected $_site_path;
    protected $_log_path;
    protected $_cache_path;
    protected $_extension_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_site_path  = $config->site_path;
        $this->_cache_path = $config->cache_path;
        $this->_log_path   = $config->log_path;
        $this->_extension_path = KObjectConfig::unbox($config->extension_path);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->merge($this->__loadConfig($config->site_path));

        $config->append(array(
            'log_path'       => $config->log_path ? $config->log_path : $config->site_path.'/logs',
            'cache_path'     => $config->cache_path ? $config->log_path : $config->site_path.'/cache',
            'extension_path' => $config->extension_path ? $config->extension_path : $config->site_path.'/extensions',
            'debug'          => JFactory::getConfig()->get('debug'),
            'base_path'      => $config->base_path ?? JFactory::getApplication()->getCfg('live_site'),
            'script_name'    => $config->script_name ? trim($config->script_name, '/') : basename($_SERVER['SCRIPT_NAME']),
        ))->append(array(
            'page_cache'            => true,
            'page_cache_path'       => $config->cache_path,
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
            'http_cache_control'         => array(),
            'http_cache_control_private' => array('private', 'no-cache'),

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

    public function getScriptName()
    {
        //Handle Joomla context
        if(JFactory::getApplication()->getCfg('sef_rewrite'))
        {
           $path = '';
           if(!JFactory::getApplication()->getCfg('sef_rewrite')) {
                $path = !empty($path) ? $path.'/index.php' : 'index.php';
           }
        }
        else $path = $this->script_name;

        return $path;
    }

    public function getCachePath()
    {
        return $this->_cache_path;
    }

    public function getExtensionPath()
    {
        return (array) $this->_extension_path;
    }

    public function getLogPath()
    {
        return $this->_log_path;
    }

    public function get($option, $default = null)
    {
        return $this->getConfig()->get($option, $default);
    }

    public function getOptions()
    {
        return KObjectConfig::unbox($this->getConfig());
    }

    final public function __get($key)
    {
        return $this->get($key);
    }

    private function __loadConfig($path)
    {
        $options = array();

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

        return $options;
    }
}