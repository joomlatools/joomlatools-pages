<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtImagesTemplateFilterImage extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOWEST,
        ));

        parent::_initialize($config);
    }

    public function filter(&$text)
    {
        //Do not filter the images if we are rendering the page
        if($this->getTemplate()->getLayout() !== NULL)
        {
            $matches = array();
            //First pass - Find images between <ktml:images></ktml:images>
            if(preg_match_all('#<ktml:images(.*)>(.*)<\/ktml:images>#siU', $text, $matches))
            {
                foreach($matches[0] as $key => $match)
                {
                    $attribs = $this->parseAttributes($matches[1][$key]);

                    //Convert class to array
                    if(isset($attribs['class'])) {
                        $attribs['class'] = explode(' ', $attribs['class']);
                    }

                    $result = $this->getTemplate()->helper('image.filter', $matches[2][$key], $attribs);
                    $text   = str_replace($match, $result, $text);
                }
            }

            //Second pass - Find other images
            $text = $this->getTemplate()->helper('image.filter', $text);
        }
    }
}