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
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);
        $this->getState()
            ->insert('path', 'url')
            ->insert('file', 'cmd', '', true, array('path'));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'identity_key' => 'file',
        ));

        parent::_initialize($config);
    }

    protected function _actionFetch(KModelContext $context)
    {
        if (!$this->getState()->isUnique())
        {
            $files = array();
            $path  = $this->getState()->path;

            //Locate the template
            if ($file = $this->getObject('com:pages.template.page')->loadFile($path)->getFilename())
            {
                $iterator = new FilesystemIterator(dirname($file));
                while( $iterator->valid() )
                {
                    $file = pathinfo($iterator->current()->getRealpath(), PATHINFO_FILENAME);

                    if($file != 'index') {
                        $files[] = $this->loadPage($path, $file);;
                    }

                    $iterator->next();
                }

                $context->entity = $files;
            }
        }
        else
        {
            $path = $this->getState()->path;
            $file = $this->getState()->file;

            //Locate the template
            if ($properties = $this->loadPage($path, $file)) {
                $context->entity = $properties;
            }
        }

        return parent::_actionCreate($context);
    }

    public function loadPage($path, $file)
    {
        $template = $this->getObject('com:pages.template.page')->loadFile($path.'/'.$file);

        //Get the properties
        $properties = $template->getData();
        $properties['path']    = $path;
        $properties['file']    = $file;
        $properties['date']    = filemtime($template->getFilename());
        $properties['content'] = $template->render($properties);

        return $properties;
    }
}