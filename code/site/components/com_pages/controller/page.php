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

        return parent::_actionRender($context);
    }

    /**
     * Get the page
     *
     * @return ComPagesTemplatePage The page template object
     */
    public function getPage()
    {
        $site_path = $this->getRequest()->getSiteUrl()->getPath();

        $path = $this->getRequest()->getUrl()->getPath();
        $path = ltrim(str_replace(array($site_path, 'index.php'), '', $path), '/');

        //Handle the site root case eg. http://mysite.com/
        $path = 'page://'.($path ?: 'index');

        return $this->getObject('com:pages.template.page')->loadFile($path);
    }
}