<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherHttp extends ComKoowaDispatcherHttp
{
    public function getRequest()
    {
        $request = parent::getRequest();

        //Get the page path
        $path = $request->getUrl()->getPath();
        $path = ltrim(str_replace(array($request->getSiteUrl()->getPath(), 'index.php'), '', $path), '/');

        //Handle the site root case eg. http://mysite.com/
        $path = 'page://pages/'.($path ?: 'index');

        //Add the format to the path if not present
        $request->query->file = pathinfo($path, PATHINFO_BASENAME);
        $request->query->path = pathinfo($path, PATHINFO_DIRNAME);

        return $request;
    }
}