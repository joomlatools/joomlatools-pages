<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateLayout extends ComPagesTemplateAbstract
{
    protected $_parent;

    public function loadFile($url)
    {
        //Qualify the layout
        if(!parse_url($url, PHP_URL_SCHEME)) {
            $url = 'page://layouts/'.$url;
        }

        if(parse_url($url, PHP_URL_SCHEME) == 'page')
        {
            if(!$file = $this->getObject('template.locator.factory')->locate($url)) {
                throw new RuntimeException(sprintf('Cannot find layout: "%s"', $url));
            }

            //Load the layout
            $template = (new ComPagesObjectConfigFrontmatter())->fromFile($file);

            if(isset($template->page) || isset($template->pages)) {
                throw new KTemplateExceptionSyntaxError('Using "page or pages" in layout frontmatter is now allowed');
            }

            //Set the parent layout
            if($layout = KObjectConfig::unbox($template->layout))
            {
                if(is_array($layout)) {
                    $this->_parent = $layout['path'];
                } else {
                    $this->_parent = $layout;
                }
            }
            else $this->_parent = false;

            //Store the data and remove the layout
            $this->_data = KObjectConfig::unbox($template->remove('layout'));

            //Load the content
            $result = $this->loadString($template->getContent(), pathinfo($file, PATHINFO_EXTENSION), $url);
        }
        else $result = parent::loadFile($url);

        return $result;
    }

    public function getParent()
    {
        return $this->_parent;
    }
}