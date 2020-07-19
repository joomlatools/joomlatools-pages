<?php

class ComPagesEventSubscriberStaticcache extends ComPagesEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'enable'     => false,
            'cache_path' => false,
        ));

        parent::_initialize($config);
    }

    public function onAfterDispatcherCache(KEventInterface $event)
    {
        $response = $event->getTarget()->getResponse();
        $request  = $response->getRequest();

        //Only cache static pages without a query string
        if(!$event->getTarget()->getContentLocation()->getQuery() && $this->getConfig()->cache_path)
        {
            $path     = $event->getTarget()->getContentLocation()->getPath(true);
            $filename = array_pop($path);
            $format   = pathinfo($filename, PATHINFO_EXTENSION);

            //Create the filename
            if(empty($filename)) {
                $filename = 'index';
            }

            if(empty($format)) {
                $filename .= '.'.$request->getFormat();
            }

            //Create the directory
            if($dir = trim(implode('/', $path), '/')) {
                $dir  = $this->getConfig()->cache_path.'/'.$dir;
            } else {
                $dir  = $this->getConfig()->cache_path;
            }

            $file = $dir.'/'.$filename;

            //Only cache statically if no max-age is defined
            if($response->isCacheable() && $response->getMaxAge() === NULL)
            {
                // Cache needs to be regenerated OR cache doesn't exist yet
                $regenerate = !$response->isNotModified() || !file_exists($file);

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
                if(file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}