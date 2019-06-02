<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

final class ComPagesObjectManagerOptions extends KObject implements KObjectSingleton
{
    protected $_configured;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_configured = false;
    }

    public function configure()
    {
        if(!$this->isConfigured())
        {
            $file = $this->getConfig()->path.'/config.php';

            if(file_exists($file))
            {
                $config = $this->getObject('object.config.factory')->fromFile($file, false);
                $config['base_path'] = $this->getConfig()->path;

                $path    = $this->getObject('object.bootstrapper')->getComponentPath('pages');
                $options = include $path.'/resources/config/options.php';

                foreach($options['identifiers'] as $identifier => $values) {
                    $this->getConfig($identifier)->merge($values);
                }
            }

            $this->_configured = true;
            return true;
        }

        return false;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'path' => Koowa::getInstance()->getRootPath().'/joomlatools-pages',
        ));

        parent::_initialize($config);
    }

    public function isConfigured()
    {
        return $this->_configured;
    }
}