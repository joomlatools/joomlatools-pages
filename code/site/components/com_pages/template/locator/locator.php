<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateLocator extends KObject implements KObjectSingleton
{
    public function locatePage($path)
    {
        $url = $this->getPageUrl($path);

        return $this->getObject('template.locator.factory')->locate($url);
    }

    public function locateLayout($path)
    {
        $url = $this->getLayoutUrl($path);

        return $this->getObject('template.locator.factory')->locate($url);
    }

    public function getLayoutUrl($path)
    {
        return 'page://layouts/'.$path;
    }

    public function getPageUrl($path)
    {
        return 'page://pages/'.$path;
    }
}