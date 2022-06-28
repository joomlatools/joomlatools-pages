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
    private $__files;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('id', 'url', '', true);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'type'  => 'cache',
            'debug' => $this->getObject('pages.config')->debug,
            'cache_path' =>  $this->getObject('pages.config')->getCachePath(),
            'identity_key' => 'id',
        ])->append([
            'behaviors'   => [
                'com:pages.model.behavior.paginatable',
                'com:pages.model.behavior.filterable',
            ],
        ]);

        parent::_initialize($config);
    }

    public function fetchFiles()
    {
        if(!isset($this->__files))
        {
            $files  = array();

            $offset = $this->getState()->offset;
            $limit  = $this->getState()->limit;

            $i = 1;
            if ($handle = opendir($this->getConfig()->cache_path))
            {
                while (false !== ($entry = readdir($handle)))
                {
                    if (!str_starts_with($entry, '.'))
                    {
                        if($limit && $i >= ($offset + $limit)) {
                            break;
                        }

                        if($i >= $offset) {
                            $files[] = $this->getConfig()->cache_path.'/'.$entry;
                        }

                        $i++;
                    }
                }

                closedir($handle);
            }

            $this->__files = $files;
        }

        return $this->__files;
    }

    protected function _beforeFetch(KModelContext $context)
    {
        $state  = $this->getState();

        $result = array();
        $files  = array();
        if ($state->isUnique())
        {
            $file = $this->getConfig()->cache_path . '/response_' . crc32($state->id) . '.php';

            if(file_exists($file)) {
                $files[] = $file;
            }
        }
        else $files = $this->fetchFiles();

        foreach ($files as $index => $file)
        {
            try
            {
                $data = require $file;

                if($data && is_array($data) && isset($data['id']))
                {
                    $result[] = array(
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
                    );
                }
                else throw new \RuntimeException("Cache file: $file is not valid");

            }
            catch(\Throwable $exception)
            {
                if(!$this->getConfig()->debug)
                {
                    unset($this->__files[$index]);
                    unlink($file);
                }
                else throw $exception;
            }
        }

        $context->data = $result;
    }

    protected function _actionCount(KModelContext $context)
    {
        return count(glob($this->getConfig()->cache_path.'/*'));
    }

    protected function _actionReset(KModelContext $context)
    {
        $this->__files = null;

        parent::_actionReset($context);
    }

    public function getHashState()
    {
        $state = [
            'limit' => $this->getState()->limit,
            'offset' => $this->getState()->offset,
        ];

        return $state;
    }

    protected function _actionHash(KModelContext $context)
    {
        $files = $this->fetchFiles();

        $result = array();
        foreach ($files as $file) {
            $result[basename($file)] = sprintf('%u', filemtime($file));
        }

        $hash = hash('crc32b', serialize($result));
        return $hash;
    }
}