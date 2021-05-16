<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtMediaTemplateFilterImage extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_LOW,
            'enabled'  => JDEBUG ? false : true,
            'origins'  => [],
            'log_path' => $this->getObject('com:pages.config')->getSitePath('logs'),
        ));

        parent::_initialize($config);
    }

    public function isEnabled()
    {
        return (bool) $this->getConfig()->enabled;
    }

    public function filter(&$text)
    {
        //Filter the images only at the end of the rendering cycle
        if($this->isEnabled())
        {
            $matches = array();
            $images  = array();

            //Find images between <ktml:images></ktml:images>
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
                    $images[$key] = $this->filterBackgroundImages($result, $attribs);

                    $text = str_replace($match, '<ktml:images:'.$key.'>', $text);
                }
            }

            //Filter image elements
            $text =  $this->filterImages($text);

            //Filter the background images
            $text = $this->filterBackgroundImages($text);

            //Find images between <ktml:images></ktml:images>
            foreach($images as $key => $value) {
                $text = str_replace('<ktml:images:'.$key.'>', $value, $text);
            }

            //Add client hints
            $text .= '<meta http-equiv="Accept-CH" content="dpr, width, viewport-width, downlink" />';
            $text .= '<meta http-equiv="Accept-CH-Lifetime" content="86400" />';
        }
    }

    public function filterImages($html, $config = array())
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
                if($src && $valid)
                {
                    //Convert class to array
                    if(isset($attribs['class'])) {
                        $attribs['class'] = explode(' ', $attribs['class']);
                    }

                    //Copy images
                    if(strpos($src, '://') !== false && count($this->getConfig()->origins))
                    {
                        foreach($this->getConfig()->origins as $origin => $path)
                        {
                            if(stripos($src, $origin) === 0)
                            {
                                if ($extension = pathinfo($src, PATHINFO_EXTENSION))
                                {
                                    $base = $this->getObject('com://site/pages.config')->getSitePath();
                                    $dest = $path .'/'. hash("crc32b", $src).'.' . $extension;

                                    if(!file_exists($base . $dest))
                                    {
                                        if(copy($src, $base . $dest) == false)
                                        {
                                            $log = $this->getConfig()->log_path.'/image.log';
                                            error_log(sprintf('Could copy image: %s'."\n", $src), 3, $log);
                                        }
                                        else $src = $dest;
                                    }
                                    else $src = $dest;
                                }
                            }
                        }
                    }

                    if($this->helper('ext.media.image.supported', $src)) {
                        $attribs['url'] = '/'.ltrim($src, '/');
                    } else {
                        $attribs['url'] = $src;
                    }

                    unset($attribs['src']);

                    //Strip data- prefix
                    foreach($attribs as $name => $value)
                    {
                        if(strpos($name, 'data-') !== false)
                        {
                            unset($attribs[$name]);

                            $name = str_replace('data-', '', $name);
                            $attribs[$name] = $value;
                        }
                    }

                    //Rename hyphen to underscore
                    $options = array();
                    foreach(array_replace_recursive($config, $attribs) as $name => $value)
                    {
                        $name = str_replace('-', '_', $name);
                        $options[$name] = $value;
                    }

                    //Covert false/true
                    foreach($options as $name => $value)
                    {
                        if($value === 'true') {
                            $value = true;
                        }

                        if($value === 'false') {
                            $value = false;
                        }

                        $options[$name] = $value;
                    }

                    //Filter the images
                    $html = str_replace($matches[0][$key], $this->helper('ext.media.image', $options), $html);
                }
            }
        }

        return $html;
    }

    public function filterBackgroundImages($html, $config = array())
    {
        $matches = array();
        if(preg_match_all('#<[a-zA-Z0-9+\#.-]+(\s[^>]*?(background-image\s*:\s*url\((.+)\);).*)>#iU', $html, $matches))
        {
            foreach($matches[1] as $key => $match)
            {
                $html .= $this->helper('ext.media.image.import', 'bgset');

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


                //Rename hyphen to underscore
                $options = array();
                foreach(array_replace_recursive($config, $attribs) as $name => $value)
                {
                    $name = str_replace('-', '_', $name);
                    $options[$name] = $value;
                }


                if($srcset = $this->helper('ext:media.image.srcset', $matches[3][$key], $options))
                {
                    $attribs['data-sizes'] = 'auto';
                    $attribs['data-bgset'] = implode(',', $srcset);

                    if(isset($attribs['class'])) {
                        $attribs['class'] = $attribs['class'].' lazyload';
                    } else {
                        $attribs['class'] = 'lazyload';
                    }

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

        return $html;
    }
}