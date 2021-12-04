<?php

class ComPagesTemplateFilterImage extends ComPagesTemplateFilterAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority'      => self::PRIORITY_LOW,
            'enabled'       => true,
            'base_url'      => (string) $this->getObject('request')->getBasePath(),
            'client_hints' => true
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
            if($this->getConfig()->client_hints)
            {
                $text .= '<meta http-equiv="Accept-CH" content="dpr, width, viewport-width, downlink" />';
                $text .= '<meta http-equiv="Accept-CH-Lifetime" content="86400" />';
            }
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

                    if($this->helper('image.supported', $src))
                    {
                        if(strpos($src, '://') === false)
                        {
                            $src = '/'.ltrim($src, '/');

                            //Prepend base
                            $base = $this->getConfig()->base_url;

                            if($base && strpos($src , $base ) !== 0) {
                                $src = $base.$src;
                            }
                        }

                        $attribs['url'] = $src;
                    }
                    else $attribs['url'] = $src;

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
                    $html = str_replace($matches[0][$key], $this->helper('image', $options), $html);
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
                $html .= $this->helper('image.import', 'bgset');

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

                if($srcset = $this->helper('image.srcset', $matches[3][$key], $options))
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