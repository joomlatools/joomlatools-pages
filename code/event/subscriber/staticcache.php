<?php

class ComPagesEventSubscriberStaticcache extends ComPagesEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'enabled'    => false,
            'cache_path' => false,
        ));

        parent::_initialize($config);
    }

    public function onAfterDispatcherCache(KEventInterface $event)
    {
        $dispatcher = $event->getTarget();
        $response   = $dispatcher->getResponse();

        //Only cache static pages without a query string
        if($event->result && !$dispatcher->getCacheUrl()->getQuery() && $this->getConfig()->cache_path)
        {
            $file = $this->getFilePath($dispatcher);
            $dir  = dirname($file);

            //Only cache statically if must not revalidate
            if($file && !in_array('must-revalidate', $response->getCacheControl()))
            {
                // Cache needs to be regenerated OR cache doesn't exist yet
                $regenerate = !$dispatcher->isIdentical() || !file_exists($file);

                //Re-generated the static cache
                if($regenerate && $content = $response->getContent())
                {
                    if(!is_dir($dir) && (false === @mkdir($dir, 0777, true))) {
                        throw new RuntimeException(sprintf('The cache path "%s" does not exist', $dir));
                    }

                    if(!is_writable($dir)) {
                        throw new RuntimeException(sprintf('The cache path "%s" is not writable', $dir));
                    }

                    if(@file_put_contents($file, $content) === false) {
                        throw new RuntimeException(sprintf('The file cannot be cached in "%s"', $file));
                    }

                    //Override default permissions for cache files
                    @chmod($file, 0666 & ~umask());
                }
            }
            else
            {
                //Purge the static cache
                if(file_exists($file))
                {
                    unlink($file);

                    //Delete the folder if its empty
                    $dir = dirname($file);
                    if(!(new \FilesystemIterator($dir))->valid()) {
                        rmdir($dir);
                    }
                }
            }
        }
    }

    public function onAfterDispatcherPurge(KEventInterface $event)
    {
        $file = $this->getFilePath($event->getTarget());

        //Purge the static cache
        if(file_exists($file))
        {
            unlink($file);

            //Delete the folder if its empty
            $dir = dirname($file);
            if(!(new \FilesystemIterator($dir))->valid()) {
                rmdir($dir);
            }
        }
    }

    public function getCachePath()
    {
        return $this->getConfig()->cache_path;
    }

    public function getFilePath(ComPagesDispatcherHttp  $dispatcher)
    {
        $path     = $dispatcher->getCacheUrl()->getPath(true);
        $filename = array_pop($path);
        $format   = pathinfo($filename, PATHINFO_EXTENSION);

        //Create the filename
        if(empty($filename)) {
            $filename = 'index';
        }

        if(empty($format)) {
            $filename .= '.'. $dispatcher->getRequest()->getFormat();
        }

        //Create the directory
        if($dir = trim(implode('/', $path), '/')) {
            $dir  = $this->getCachePath().'/'.$dir;
        } else {
            $dir  = $this->getCachePath();
        }

        $file = $dir.'/'.$filename;

        return $file;
    }
}