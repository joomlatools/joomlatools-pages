<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtImagesTemplateHelperImage extends ComPagesTemplateHelperAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'enable'    => JDEBUG ? false : true,
            'max_width' => 1920,
            'min_width' => 320,
            'base_url'  => $this->getObject('request')->getBaseUrl(),
            'base_path' => $this->getObject('com://site/pages.config')->getSitePath(),
            'exclude'    => ['svg'],
            'suffix'     => '',
            'parameters' => ['auto' => 'true']
        ));

        parent::_initialize($config);
    }

    public function __invoke($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'image'   => '',
            'alt'     => '',
            'class'   =>[],
            'width'   => null,
            'height'  => null,
            'max_width' => $this->getConfig()->max_width,
            'min_width' => $this->getConfig()->min_width,
            'preload'   => false,
            'lazyload'  => true,
        ))->append(array(
            'attributes' => array('class' => $config->class),
        ));

        //Set lazyload class for lazysizes
        if($config->lazyload) {
            $config->attributes->class[] = 'lazyload';
        }

        //Get the image format
        $format  = strtolower(pathinfo($config->image, PATHINFO_EXTENSION));
        $exclude = KObjectConfig::unbox($this->getConfig()->exclude);

        if($this->supported($config->image))
        {
            //Calculate the max width
            if(stripos($config->max_width, '%') !== false) {
                $config->max_width = ceil($this->getConfig()->max_width / 100 * (int) $config->max_width);
            }

            if(stripos($config->min_width, '%') !== false) {
                $config->min_width = ceil($this->getConfig()->min_width / 100 * (int) $config->min_width);
            }

            //Build path for the high quality image
            $hqi_url = $this->url($config->image);

            //Build the path for the low quality image
            $lqi_url = $this->url($config->image, ['bl' => 75, 'q' => 40]);

            //Responsive image with auto sizing through lazysizes
            $html = '';
            if(!isset($config['width']) && !isset($config['height']))
            {
                $breakpoints = $this->_calculateBreakpoints($config->image, $config->max_width, $config->min_width);

                $lqi_srcset = sprintf($lqi_url.'&fm=jpg&w=%1$s', $breakpoints[0]);

                //Generate data url for low quality image and preload it inline
                if($config['preload'])
                {
                    $context = stream_context_create([
                        "ssl" => [
                            "verify_peer"      =>false,
                            "verify_peer_name" =>false,
                        ],
                    ]);

                    if($data = @file_get_contents($this->getConfig()->base_url.$lqi_srcset, false, $context)) {
                        $srcset = 'data:image/jpg;base64,'.base64_encode($data);
                    }
                }

                $hqi_srcset = [];
                foreach($breakpoints as $breakpoint) {
                    $hqi_srcset[] = sprintf($hqi_url.'&w=%1$s %1$sw', $breakpoint);
                }

                if($config->lazyload)
                {
                    //Combine a normal src attribute with a low quality image as srcset value and a data-srcset attribute.
                    //Modern browsers will lazy load without loading the src attribute and all others will simply fallback
                    //to the initial src attribute (without lazyload).
                    //
                    //Set data-expaned to -10 to only load the image when it becomes visible in the viewport
                    $html .='<img width="'.$breakpoints[0].'" src="'.$hqi_url.'&w='.$breakpoints[0].'"
                        srcset="'. $lqi_srcset.'"
                        data-sizes="auto"
                        data-srcset="'. implode(', ', $hqi_srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'  data-expand="-10"  />';
                }
                else
                {
                    $html .='<img width="'.$breakpoints[0].'" src="'.$hqi_url.'&w='.$breakpoints[0].'"
                        srcset="'. implode(', ', $hqi_srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).' />';
                }
            }
            //Fixed image with display density description
            else
            {
                $width  = $config->width;
                $height = $config->height;

                if(!$width && !$height)
                {
                    $size = @getimagesize($this->getConfig()->base_path.$config->image);
                    $width = $size[0];
                }

                if($height)
                {
                    $srcset = array(
                        sprintf($hqi_url.'&h=%1$s&dpr=1 1x', $height),
                        sprintf($hqi_url.'&h=%1$s&dpr=2 2x', $height),
                        sprintf($hqi_url.'&h=%1$s&dpr=3 3x', $height),
                    );

                    $size = 'height="'.$height.'"';
                }
                else
                {
                    $srcset = array(
                        sprintf($hqi_url.'&w=%1$s&dpr=1 1x', $width),
                        sprintf($hqi_url.'&w=%1$s&dpr=2 2x', $width),
                        sprintf($hqi_url.'&w=%1$s&dpr=3 3x', $width),
                    );

                    $size = 'width="'.$width.'"';
                }

                //Combine transparent image as srcset value and a data-srcset attribute. In case disabled JavaScript is
                //disabled, fallback on the noscript element.
                //
                //Set data-expand to 300 to delay loading the image
                $html = '<noscript>';
                $html .=    '<img '.$size.' src="'.$hqi_url.'&w='.$width.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).' />';
                $html .= '</noscript>';
                $html .='<img '.$size.'
                srcset="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
                data-srcset="'. implode(',', $srcset).'"
                alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).' data-expand="300" />';
            }
        }
        else
        {
            $width  = $config->width;
            $height = $config->height;

            if(!$width && !$height)
            {
                $size = @getimagesize($this->getConfig()->base_path.$config->image);
                $width = $size[0];
            }

            if($height) {
                $size = 'height="'.$height.'"';
            } else {
                $size = 'width="'.$width.'"';
            }

            $html ='<img '.$size.'src="'.$config->image.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).' />';
        }

        return $html;
    }

    public function url($image, $parameters = array())
    {
        $config = new KObjectConfigJson($parameters);
        $config->append($this->getConfig()->parameters);

        $parts = parse_url($image);
        $query = array();

        if(isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query = array_merge(array_filter(KObjectConfig::unbox($config)), $query);

        if($this->getConfig()->suffix) {
            $parts['path'] = $parts['path'].'.'.$this->getConfig()->suffix;
        }

        $url = $parts['path'].'?'.urldecode(http_build_query($query));

        return $url;
    }

    public function filter($html, $options = array())
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
                    if($src && $valid && $this->supported($src))
                    {
                        //Convert class to array
                        if(isset($attribs['class'])) {
                            $attribs['class'] = explode(' ', $attribs['class']);
                        }

                        $attribs['image'] = '/'.ltrim($src, '/');
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

                        //Merge config and attribs
                        $config = array_merge_recursive($options, $attribs);
                        $html   = str_replace($matches[0][$key], $this->__invoke($config), $html);
                    }
                }
            }
        }

        return $html;
    }

    public function enabled()
    {
        return (bool) $this->getConfig()->enable;
    }

    public function supported($image)
    {
        $result = true;

        $format  = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        $exclude = KObjectConfig::unbox($this->getConfig()->exclude);

        if(in_array($format, $exclude) || strpos($image, 'data:') !== false || substr($image, 0, 4) == 'http') {
            $result = false;
        }

        return $result;
    }

    public function parseAttributes($string)
    {
        $result = array();

        if (!empty($string))
        {
            $pattern = '#(?(DEFINE)
                (?<name>[a-zA-Z][a-zA-Z0-9-_:]*)
                (?<value_double>"[^"]+")
                (?<value_none>[^\s>]+)
                (?<value>((?&value_double)|(?&value_none)))
            )
            (?<n>(?&name))[\s]*(=[\s]*(?<v>(?&value)))?#xs';

            if (preg_match_all($pattern, $string, $matches, PREG_SET_ORDER))
            {
                foreach ($matches as $match) {
                    if (!empty($match['n'])) {
                        $result[$match['n']] = isset($match['v']) ? trim($match['v'], '\'"') : '';
                    }
                }
            }
        }

        return $result;
    }

    /*
     * Dynamically calculate the response image breakpoints based on fixed filesize reduction
     *
     * Inspired by https://stitcher.io/blog/tackling_responsive_images-part_2
     */
    protected function _calculateBreakpoints($image, $max_width, $min_width)
    {
        $min_filesize = 1024 * 10; //10kb
        $modifier     = 0.7;       //70% (each image should be +/- 30% smaller in expected size)

        //Get dimensions
        list($width, $height) = @getimagesize($this->getConfig()->base_path.$image);

        //Get filesize
        $filesize = @filesize($this->getConfig()->base_path.$image);

        $breakpoints = array();
        if ($width < $max_width) {
            $breakpoints[] = $width;
        }

        $ratio   = $height / $width;
        $area    = $height * $width;

        $density = $filesize / $area;

        while(true)
        {
            $filesize *= $modifier;

            if ((int) $filesize < $min_filesize) {
                break;
            }

            $width = (int) floor(sqrt(( $filesize / $density) / $ratio));

            if ($width < $min_width) {
                break;
            }

            //Add the width
            if ($width < $max_width) {
                $breakpoints[] = $width;
            }
        }

        if(empty($breakpoints)) {
            $breakpoints[] = $max_width;
        }

        return $breakpoints;
    }
}