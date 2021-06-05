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
            'base_path' => $this->getObject('com:pages.config')->getSitePath().'/images',
            'log_path'  => $this->getObject('com:pages.config')->getLogPath(),
            'exclude'    => ['svg'],
            'lazyload'   => true,
            'preload'    => false,
            'parameters'     => ['auto' => 'true'],
            'parameters_lqi' => ['auto' => 'compress', 'fm' => 'jpg', 'q' => 50]
        ));

        parent::_initialize($config);
    }

    public function __invoke($config = array())
    {
        $config = new ComPagesObjectConfig($config);
        $config->append(array(
            'url'     => '',
            'alt'     => '',
            'class'   => [],
            'width'   => null,
            'height'  => null,
            'max_width' => $this->getConfig()->max_width,
            'min_width' => $this->getConfig()->min_width,
            'max_dpr'   => $this->getConfig()->max_dpr,
            'lazyload'  => $this->getConfig()->lazyload,
            'preload'   => $this->getConfig()->preload,
        ))->append(array(
            'attributes' => array('class' => $config->class, 'decoding' => 'async'),
        ))->append(array(
            'attributes_container' => array('class' => clone $config->class),
            'attributes_noscript'  => clone $config->attributes,
        ));

        //Get the image format
        $format  = strtolower(pathinfo($config->url, PATHINFO_EXTENSION));
        $exclude = KObjectConfig::unbox($this->getConfig()->exclude);

        //Set the img container class
        $config->attributes_container->class->append(['img-container']);

        if($this->exists($config->url))
        {
            $html = $this->import(); //import lazysizes script

            //Set lazyload class for lazysizes
            if($config->lazyload)
            {
                $config->attributes->class->append(['lazyload']);
                $config->attributes_noscript->loading = 'lazy';

                $lazyload = KObjectConfig::unbox($config->lazyload);

                if(!is_array($lazyload)) {
                    $lazyload = array_map('trim', explode(',', $config->lazyload));
                }
            }

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

                //Calculate the image size
                list($width, $height) = $this->_calculateSize($config->url, $width);

                if($config->lazyload !== false)
                {
                    if(in_array('progressive', $lazyload))
                    {
                        $config->attributes->class->append(['lazyprogressive']);

                        $parameters = array();
                        $parameters['w'] = (int) ($width / 8);

                        $lqi_url = $this->url_lqi($config->url, $parameters, in_array('inline', $lazyload));
                    }
                    else $lqi_url = '';

                    //Combine low quality image and a data-srcset attribute and provide a fallback if javascript is disabled
                    $html .= '<span '.$this->buildAttributes($config->attributes_container).'>';
                    $html .= '<noscript>';
                    $html .=    '<img width="'.$width.'" height="'.$height.'" src="'.$hqi_url.'&w='.$width.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes_noscript).'>';
                    $html .= '</noscript>';
                    $html .='<img width="'.$width.'" height="'.$height.'" style="--lqi: '.sprintf("url('%s')", $lqi_url).'"
                        src="'.$this->_generatePlaceholder($width, $height).'"
                        data-sizes="auto"
                        data-srcset="'. implode(', ', $srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                    $html .= '</span>';
                }
                else
                {
                    $html .='<img width="'.$width.'" height="'.$height.'" src="'.$hqi_url.'&w='.$width.'"
                        srcset="'. implode(', ', $srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                }

                //Add preload link to head
                if($config->preload)
                {
                    $html .= '<link href="'.$hqi_url.'&w='.$width.'" rel="preload" as="image" 
                        imagesrcset="'. implode(', ', $srcset).'" imagesizes="100vw" />';

                }
            }
            //Fixed image with display density description
            else
            {
                //Calculate the image size
                list($width, $height) = $this->_calculateSize($config->url, $config->width, $config->height);

                $srcset = [];
                if($height)
                {
                    for($i=1; $i <= $config->max_dpr; $i++) {
                        $srcset[] = sprintf($hqi_url.'&h=%1$s&dpr=%2$d %2$dx', $height, $i);
                    }

                    $hqi_url = $hqi_url.'&h='.$height;
                }
                else
                {
                    for($i=1; $i <= $config->max_dpr; $i++) {
                        $srcset[] = sprintf($hqi_url.'&w=%1$s&dpr=%2$d %2$dx', $width, $i);
                    }

                    $hqi_url = $hqi_url.'&w='.$width;
                }

                if($config->lazyload !== false)
                {
                    if(in_array('progressive', $lazyload))
                    {
                        $config->attributes->class->append(['lazyprogressive']);

                        $parameters = array();

                        if($height) {
                            $parameters['h'] = (int) ($height / 8);
                        } else {
                            $parameters['w'] = (int) ($width / 8);
                        }

                        $lqi_url = $this->url_lqi($config->url, $parameters, in_array('inline', $lazyload));
                    }
                    else $lqi_url = '';

                    //Combine low quality image and a data-srcset attribute and provide a fallback if javascript is disabled
                    $html .= '<span '.$this->buildAttributes($config->attributes_container).'>';
                    $html .= '<noscript>';
                    $html .=    '<img width="'.$width.'" height="'.$height.'" src="'.$hqi_url.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes_noscript).'>';
                    $html .= '</noscript>';
                    $html .='<img width="'.$width.'" height="'.$height.'" style="--lqi: '.sprintf("url('%s')", $lqi_url).'"
                      src="'.$this->_generatePlaceholder($width, $height).'"
                      data-srcset="'. implode(',', $srcset).'"
                      alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                    $html .= '</span>';

                }
                else
                {
                    $html .='<img width="'.$width.'" height="'.$height.'"
                        src="'.$hqi_url.'"
                        srcset="'. implode(', ', $srcset).'"
                        alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
                }

                //Add preload link to head
                if($config->preload)
                {
                    $html .= '<link href="'.$hqi_url.'" rel="preload" as="image" 
                        imagesrcset="'. implode(', ', $srcset).'" imagesizes="100vw" />';
                }
            }
        }
        else
        {
            $html = '<span '.$this->buildAttributes($config->attributes_container).'>';

            if($config->width) {
                $config->attributes['width'] = $config->width;
            }

            if($config->height) {
                $config->attributes['height'] = $config->height;
            }

            if($this->supported($config->url) && !$this->exists($config->url)) {
                $html .= '<img class="lazymissing" src="'.$config->url.'" alt="Image Not Found: '.$config->url.'">';
            } else {
                $html .=  '<img src="'.$config->url.'" alt="'.$config->alt.'" '.$this->buildAttributes($config->attributes).'>';
            }

            $html .= '</span>';
        }

        return $html;
    }

    public function url($url, $parameters = array())
    {
        $config = new ComPagesObjectConfig($parameters);
        $config->append($this->getConfig()->parameters);

        if($this->supported($url))
        {
            $url   = KHttpUrl::fromString($url);
            $query = array_merge(array_filter(KObjectConfig::unbox($config)), $url->query);

            //Add CRC32 checksum
            $query['crc'] = hash('crc32', filesize($this->_findFile($url)));

            ksort($query); //sort alphabetically
            $url->query = $query;
        }

        return $url;
    }

    public function url_lqi($url, $parameters = array(), $data_url = false)
    {
        $config = new ComPagesObjectConfig($parameters);
        $config->append($this->getConfig()->parameters_lqi);
        $config->append(array(
            'fm' => 'jpg'
        ));

        if($this->supported($url))
        {
            $result = (string) $this->url($url, $config);

            //Generate data url for low quality image
            if($data_url && PHP_SAPI != 'cli-server')
            {
                $context = stream_context_create([
                    "ssl" => [
                        "verify_peer"      => false,
                        "verify_peer_name" => false,
                    ],
                ]);

                $url = $this->getConfig()->base_url.'/'.trim($result, '/');
                if($data = @file_get_contents($url, false, $context))
                {
                    //Failsafe: Ensure file is smaller then 10kb
                    if(strlen($data) > 10000)
                    {
                        $log = $this->getConfig()->log_path.'/image.log';
                        error_log(sprintf('Could not generate LQI image: %s'."\n", $url), 3, $log);
                    }
                    else $result = 'data:image/jpg;base64,'.base64_encode($data);
                }
            }
        }
        else $result = $url;

        return $result;
    }

    public function srcset($url, $parameters = array())
    {
        $config = new ComPagesObjectConfig($parameters);
        $config->append(array(
            'max_width'   => $this->getConfig()->max_width,
            'min_width'   => $this->getConfig()->min_width,
            'max_dpr'     => $this->getConfig()->max_dpr,
            'descriptors' => true,
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

            $sizes = $this->_calculateSizes($url->getPath(), $config->max_width * $config->max_dpr, $config->min_width);

            //Build path for the high quality image
            $hqi_url = $this->url($url);

            foreach ($sizes as $size)
            {
                $hqi_url->query['w'] = $size;
                if($config->descriptors) {
                    $srcset[$size] = sprintf((string)$hqi_url . ' %1$sw', $size);
                } else {
                    $srcset[$size] = (string)$hqi_url;
                }
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

    public function exists($url)
    {
        $result = false;

        if($this->supported($url)) {
            $result = (bool) $this->_findFile($url);
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

    protected function _findFile($url)
    {
        if(!$url instanceof KHttpUrlInterface) {
            $url = KHttpUrl::fromString($url);
        }

        $path  = $url->getPath();
        $file = $this->getConfig()->base_path . '/' . str_replace('/images/', '', $path);

        if(!file_exists($file)) {
            $file = false;
        }

        return $file;
    }

    /*
     * Calculate the image breakpoints based on fixed filesize reduction
     *
     * Inspired by https://stitcher.io/blog/tackling_responsive_images-part_2
     */
    protected function _calculateSizes($url, $max_width, $min_width = 320)
    {
        $min_filesize = 1024 * 5; //5kb
        $modifier     = 0.7;       //70% (each image should be +/- 30% smaller in expected size)

        //Get dimensions
        $sizes = array();
        if($file = $this->_findFile($url))
        {
            list($width, $height) = @getimagesize($file);

            //Get filesize
            $filesize = @filesize($file);

            if ($width < $max_width) {
                $sizes[] = $width;
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
        }

        return $sizes;
    }

    /*
     * Calculate the image size
     */
    protected function _calculateSize($url, $max_width = null, $max_height = null)
    {
        $width  = false;
        $height = false;

        if($file = $this->_findFile($url))
        {
            list($width, $height) = @getimagesize($file);

            if($max_width && $max_width < $width)
            {
                $height = ceil(($max_width / $width) * $height);
                $width  = $max_width;
            }

            if($max_height && $max_height < $height)
            {
                $width = ceil(($max_height / $height) * $width);
                $height = $max_height;
            }
        }

        return [$width, $height];
    }

    /**
     *  Generation svg placeholder maintaining aspect ratio
     */
    protected function _generatePlaceholder($width, $height)
    {
        $svg = '<svg viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg"><rect fill-opacity="0" /></svg>';
        $uri =  'data:image/svg+xml;base64,'.base64_encode($svg);
        return $uri;
    }
}