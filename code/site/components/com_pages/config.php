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

    protected $_boootstrapped;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_bootstrapped = false;

        $this->_site_path = $config->site_path;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'site_path' => Koowa::getInstance()->getRootPath().'/joomlatools-pages'
        ));
    }

    public function getSitePath()
    {
        return $this->_site_path;
    }

    public function bootstrap($path)
    {
        if(!$this->_boootstrapped)
        {
            $this->_site_path = $path;

            //Configure object manager
            $this->_loadConfig($path);

            //Load the extensions
            $this->_loadExtensions($path);

            $this->_bootstrapped = true;
        }
    }

    public function isBootstrapped()
    {
        return $this->_boootstrapped;
    }

    protected function _loadConfig($path)
    {
        $config = array();

        $file =  $path.'/config.php';
        if(file_exists($file))
        {
            $config = $this->getObject('object.config.factory')->fromFile($file, false);

            //Load config options
            $base_path = $path;

            $directory = $this->getObject('object.bootstrapper')->getComponentPath('pages');
            $options   = include $directory.'/resources/config/site.php';

            //Set config options
            foreach($options['identifiers'] as $identifier => $values) {
                $this->getConfig($identifier)->merge($values);
            }

            //Set config options
            foreach($options['extensions'] as $identifier => $values) {
                $this->getConfig($identifier)->merge($values);
            }

            //Add the cacheable behavior, if http cache is enabled
            if(isset($config['http_cache']) && $config['http_cache']) {
                $this->getObject('dispatcher')->addBehavior('cacheable');
            }

            //Set the request headers
            if(isset($config['headers'])) {
                $this->getObject('response')->getHeaders()->add($config['headers'], true);
            }

            //Configure the Joomla template
            if(isset($config['template']) || isset($config['template_config']))
            {
                if(isset($config['template'])) {
                    $template = $config['template'];
                } else {
                    $template = JFactory::getApplication()->getTemplate();
                }

                if(isset($config['template_config']) && is_array($config['template_config'])) {
                    $params = $config['template_config'];
                } else {
                    $params = null;
                }

                JFactory::getApplication()->setTemplate($template, $params);
            }
        }

        return $config;
    }

    protected function _loadExtensions($path)
    {
        //Add extension locators
        $this->getObject('manager')->getClassLoader()->registerLocator(new ComPagesClassLocatorExtension(array(
            'namespaces' => array('\\'  => $path.'/extensions')
        )));

        $this->getObject('manager')->registerLocator('com:pages.object.locator.extension');

        //Register event subscribers
        foreach (glob($path.'/extensions/subscriber/[!_]*.php') as $filename) {
            $this->getObject('event.subscriber.factory')->registerSubscriber('ext:subscriber.'.basename($filename, '.php'));
        }

        //Register template filters
        foreach (glob($path.'/extensions/template/filter/[!_]*.php') as $filename) {
            $this->getConfig('com://site/pages.template.default')->merge(['filters' => ['ext:template.filter.'.basename($filename, '.php')]]);
        }

        //Register template function
        foreach (glob($path.'/extensions/template/function/[!_]*.php') as $filename) {
            $this->getConfig('com://site/pages.template.default')->merge(['functions' => [basename($filename, '.php') => $filename]]);
        }
    }
}