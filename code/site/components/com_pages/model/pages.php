<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelPages extends KModelAbstract
{
    protected $_base_path;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);
        $this->getState()
            ->insert('path', 'url')
            ->insert('file', 'cmd', '', true, array('path'));

        $this->_base_path =  rtrim($config->base_path, '/');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path'    => 'page://pages',
            'identity_key' => 'path',
        ));

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        if ($this->getState()->isUnique())
        {
            $path = $this->getState()->path.'/'.$this->getState()->file;
            $url  = $this->qualifyPage($path);

            //Locate the template
            if ($file = $this->getObject('template.locator.factory')->locate($url))
            {
                $context->entity =  array(
                    'path' => $path,
                    'file' => $file,
                    'url'  => $url
                );
            }
        }
        else
        {
            $files = array();
            $path  = $this->getState()->path;
            $url   = $this->qualifyPage($path);

            //Locate the template
            if ($file = $this->getObject('template.locator.factory')->locate($url))
            {
                $iterator = new FilesystemIterator(dirname($file));
                while( $iterator->valid() )
                {
                    $file = $iterator->current();
                    $name = pathinfo($file->getRealpath(), PATHINFO_FILENAME);

                    if($name != 'index')
                    {
                        $files[] = [
                            'file' => $file->getRealPath(),
                            'path' => $path.'/'.$name,
                            'url'  => $url.'/'.$name
                        ];
                    }

                    $iterator->next();
                }

                $context->entity = $files;
            }
        }

        return parent::_actionCreate($context);
    }

    public function qualifyPage($path)
    {
        if(!parse_url($path, PHP_URL_SCHEME)) {
            $url  = $path = $this->_base_path.'/'.$path;
        } else {
            $url = $path;
        }

        return $url;
    }
}