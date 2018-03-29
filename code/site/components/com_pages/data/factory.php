<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

final class ComPagesDataFactory extends KObject implements KObjectSingleton
{
    /**
     * The data object cache
     *
     * @var string
     */
    private static $__cache = array();

    /**
     * Read a data from a file or folder
     *
     * @param  string  $path
     * @throws \InvalidArgumentException If the data file(s) could not be located
     * @throws RuntimeException
     * @return ComPagesDataObject
     */
    public function fromPath($path)
    {
        if(!isset($this->__cache[$path]))
        {
            //Locate the data file
            if (!$files = $this->getObject('template.locator.factory')->locate('data://'.$path)) {
                throw new InvalidArgumentException(sprintf('The data file "%s" cannot be located.', $path));
            }

            //Get the data
            $result = array();
            foreach((array) $files as $file)
            {
                $data = $this->getObject('object.config.factory')->fromFile($file, false);
                $result = array_merge_recursive($result, $data);
            }

            //Create the data object
            $this->__cache[$path] = new ComPagesDataObject($result);
        }

        return $this->__cache[$path];
    }
}