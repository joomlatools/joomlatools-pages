<?php
//See: https://www.smashingmagazine.com/2015/06/efficient-image-resizing-with-imagemagick/

//Check if we have been redirected by Apache or Litespeed
if(getenv('REDIRECT_IMAGE') === false && getenv('IMAGE') === false)
{
    http_response_code(404);
    exit();
}

/**
 * Config options
 */

$image_root    = rtrim($_SERVER['PAGES_IMAGES_ROOT'], '/');
$enhance       = false;
$quality       = 100;
$compress      = false;
$refresh_time  = '1month';  //time before images that are not accessed are garbage collected

$w    = null;  //width
$h    = null;  //height
$dpr  = 1;     //device pixel ratio
$vary     = isset($_SERVER['HTTP_ACCEPT']) ? ['accept'] : [];

$max_w   = 1920;
$min_w   = 320;
$max_dpr = 3;

/**
 * Route request
 */

$cache_root     = isset($_SERVER['PAGES_CACHE_ROOT']) ? $_SERVER['PAGES_CACHE_ROOT'] : false;
$cache_none     = isset($_SERVER['HTTP_CACHE_CONTROL']) && strstr($_SERVER['HTTP_CACHE_CONTROL'], 'no-cache') !== false;
$cache_versions = isset($_SERVER['HTTP_CACHE_ACCEPT']) && strstr($_SERVER['HTTP_CACHE_ACCEPT'], 'versions') !== false;

//Request
$query = array();
parse_str(filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_URL), $query);

//Time
$time = microtime(true);

if($query['image_path'])
{
    $image_path  = trim($query['image_path'], '/');
    $cache_path = isset($query['cache_path']) ? $query['cache_path'].'/'.$image_path : $image_path;

    $source      = $image_root.'/'.$image_path;
    $destination = $cache_root ?  $cache_root.'/'.$cache_path : false;
    $background   = null;

    if(!file_exists($source))
    {
        http_response_code(404);
        exit();
    }

    $format = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
    if(Image::isSupported($format) && !Image::isAnimated($source))
    {
        //Get the parameters
        unset($query['image_path']);
        unset($query['cache_path']);
        $parameters = $query;

        if(isset($parameters['auto']))
        {
            $directives = array_map('trim', explode(',', $parameters['auto']));

            if(!isset($parameters['fm']) && (in_array('format', $directives) || in_array('true', $directives)))
            {
                //Return WebP if supported (be forward compat when Safari offers Webp support)
                if(isset($_SERVER['HTTP_ACCEPT']) && strstr($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false)
                {
                    if(Image::isSupported('webp')) {
                        $format = 'webp';
                    }
                }
            }

            //Set compression
            if(!isset($parameters['q']) && (in_array('compress', $directives) || in_array('true', $directives)))
            {
                $compress = true;
                $quality  = 75;
            }

            //Set enchancement
            if(in_array('enhance', $directives) || in_array('true', $directives)) {
                $enhance = true;
            }
        }

        //Auto DPR
        if(isset($parameters['dpr']))
        {
            if($parameters['dpr'] == 'auto')
            {
                $dpr = isset($_SERVER['HTTP_DPR']) ? floatval($_SERVER['HTTP_DPR']) : $dpr;
                $dpr = floor($dpr * 2) / 2; //round with 0.5 precision

                $vary[] = 'dpr'; //Add dpr to Vary
            }
            else $dpr = floatval($parameters['dpr']);

            $parameters['dpr'] = max($dpr, $max_dpr);
        }

        //Auto Width
        if(isset($parameters['w']))
        {
            if($parameters['w'] == 'auto')
            {
                if(isset($_SERVER['HTTP_VIEWPORT_WIDTH'])) {
                    $max_w = min(intval($_SERVER['HTTP_VIEWPORT_WIDTH']), $max_w);
                }

                if(isset($_SERVER['HTTP_WIDTH'])) {
                    $w = intval($_SERVER['HTTP_WIDTH']);
                } else {
                    $w = $max_w;
                }

                $sizes = Image::calculateSizes($source, $max_w, $min_w);

                foreach(array_reverse($sizes) as $size)
                {
                    if($size > $w) {
                        $w = $size; break;
                    }
                }

                $vary[] = 'width'; //Add width to Vary
            }
            else $w = intval($parameters['w']);

            $parameters['w'] = $w;
        }

        //Set quality
        if(isset($parameters['q'])) {
            $quality = (int) $parameters['q'];
        }

        //Set the format
        if(isset($parameters['fm'])) {
            $format = $parameters['fm'];
        }

        //Set background color
        if(isset($parameters['bg']))
        {
            if($color = Image::normalizeColor($parameters['bg'])) {
                $background = $color;
            }
        }

        //Create the filename
        if($cache_root)
        {
            $cache_query = $parameters;

            if(!isset($parameters['fm']))
            {
                $search     = pathinfo($image_path, PATHINFO_EXTENSION);
                $cache_path = str_replace('.'.$search, '.'.$format, $cache_path);
            }

            $destination = $cache_root.'/'.$cache_path.'_'.http_build_query($cache_query, '', '&');
        }

        //Generate image
        $image = null;
        if(!$destination || !file_exists($destination) || $cache_none)
        {
            try
            {
                //Load the original
                $image = (new Image())->read($source, $format, $background);

                //Resize
                if(isset($parameters['w']) || isset($parameters['h'])) {
                    $image->resize($parameters['w'] ?? null, $parameters['h'] ?? null, $dpr);
                }

                //Pixellate
                if(isset($parameters['px'])) {
                    $image->pixellate((int) $parameters['px'], true);
                }

                //Blur
                if(isset($parameters['bl'])) {
                    $image->blur((int) $parameters['bl']);
                }

                //Interlace
                if($format == 'pjpeg') {
                    $image->interlace();
                }

                //Enhance the image
                if($enhance) {
                    $image->enhance();
                }

                //Compress the image
                if($compress) {
                    $image->compress();
                }

                //Create the directory
                $dir = dirname($destination);

                if(is_dir($dir) || (true === @mkdir($dir, 0777, true)))
                {
                    //Save the image
                    $image->write($quality, $destination);

                    //Override default permissions for generated file
                    @chmod($destination, 0666 & ~umask());
                }

            }
            catch(Exception $e)
            {
                $log = $cache_root.'/.error_log';
                error_log(sprintf('Could not generate image: %s, error: %s'."\n", $destination, $e->getMessage()), 3, $log);
            }

        }
    }
    else
    {
        if(!file_exists($destination) || $cache_none)
        {
            //Create the directory
            $dir = dirname($destination);

            if(is_dir($dir) || (true === @mkdir($dir, 0777, true)))
            {
                //Copy the image
                copy($source, $destination);

                //Override default permissions for generated file
                @chmod($destination, 0666 & ~umask());
            }
        }
    }

    //Get a list of all the different file versions
    $versions = [];
    if($cache_none || $cache_versions)
    {
        foreach (glob($cache_root.'/'.$cache_path.'*') as $file)
        {
            if($cache_none) {
                unlink($file);
            }

            if($file != $destination)
            {
                foreach(['jpg', 'jpeg', 'png', 'gif'] as $ext) {
                    $file = str_replace($ext.'_', $ext.'?', $file);
                }

                $versions[] = str_replace($cache_root, '', $file);
            }
        }
    }

    //Garbage collect (single folder only for performance reasons)
    $refresh_time = is_string($refresh_time) ? strtotime($refresh_time) - strtotime('now') : $refresh_time;
    foreach (glob(dirname($destination).'/*') as $file)
    {
        if (is_file($file))
        {
            if ((time() - fileatime($file) >= $refresh_time)) {
                unlink($file);
            }
        }
    }

    //If the image couldn't be generated use the source instead
    if($destination && file_exists($destination)) {
        $file = $destination;
    } else {
        $file = $source;
    }

    header('Content-Type: '. mime_content_type($file));
    header('Content-Length: '.filesize($file));
    header('Date: '.date('D, d M Y H:i:s', strtotime('now')).' GMT');
    header('Last-Modified: '.date('D, d M Y H:i:s', filemtime($file)).' GMT');
    header('Vary: '.implode(',', $vary));

    //Set X-Created-With
    if($image)
    {
        if($image->isImagick()) {
            $version = Imagick::getVersion()['versionString'];
        } else {
            $version = 'GD '.GD_Info()['GD Version'];
        }

        header('X-Created-With:'.$version);
    }

    //Set Server-Timing
    if (isset($_SERVER['REQUEST_TIME_FLOAT']))
    {
        $time  = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
        header('Server-Timing: tot;desc="Total";dur='.(int) $time);
    }

    if(!empty($versions)) {
        header('Cache-Versions: '.implode(',', $versions));
    }

    readfile($file);

    //Cleanup and flush output to client
    if (!function_exists('fastcgi_finish_request'))
    {
        for ($i = 0; $i < ob_get_level(); $i++) {
            ob_end_flush();
        }

        flush();
    }
    else fastcgi_finish_request();

    exit();
}


Class Image
{
    protected $_image;
    protected $_file;
    protected $_format;
    protected $_background;

    public function __destruct()
    {
        if(is_resource($this->_image)) {
            imagedestroy($this->_image);
        }
    }

    public function read($file, $format = null, $background = null)
    {
        if (!is_file($file)){
            throw new Exception('Image not found');
        }

        $file_format = pathinfo($file, PATHINFO_EXTENSION);

        $this->_file       = $file;
        $this->_format     = $format ?? $file_format;
        $this->_background = $background;

        //Use Imagick if supported
        if(class_exists('Imagick'))
        {
            $output = strtoupper($this->_format);
            $input  = strtoupper($file_format);

            if(Imagick::queryFormats($input) && Imagick::queryFormats($output))
            {
                $this->_image = new Imagick();

                //Fill with background color
                if($background)
                {
                    $color = sprintf('rgb(%d, %d, %d)', $background['r'], $background['g'], $background['b']);
                    $this->_image->setBackgroundColor(new ImagickPixel($color));
                }

                $this->_image->readImage($file);
            }
        }

        //Fallback to GD
        if(!$this->_image)
        {
            switch($file_format)
            {
                case 'jpg'  :
                case 'jpeg' : $this->_image = @imagecreatefromjpeg($file); break;
                case 'gif'  : $this->_image = @imagecreatefromgif($file); break;
                case 'webp' : $this->_image = @imagecreatefromwebp($file); break;
                case 'png'  : $this->_image = @imagecreatefrompng($file);
                    break;
            }

            // Convert pallete images to true color images
            imagepalettetotruecolor($this->_image);

            //Fill with background color
            if($background)
            {
                $resampled = imagecreatetruecolor($this->getWidth(), $this->getHeight());
                $color     = imagecolorallocate($resampled, $background['r'], $background['g'], $background['b']);

                imagefill($resampled, 0, 0, $color);
                imagecopy($resampled, $this->_image, 0, 0, 0, 0, $this->getWidth(), $this->getHeight());

                $this->_image = $resampled;
            }
        }

        if(!$this->_image) {
            throw new Exception('File is not a supported image type');
        }

        return $this;
    }

    public function write($quality = 100, $file = null)
    {
        $format  = $this->_format;
        $quality = (int) round($quality);

        //Default: GD
        if(!$this->_image instanceof Imagick)
        {
            switch($format)
            {
                case 'jpg'  :
                case 'pjpeg':
                case 'jpeg' : imagejpeg($this->_image, $file, $quality); break;
                case 'gif'  : imagegif($this->_image, $file); break;
                case 'png'  : imagepng($this->_image, $file,  (int)(9 - round(($quality/100) * 9))); break;
                case 'webp' : imagewebp($this->_image, $file,  $quality); break;
            }
        }
        //Imagick
        else
        {
            if($format == 'png')
            {
                $this->_image->setOption('png:compression-level', 9);
                $this->_image->setOption('png:compression-filter', 5);
                $this->_image->setOption('png:compression-strategy', 1);
                $this->_image->setOption('png:exclude-chunk', 'all');
            }
            else $this->_image->setImageCompressionQuality($quality);

            if($format == 'jpeg' || $format == 'pjpeg' || $format == 'jpg') {
                $this->_image->setOption('jpeg:fancy-upsampling', 'off');
            }

            //Set colorspace to SRGB
            $this->_image->setColorspace(Imagick::COLORSPACE_SRGB);

            //Set image depth to max 8 bits
            $this->_image->setImageDepth(8);

            //Turn off interlacing
            $this->_image->setInterlaceScheme(\Imagick::INTERLACE_NO);

            //Set the format
            $this->_image->setImageFormat($format);

            //Remove the alpha channel if a background is defined
            if($this->_background) {
                $this->_image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE );
            }

            //If the alpha channel is not defined, make it opaque
            if ($this->_image->getImageAlphaChannel() == Imagick::ALPHACHANNEL_UNDEFINED) {
                $this->_image->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
            }

            if($file) {
                $this->_image->writeImage($file);
            } else {
                $this->_image->getImageBlob();
            }
        }

        return $this;
    }

    public function blur($factor = 3)
    {
        $factor = (int) ceil(($factor / 100) * 10);

        //Default: GD
        if(!$this->_image instanceof Imagick)
        {
            $factor = round($factor);

            $source_width  = $this->getWidth();
            $source_height = $this->getHeight();

            $smallest_width  = ceil($source_width * pow(0.775,  $factor));
            $smallest_height = ceil($source_height * pow(0.775, $factor));

            //For the first run, the previous image is the source
            $prev_image  = $this->_image;
            $prev_width  = $source_width;
            $prev_height = $source_height;

            //Scale way down and gradually scale back up, blurring all the way
            for($i = 0; $i < $factor; $i += 1)
            {
                //Determine dimensions of next image
                $next_width  = $smallest_width * pow(1.5, $i);
                $next_height = $smallest_height * pow(1.5, $i);

                //Resize previous image to next size
                $next_image = imagecreatetruecolor($next_width, $next_height);
                imagecopyresized($next_image, $prev_image, 0, 0, 0, 0, $next_width, $next_height, $prev_width, $prev_height);

                //Apply blur filter
                imagefilter($next_image, IMG_FILTER_GAUSSIAN_BLUR);

                // now the new image becomes the previous image for the next step
                $prev_image = $next_image;
                $prev_width = $next_width;
                $prev_height = $next_height;
            }

            // scale back to source size and blur one more time
            imagecopyresized($this->_image, $next_image, 0, 0, 0, 0, $source_width, $source_height, $next_width, $next_height);
            imagefilter($this->_image, IMG_FILTER_GAUSSIAN_BLUR);
        }
        //Imagick
        else $this->_image->blurImage(0, $factor);

        return $this;
    }

    public function pixellate($size = 1, $advanced = true)
    {
        $size = round($size);

        //Default: GD
        if(!$this->_image instanceof Imagick) {
            imagefilter($this->_image, IMG_FILTER_PIXELATE, $size, $advanced);
        }
        //Imagick
        else
        {
            $width  = $this->getWidth();
            $height = $this->getHeight();

            $this->_image->scaleImage(max(1, ($width / $size)), max(1, ($height / $size)));
            $this->_image->scaleImage($width, $height);
        }

        return $this;
    }

    public function resize($width, $height = null, $density = 1)
    {
        //Calculate the width
        if($height) {
            $width = (int) (($this->getWidth() / $this->getHeight()) * $height);
        } else {
            $height = (int) (($this->getHeight() / $this->getWidth()) * $width);
        }

        //Calculate total size based on density
        $width  = $width  * $density;
        $height = $height * $density;

        //Default: GD
        if(!$this->_image instanceof Imagick)
        {
            //Resample the source
            if($resampled = imagecreatetruecolor($width, $height))
            {
                imagecopyresampled($resampled, $this->_image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());

                //Replace the source
                $this->_image  = $resampled;
            }
        }
        //Imagick
        else
        {
            $this->_image->setOption('filter:support', '2.0');
            if(method_exists('Imagick', 'adaptiveResizeImage')) {
                $this->_image->adaptiveResizeImage($width, $height);
            } else {
                $this->_image->resizeImage($width, $height, Imagick::FILTER_TRIANGLE);
            }
        }

        return $this;
    }

    public function interlace($interlace = true)
    {
        //Default: GD
        if(!$this->_image instanceof Imagick) {
            imageinterlace($this->_image, $interlace);
            //Imagick
        } else {
            $this->_image->setInterlaceScheme(Imagick::INTERLACE_PLANE);
        }

        return $this;
    }

    public function enhance($sharpen = 10)
    {
        if($this->_image instanceof Imagick)
        {
            $this->_image->autoLevelImage();

            if($this->_format != 'png') {
                $this->_image->enhanceImage();
            }

            if(method_exists('Imagick', 'adaptiveSharpenImage')) {
                $this->_image->adaptiveSharpenImage(0.25, 0.25);
            } else {
                $this->_image->unsharpMaskImage(0.25, 0.25, $sharpen, 0.065);
            }

        }
        else
        {
            // Normalize amount
            $amount = max(1, min(100, $sharpen)) / 100;

            $sharpen = [
                [-1, -1, -1],
                [-1,  8 / $amount, -1],
                [-1, -1, -1],
            ];

            $divisor = array_sum(array_map('array_sum', $sharpen));
            imageconvolution($this->_image, $sharpen, $divisor, 0);
        }

        return $this;
    }

    public function compress()
    {
        if($this->_image instanceof Imagick)
        {
            // Strip all profiles except color profiles.
            foreach ($this->_image->getImageProfiles('*', true) as $key => $value)
            {
                if ($key != 'icc' && $key != 'icm') {
                    $this->_image->removeImageProfile($key);
                }
            }

            $properties = [
                'comment',
                'software',
                'Thumb::URI',
                'Thumb::MTime',
                'Thumb::Size',
                'Thumb::Mimetype',
                'Thumb::Image::Width',
                'Thumb::Image::Height',
                'Thumb::Document::Pages'
            ];

            foreach($properties as $property)
            {
                if (method_exists($this->_image, 'deleteImageProperty')) {
                    $this->_image->deleteImageProperty($property);
                } else {
                    $this->_image->setImageProperty($property, '');
                }
            }

            $this->_image->setOption('dither', 'none');
            $this->_image->posterizeImage(136, false);
        }
    }

    public function getWidth()
    {
        //Default: GD
        if(!$this->_image instanceof Imagick) {
            $result = imagesx($this->_image);
            //Imagick
        } else {
            $result = $this->_image->getImageWidth();
        }

        return $result;
    }

    public function getHeight()
    {
        //Default: GD
        if(!$this->_image instanceof Imagick) {
            $result = imagesy($this->_image);
            //Imagick
        } else {
            $result = $this->_image->getImageHeight();
        }

        return $result;
    }

    public function getPath()
    {
        return $this->_file;
    }

    public function getFormat()
    {
        return $this->_format;
    }

    public function isImagick()
    {
        $result = false;

        if(class_exists('Imagick') && $this->_image) {
            $result = $this->_image instanceof Imagick;
        }

        return $result;
    }

    public static function isSupported($format)
    {
        if(class_exists('Imagick')) {
            $supported = (bool) Imagick::queryFormats(strtoupper($format));
        }

        if(!$supported && defined('GD_VERSION')) {
            $supported = in_array(strtolower($format), ['jpeg', 'jpg', 'png', 'gif', 'webp']);
        }

        return $supported;
    }

    /**
     * Detects animated GIF from given file pointer resource or filename.
     *
     * @param string $file File pointer resource or filename
     * @return bool
     */
    public static function isAnimated($file)
    {
        $result = false;
        $format = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if(file_exists($file) && $format == 'gif')
        {
            $fp = null;
            $fp = fopen($file, "rb");

            if (fread($fp, 3) !== "GIF")
            {
                fclose($fp);
                return false;
            }

            $frames = 0;
            while (!feof($fp) && $frames < 2)
            {
                if (fread($fp, 1) === "\x00")
                {
                    /* Some of the animated GIFs do not contain graphic control extension (starts with 21 f9) */
                    if (fread($fp, 1) === "\x21" || fread($fp, 2) === "\x21\xf9") {
                        $frames++;
                    }
                }
            }

            fclose($fp);

            $result = $frames > 1;
        }

        return $result;
    }

    public static function hexToRgb($hex)
    {
        // Ignore '#' prefixes.
        $hex = ltrim($hex, '#');

        // Convert shorthands like '#abc' to '#aabbcc'.
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $c = hexdec($hex);
        return [
            'r' => $c >> 16 & 0xff,
            'g' => $c >> 8 & 0xff,
            'b' => $c & 0xff,
        ];
    }

    /**
     * Normalizes a hex or array color value to a well-formatted RGBA array.
     *
     * @param string|array $color A CSS color name or a hex string
     * @return array [r, g, b].
     */
    public static function normalizeColor($color)
    {
        $result = array();

        // 140 CSS color names and hex values
        $colors = [
            'aliceblue' => '#f0f8ff', 'antiquewhite' => '#faebd7', 'aqua' => '#00ffff',
            'aquamarine' => '#7fffd4', 'azure' => '#f0ffff', 'beige' => '#f5f5dc', 'bisque' => '#ffe4c4',
            'black' => '#000000', 'blanchedalmond' => '#ffebcd', 'blue' => '#0000ff',
            'blueviolet' => '#8a2be2', 'brown' => '#a52a2a', 'burlywood' => '#deb887',
            'cadetblue' => '#5f9ea0', 'chartreuse' => '#7fff00', 'chocolate' => '#d2691e',
            'coral' => '#ff7f50', 'cornflowerblue' => '#6495ed', 'cornsilk' => '#fff8dc',
            'crimson' => '#dc143c', 'cyan' => '#00ffff', 'darkblue' => '#00008b', 'darkcyan' => '#008b8b',
            'darkgoldenrod' => '#b8860b', 'darkgray' => '#a9a9a9', 'darkgrey' => '#a9a9a9',
            'darkgreen' => '#006400', 'darkkhaki' => '#bdb76b', 'darkmagenta' => '#8b008b',
            'darkolivegreen' => '#556b2f', 'darkorange' => '#ff8c00', 'darkorchid' => '#9932cc',
            'darkred' => '#8b0000', 'darksalmon' => '#e9967a', 'darkseagreen' => '#8fbc8f',
            'darkslateblue' => '#483d8b', 'darkslategray' => '#2f4f4f', 'darkslategrey' => '#2f4f4f',
            'darkturquoise' => '#00ced1', 'darkviolet' => '#9400d3', 'deeppink' => '#ff1493',
            'deepskyblue' => '#00bfff', 'dimgray' => '#696969', 'dimgrey' => '#696969',
            'dodgerblue' => '#1e90ff', 'firebrick' => '#b22222', 'floralwhite' => '#fffaf0',
            'forestgreen' => '#228b22', 'fuchsia' => '#ff00ff', 'gainsboro' => '#dcdcdc',
            'ghostwhite' => '#f8f8ff', 'gold' => '#ffd700', 'goldenrod' => '#daa520', 'gray' => '#808080',
            'grey' => '#808080', 'green' => '#008000', 'greenyellow' => '#adff2f',
            'honeydew' => '#f0fff0', 'hotpink' => '#ff69b4', 'indianred ' => '#cd5c5c',
            'indigo ' => '#4b0082', 'ivory' => '#fffff0', 'khaki' => '#f0e68c', 'lavender' => '#e6e6fa',
            'lavenderblush' => '#fff0f5', 'lawngreen' => '#7cfc00', 'lemonchiffon' => '#fffacd',
            'lightblue' => '#add8e6', 'lightcoral' => '#f08080', 'lightcyan' => '#e0ffff',
            'lightgoldenrodyellow' => '#fafad2', 'lightgray' => '#d3d3d3', 'lightgrey' => '#d3d3d3',
            'lightgreen' => '#90ee90', 'lightpink' => '#ffb6c1', 'lightsalmon' => '#ffa07a',
            'lightseagreen' => '#20b2aa', 'lightskyblue' => '#87cefa', 'lightslategray' => '#778899',
            'lightslategrey' => '#778899', 'lightsteelblue' => '#b0c4de', 'lightyellow' => '#ffffe0',
            'lime' => '#00ff00', 'limegreen' => '#32cd32', 'linen' => '#faf0e6', 'magenta' => '#ff00ff',
            'maroon' => '#800000', 'mediumaquamarine' => '#66cdaa', 'mediumblue' => '#0000cd',
            'mediumorchid' => '#ba55d3', 'mediumpurple' => '#9370db', 'mediumseagreen' => '#3cb371',
            'mediumslateblue' => '#7b68ee', 'mediumspringgreen' => '#00fa9a',
            'mediumturquoise' => '#48d1cc', 'mediumvioletred' => '#c71585', 'midnightblue' => '#191970',
            'mintcream' => '#f5fffa', 'mistyrose' => '#ffe4e1', 'moccasin' => '#ffe4b5',
            'navajowhite' => '#ffdead', 'navy' => '#000080', 'oldlace' => '#fdf5e6', 'olive' => '#808000',
            'olivedrab' => '#6b8e23', 'orange' => '#ffa500', 'orangered' => '#ff4500',
            'orchid' => '#da70d6', 'palegoldenrod' => '#eee8aa', 'palegreen' => '#98fb98',
            'paleturquoise' => '#afeeee', 'palevioletred' => '#db7093', 'papayawhip' => '#ffefd5',
            'peachpuff' => '#ffdab9', 'peru' => '#cd853f', 'pink' => '#ffc0cb', 'plum' => '#dda0dd',
            'powderblue' => '#b0e0e6', 'purple' => '#800080', 'rebeccapurple' => '#663399',
            'red' => '#ff0000', 'rosybrown' => '#bc8f8f', 'royalblue' => '#4169e1',
            'saddlebrown' => '#8b4513', 'salmon' => '#fa8072', 'sandybrown' => '#f4a460',
            'seagreen' => '#2e8b57', 'seashell' => '#fff5ee', 'sienna' => '#a0522d',
            'silver' => '#c0c0c0', 'skyblue' => '#87ceeb', 'slateblue' => '#6a5acd',
            'slategray' => '#708090', 'slategrey' => '#708090', 'snow' => '#fffafa',
            'springgreen' => '#00ff7f', 'steelblue' => '#4682b4', 'tan' => '#d2b48c', 'teal' => '#008080',
            'thistle' => '#d8bfd8', 'tomato' => '#ff6347', 'turquoise' => '#40e0d0',
            'violet' => '#ee82ee', 'wheat' => '#f5deb3', 'white' => '#ffffff', 'whitesmoke' => '#f5f5f5',
            'yellow' => '#ffff00', 'yellowgreen' => '#9acd32'
        ];

        // Translate CSS color names to hex values
        if(is_string($color) && array_key_exists(strtolower($color), $colors)) {
            $color = $colors[strtolower($color)];
        }

        // Convert hex values to RGBA
        $color = ltrim($color, '#');
        if(ctype_xdigit($color)) {
            $result = self::hexToRgb($color);
        }

        return $result;
    }

    /*
     * Calculate the image breakpoints based on fixed filesize reduction
     *
     * Inspired by https://stitcher.io/blog/tackling_responsive_images-part_2
     */
    public static function calculateSizes($file, $max_width, $min_width = 320)
    {
        $min_filesize = 1024 * 10; //10kb
        $modifier     = 0.7;       //70% (each image should be +/- 30% smaller in expected size)

        //Get dimensions
        list($width, $height) = @getimagesize($file);

        //Get filesize
        $filesize = @filesize($file);

        $sizes = array();
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

        return $sizes;
    }
}
