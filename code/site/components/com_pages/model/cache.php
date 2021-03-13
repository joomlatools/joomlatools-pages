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

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('id', 'url', '', true);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type'         => 'cache',
            'cache_path'   =>  $this->getObject('com://site/pages.config')->getSitePath('cache'),
            'identity_key' => 'id',
        ]);

        parent::_initialize($config);
    }

    public function fetchData()
    {
        if(!isset($this->__data))
        {
            $this->__data = array();

            $dispatcher = $this->getObject('com://site/pages.dispatcher.http');
            if($dispatcher->isCacheable(false))
            {
                $state  = $this->getState();
                $files  = array();

                if ($state->isUnique())
                {
                    $file = $dispatcher->locateCache($state->id);

                    if(file_exists($file)) {
                        $files[] = $file;
                    }
                }
                else $files = glob($this->getConfig()->cache_path.'/response_*');

                foreach ($files as $file)
                {
                    $data = require $file;

                    $valid = $dispatcher->validateCache($data['validators'], true);

                    $this->__data[] = array(
                        'id'          => $data['id'],
                        'url'         => $data['url'],
                        'date'        => $this->getObject('date', array('date' => $data['headers']['Last-Modified'])),
                        'hash'        => $data['headers']['Etag'],
                        'token'       => $data['token'],
                        'format'      => $data['format'],
                        'language'    => $data['language'],
                        'collections' => $data['headers']['Content-Collections'] ?? array(),
                        'robots'      => isset($data['headers']['X-Robots-Tag']) ? array_map('trim', explode(',', $data['headers']['X-Robots-Tag'])) : array(),
                        'status'      => $data['status'],
                        'valid'       => $valid
                    );
                }
            }
        }

        return $this->__data;
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__data = null;

        parent::_actionReset($context);
    }
}