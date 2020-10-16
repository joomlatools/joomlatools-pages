<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtMediaTemplateHelperImage extends ExtMediaTemplateHelperLazysizes
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'max_width' => 1920,
            'min_width' => 320,
            'max_dpr'   => 3,
            'base_url'  => $this->getObject('request')->getBaseUrl(),
            'base_path' => $this->getObject('com://site/pages.config')->getSitePath(),
            'exclude'    => ['svg'],
            'suffix'     => '',
            'parameters'     => ['auto' => 'true'],
            'parameters_lqi' => ['auto' => 'compress', 'fm' => 'jpg', 'q' => 50]
        ));

        parent::_initialize($config);
    }

    public function __invoke($config = array())
    {
        $config = new KObjectConfigJson($config);
        $config->append(array(
            'url'     => '',
            'alt'     => '',
            'class'   => [],
            'width'   => null,
            'height'  => null,
            'max_width' => $this->getConfig()->max_width,
            'min_width' => $this->getConfig()->min_width,
            'max_dpr'   => $this->getConfig()->max_dpr,
            'lazyload'  => true, //progressive, inline
        ))->append(array(
            'attributes' => array('class' => $config->class),
        ));

        //Set lazyload class for lazysizes
        if($config->lazyload) {
            $config->attributes->class->append(['lazyload']);
        }

        //Get the image format
        $format  = strtolower(pathinfo($config->url, PATHINFO_EXTENSION));
        $exclude = KObjectConfig::unbox($this->getConfig()->exclude);

        if($this->supported($config->url))
        {
            $html = $this->import(); //import lazysizes script

            //Build path for the high quality image
            $hqi_url = $this->url($config->url);

            //Responsive image with auto sizing through lazysizes
            if(!isset($config['width']) && !isset($config['height']))
            {
                $srcset = $this->srcset($config->url, $config);

                //Find the width for the none-responsive image
                if (stripos($config->max_width, '%') !== false) {
                    $width = ceil($this->getConfig()->max_width / 100 * (int)$config->max_width);
                } else {
                    $width = $config->max_width;
                }

                foreach(array_reverse(array_keys($srcset)) as $size)
                {
                    if($size > $width) {
                        $width = $size; break;
                    }
                }

                if($config->lazyload !== false)
                {
                    $lazyload = array_map('trim', explode(',', $config->lazyload));

                    if(in_array('progressive', $lazyload))
                    {
                        $config->attributes->class->append(['progressive']);

                        $parameters = array();
                        $parameters['w'] = (int) ($width / 8);

                        $lqi_url = $this->url_lqi($config->url, $parameters, in_array('inline', $lazyload));
                    }
                    else $lqi_url = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

                    //Combine low quality image as srcset value and a data-srcset attribute and
                    //provide a fallback if javascript is disabled
                    $html .= '<noscript>';
                    $html .=    '<img width="'.$width.'" src="'.$hqi_url.'&w='.$width.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                    $html .= '</noscript>';
                    $html .='<img width="'.$width.'" data-srclow="'. $lqi_url.'"
                        data-sizes="auto"
                        data-srcset="'. implode(', ', $srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                }
                else
                {
                    $html .='<img width="'.$width.'" src="'.$hqi_url.'&w='.$width.'"
                        srcset="'. implode(', ', $srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                }
            }
            //Fixed image with display density description
            else
            {
                $width  = $config->width;
                $height = $config->height;

                if(!$width && !$height)
                {
                    $size = @getimagesize($this->getConfig()->base_path.$config->url);
                    $width = $size[0];
                }

                $srcset = [];
                if($height)
                {
                    for($i=1; $i <= $config->max_dpr; $i++) {
                        $srcset[] = sprintf($hqi_url.'&h=%1$s&dpr=%2$d %2$dx', $height, $i);
                    }

                    $size    = 'height="'.$height.'"';
                    $hqi_url = $hqi_url.'&h='.$height;
                }
                else
                {
                    for($i=1; $i <= $config->max_dpr; $i++) {
                        $srcset[] = sprintf($hqi_url.'&w=%1$s&dpr=%2$d %2$dx', $width, $i);
                    }

                    $size    = 'width="'.$width.'"';
                    $hqi_url = $hqi_url.'&w='.$width;
                }

                if($config->lazyload !== false)
                {
                    $lazyload =  array_map('trim', explode(',', $config->lazyload));

                    if(in_array('progressive', $lazyload))
                    {
                        $parameters = array();

                        if($height) {
                            $parameters['h'] = (int) ($height / 8);
                        } else {
                            $parameters['w'] = (int) ($width / 8);
                        }

                        $lqi_url = $this->url_lqi($config->url, $parameters, in_array('inline', $lazyload));
                    }
                    else $lqi_url = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

                    //Combine low quality image as srcset value and a data-srcset attribute and
                    //provide a fallback if javascript is disabled
                    $html .= '<noscript>';
                    $html .=    '<img '.$size.' src="'.$hqi_url.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                    $html .= '</noscript>';
                    $html .='<img '.$size.' data-srclow="'. $lqi_url.'"
                      data-srcset="'. implode(',', $srcset).'"
                      alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';

                }
                else
                {
                    $html .='<img '.$size.' src="'.$hqi_url.'"
                        srcset="'. implode(', ', $srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                }
            }
        }
        else
        {
            $width  = $config->width;
            $height = $config->height;

            if(!$width && !$height)
            {
                $size = @getimagesize($this->getConfig()->base_path.$config->url);
                $width = $size[0];
            }

            if($height) {
                $size = 'height="'.$height.'"';
            } else {
                $size = 'width="'.$width.'"';
            }

            $html ='<img '.$size.'src="'.$config->url.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
        }

        return $html;
    }

    public function url($url, $parameters = array())
    {
        $config = new KObjectConfigJson($parameters);
        $config->append($this->getConfig()->parameters);

        if($this->supported($url))
        {
            $url   = KHttpUrl::fromString($url);
            $query = array_merge(array_filter(KObjectConfig::unbox($config)), $url->query);

            ksort($query); //sort alphabetically
            $url->query = $query;

            if($this->getConfig()->suffix) {
                $url->setPath($url->getPath().'.'.$this->getConfig()->suffix);
            }
        }

        return $url;
    }

    public function url_lqi($url, $parameters = array(), $data_url = false)
    {
        $config = new KObjectConfigJson($parameters);
        $config->append($this->getConfig()->parameters_lqi);
        $config->append(array(
            'fm' => 'jpg'
        ));

        if($this->supported($url))
        {
            $result = (string) $this->url($url, $config);

            //Generate data url for low quality image
            if($data_url)
            {
                $context = stream_context_create([
                    "ssl" => [
                        "verify_peer"      =>false,
                        "verify_peer_name" =>false,
                    ],
                ]);

                if($data = @file_get_contents($this->getConfig()->base_url.'/'.trim($result, '/'), false, $context)) {
                    $result = 'data:image/jpg;base64,'.base64_encode($data);
                }
            }
        }
        else $result = $url;

        return $result;
    }

    public function srcset($url, $parameters = array())
    {
        $config = new KObjectConfigJson($parameters);
        $config->append(array(
            'max_width' => $this->getConfig()->max_width,
            'min_width' => $this->getConfig()->min_width,
            'max_dpr'   => $this->getConfig()->max_dpr,
        ));

        $srcset = [];
        if($this->supported($url))
        {
            if (!$url instanceof KHttpUrlInterface) {
                $url = KHttpUrl::fromString($url);
            }

            if (stripos($config->max_width, '%') !== false) {
                $config->max_width = ceil($this->getConfig()->max_width / 100 * (int)$config->max_width);
            }

            if (stripos($config->min_width, '%') !== false) {
                $config->min_width = ceil($this->getConfig()->min_width / 100 * (int)$config->min_width);
            }

            $file  = $this->getConfig()->base_path . '/' . trim($url->getPath(), '/');
            $sizes = $this->_calculateSizes($file, $config->max_width * $config->max_dpr, $config->min_width);

            //Build path for the high quality image
            $hqi_url = $this->url($url);

            foreach ($sizes as $size)
            {
                $hqi_url->query['w'] = $size;
                $srcset[$size] = sprintf((string)$hqi_url . ' %1$sw', $size);
            }
        }

        return $srcset;
    }

    public function supported($url)
    {
        $result = true;

        if(!$url instanceof KHttpUrlInterface) {
            $url = KHttpUrl::fromString($url);
        }

        $format  = strtolower(pathinfo($url->getPath(), PATHINFO_EXTENSION));
        $exclude = KObjectConfig::unbox($this->getConfig()->exclude);

        if(in_array($format, $exclude) || $url->scheme == 'data' || substr($url->scheme, 0, 4) == 'http') {
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
    protected function _calculateSizes($file, $max_width, $min_width = 320)
    {
        $min_filesize = 1024 * 10; //10kb
        $modifier     = 0.7;       //70% (each image should be +/- 30% smaller in expected size)

        //Get dimensions
        list($width, $height) = @getimagesize($file);

        //Get filesize
        $filesize = @filesize($file);

        $sizes = array();
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
                $sizes[] = $width;
            }
        }

        if(empty($sizes))
        {
            if(is_int($max_width) && $max_width < $width) {
                $sizes[] = $max_width;
            } else {
                $sizes[] = $width;
            }
        }

        return $sizes;
    }
}