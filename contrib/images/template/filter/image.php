<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtPagesTemplateFilterImage extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOWEST,
            'enable'   => JDEBUG ? false : true,
        ));

        parent::_initialize($config);
    }

    public function enabled()
    {
        return (bool) $this->getConfig()->enable;
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

                    //Filter image elements
                    $result = $this->filterImages($matches[2][$key], $attribs);

                    //Filter background images
                    $result = $this->filterBackgroundImages($result);

                    $text = str_replace($match, $result, $text);
                }
            }

            //Second pass - Filter image elements
            $text =  $this->filterImages($text);

            //Second pass- Filter the background images
            $text = $this->filterBackgroundImages($text);
        }
    }

    public function filterImages($html, $config = array())
    {
        if($this->enabled())
        {
            $matches = array();
            if(preg_match_all('#<img\s([^>]*?[\'\"][^>]*?)>(?!\s*<\/noscript>)#siU', $html, $matches))
            {
                foreach($matches[1] as $key => $match)
                {
                    $attribs = $this->parseAttributes($match);
                    $src     = $attribs['src'] ?? null;
                    $valid   = !isset($attribs['srcset']) && !isset($atrribs['data-srcset']) && !isset($attribs['data-src']);

                    //Only handle none responsive supported images
                    if($src && $valid && $this->getTemplate()->helper('image.supported', $src))
                    {
                        //Convert class to array
                        if(isset($attribs['class'])) {
                            $attribs['class'] = explode(' ', $attribs['class']);
                        }

                        $attribs['url'] = '/'.ltrim($src, '/');
                        unset($attribs['src']);

                        foreach($attribs as $name => $value)
                        {
                            if(strpos($name, 'data-') !== false)
                            {
                                unset($attribs[$name]);

                                $name = str_replace('data-', '', $name);

                                if($value === 'true') {
                                    $value = true;
                                }

                                if($value === 'false') {
                                    $value = false;
                                }

                                $attribs[$name] = $value;
                            }
                        }

                        //Replace config and attribs
                        $attribs = array_replace_recursive($config, $attribs);

                        //Rename hyphen to underscore
                        $options = array();
                        foreach($attribs as $name => $value)
                        {
                            $name = str_replace('-', '_', $name);
                            $options[$name] = $value;
                        }

                        //Filter the images
                        $html = str_replace($matches[0][$key], $this->getTemplate()->helper('image', $options), $html);
                    }
                }
            }
        }

        return $html;
    }

    public function filterBackgroundImages($html, $config = array())
    {
        if($this->enabled())
        {
            $matches = array();
            if(preg_match_all('#<\S+\s+(.*(background-image\s*:\s*url\((.+)\);*?).+)>#iU', $html, $matches))
            {
                foreach($matches[1] as $key => $match)
                {
                    $html .= $this->getTemplate()->helper('image.import', 'bgset');

                    $attribs = $this->parseAttributes($match);

                    foreach($attribs as $name => $value)
                    {
                        if(strpos($name, 'data-') !== false)
                        {
                            unset($attribs[$name]);

                            $name = str_replace('data-', '', $name);

                            if($value === 'true') {
                                $value = true;
                            }

                            if($value === 'false') {
                                $value = false;
                            }

                            $attribs[$name] = $value;
                        }
                    }

                    //Replace config and attribs
                    $attribs = array_replace_recursive($config, $attribs);

                    //Rename hyphen to underscore
                    $options = array();
                    foreach($attribs as $name => $value)
                    {
                        $name = str_replace('-', '_', $name);
                        $options[$name] = $value;
                    }

                    if($srcset = $this->getTemplate()->helper('image.srcset', $matches[3][$key], $options))
                    {
                        $attribs['data-sizes'] = 'auto';
                        $attribs['data-bgset'] = implode(',', $srcset);
                        $attribs['class']      = $attribs['class'].' lazyload';

                        $attribs['style'] = str_replace($matches[2][$key], '', $attribs['style']);

                        if(empty(trim($attribs['style'], '"'))) {
                            unset($attribs['style']);
                        }

                        $attribs = $this->buildAttributes($attribs);

                        //Filter the images
                        $html = str_replace($matches[1][$key], $attribs, $html);
                    }
                }
            }
        }

        return $html;
    }
}