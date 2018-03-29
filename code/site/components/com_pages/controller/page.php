<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesControllerPage extends KControllerView
{
    /**
     * Inject the page into the view
     *
     * @param  KControllerContextInterface $context A command context object
     * @throws KControllerExceptionFormatNotSupported If the requested format is not supported for the resource
     * @return string|bool The rendered output of the view or false if something went wrong
     */
    protected function _actionRender(KControllerContextInterface $context)
    {
        $this->getView()->setPage($this->getPage());

        if($result = parent::_actionRender($context))
        {
            //Set the title
            JFactory::getDocument()->setTitle($this->getView()->getTitle());

            //Set the description
            JFactory::getDocument()->setDescription($this->getView()->getDescription());
        }

        return $result;
    }

    /**
     * Get the page
     *
     * @return ComPagesTemplatePage The page template object
     */
    public function getPage()
    {
        $request = $this->getRequest();

        //Get the page path
        $path = $request->getUrl()->getPath();
        $path = ltrim(str_replace(array($request->getSiteUrl()->getPath(), 'index.php'), '', $path), '/');

        //Handle the site root case eg. http://mysite.com/
        $path = 'page://pages/'.($path ?: 'index');

        //Add the format to the path if not present
        $path = pathinfo($path, PATHINFO_EXTENSION) ? $path : $path.'.'.$request->getFormat();

        return $this->getObject('com:pages.page')->loadFile($path);
    }
}