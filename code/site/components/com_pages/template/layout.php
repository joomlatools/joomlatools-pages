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

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' => 'page://layouts',
        ));

        parent::_initialize($config);
    }

    public function loadFile($url)
    {
        $url = $this->qualify($url);

        if(parse_url($url, PHP_URL_SCHEME) == 'page')
        {
            if(!$file = $this->findFile($url)) {
                throw new RuntimeException(sprintf('Cannot find layout: "%s"', $url));
            }

            //Load the layout
            $layout = (new ComPagesTemplateFile())->fromFile($file);

            if(isset($layout->page) || isset($layout->pages)) {
                throw new KTemplateExceptionSyntaxError('Using "page or pages" in layout frontmatter is now allowed');
            }

            //Set the parent layout
            if($layout->layout) {
                $this->_parent = $layout->layout;
            } else {
                $this->_parent = false;
            }

            //Store the data
            $this->_data = KObjectConfig::unbox($layout);

            //Store the filename
            $this->_filename = $file;

            //Load the content
            $result = $this->loadString($layout->getContent(), pathinfo($file, PATHINFO_EXTENSION), $url);
        }
        else $result = parent::loadFile($url);

        return $result;
    }

    public function getParent()
    {
        return $this->_parent;
    }
}