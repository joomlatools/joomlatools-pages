<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelCache extends ComPagesModelCollection
{
    private $__data;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type'         => 'cache',
            'cache_path'   =>  $this->getObject('com://site/pages.config')->getSitePath('cache'),
            'identity_key' => 'id',
        ]);

        parent::_initialize($config);
    }

    public function fetchData($count = false)
    {
        if(!isset($this->__data))
        {
            $this->__data = array();

            foreach (glob($this->getConfig()->cache_path.'/response_*') as $file)
            {
                $data = require $file;

                $this->__data[] = array(
                    'id'        => str_replace('response_', '', basename($file, '.php')),
                    'url'       => $data['url'],
                    'date'      => $this->getObject('date', array('date' => $data['headers']['Last-Modified'])),
                    'hash'      => $data['headers']['Etag'],
                    'token'     => $data['token'],
                    'format'    => $data['format'],
                    'language'  => $data['page']['language'],
                    'tags'      => array_unique(array_column($data['collections'], 'type')),
                    'robots'    => isset($data['headers']['X-Robots-Tag']) ? array_map('trim', explode(',', $data['headers']['X-Robots-Tag'])) : array(),
                    'status'    => $data['status'],
                );
            }
        }

        return $this->__data;
    }

    public function getPrimaryKey()
    {
        //Cache doesn't have a primary key
        return array();
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }
}