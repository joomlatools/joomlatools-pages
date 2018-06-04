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

        //Hanlde the root
        if(!isset($request->query->file))
        {
            $request->query->file = 'index';
            $request->query->path = '.';
        }

        return $request;
    }
}