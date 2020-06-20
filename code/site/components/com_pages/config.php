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

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_site_path = $config->site_path;

        //Load the site specific options
        $this->_loadOptions();
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'site_path' => Koowa::getInstance()->getRootPath().'/joomlatools-pages'
        ))->append(array(
            'page_cache'            => true,
            'page_cache_path'       =>  $config->site_path.'/cache/pages',
            'page_cache_validation' => true,

            'data_namespaces'       => array(),
            'data_cache'            => true,
            'data_cache_path'       => $config->site_path ? $config->site_path.'/cache/data' : false,
            'data_cache_validation' => true,

            'template_cache'            => true,
            'template_cache_path'       => $config->site_path ? $config->site_path.'/cache/templates' : false,
            'template_cache_validation' => true,

            'http_cache'                => false,
            'http_cache_path'           => $config->site_path ? $config->site_path.'/cache/responses': false,
            'http_cache_time'           => '15min',
            'http_cache_time_proxy'     => '2h',
            'http_cache_validation'     => true,
            'http_cache_control'        => array(),

            'http_resource_cache'       => JFactory::getConfig()->get('caching'),
            'http_resource_cache_time'  => '1day',
            'http_resource_cache_path'  => $config->site_path ? $config->site_path.'/cache/resources' : false,
            'http_resource_cache_force' => false,
            'http_resource_cache_debug' => (JDEBUG ? true : false),

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
            if(file_exists(JPATH_CONFIGURATION.'/configuration-pages.php'))
            {
                $config = (array) include JPATH_CONFIGURATION.'/configuration-pages.php';
                $options = array_replace_recursive($options, $config);
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
}