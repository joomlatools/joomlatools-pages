<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

trait ComPagesPageTrait
{
    private $__page;

    public function setPage($page)
    {
        $this->__page = $page;
        return $this;
    }

    public function getPage($path = null)
    {
        if(is_null($path))
        {
            if(!$this->__page instanceof ComPagesPageInterface)
            {
                $this->__page = $this->getObject($this->__page ?? 'page');

                if(!$this->__page instanceof ComPagesPageInterface)
                {
                    throw new UnexpectedValueException(
                        'Page: '.get_class($this->__page).' does not implement ComPagesPageInterface'
                    );
                }
            }

            $result = $this->__page;
        }
        else $result = $this->getObject('page.registry')->getPageEntity($path);

        return $result;
    }
}