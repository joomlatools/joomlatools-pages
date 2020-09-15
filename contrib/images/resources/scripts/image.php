<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */


//Check if we have been redirected by Apache
if(getenv('REDIRECT_IMAGE') === false)
{
    http_response_code(404);
    exit();
}

/**
 * Config options
 */

$basepath      = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$enhance       = false;
$quality       = 100;
$compress      = false;
$refresh_time  = '1week';  //time before images that are not accessed are garbage collected

/**
 * Route request
 */

$cache_dir    = getenv('KPATH_PAGES_CACHE') ? $_SERVER['DOCUMENT_ROOT'].getenv('KPATH_PAGES_CACHE') : false;
$cache_bypass = isset($_SERVER['HTTP_CACHE_CONTROL']) && strstr($_SERVER['HTTP_CACHE_CONTROL'], 'no-cache') !== false;

//Request
$host    = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL);
$request = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
$parts   = parse_url('https://'.$host.$request);

//Time
$time = microtime(true);

if($parts['query'] && $parts['path'])
{
    $filepath  = str_replace('.php', '', trim($parts['path'], '/'));

    $source      = $basepath.'/'.$filepath;
    $destination = false;

    $format   = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $type     = null;

    //Get the parameters
    $parameters = array();
    parse_str($parts['query'], $parameters);

    if(isset($parameters['auto']))
    {
        $directives = array_map('trim', explode(',', $parameters['auto']));

        if(!isset($parameters['fm']) && (in_array('format', $directives) || in_array('true', $directives)))
        {
            $format = 'pjpg';

            //Return JPEG200 if supported (Safari 6+ only)
            if(isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false)
            {
                preg_match('/Version\/(?P<version>[0-9]{2})/', $_SERVER['HTTP_USER_AGENT'], $params);

                //Because Safari 5 only represents 0.22% of browser usage on the world, ignore Windows/Mac
                //detection and start from version 6. Safari Accept Header does not specify JP2 support,
                //so as a fallback we are going to check if the browser is Safari, and check it’s version.
                if ((preg_match('/Version\/(?P<version>[0-9])/', $_SERVER['HTTP_USER_AGENT'], $params)) && (round($params['version']) >= 6))
                {
                    if(Image::isSupported('jp2')) {
                        $format = 'jp2';
                    }
                }
            }

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

    //Set quality
    if(isset($parameters['q'])) {
        $quality = (int) $parameters['q'];
    }

    //Set the format
    if(isset($parameters['fm']) && $format != $parameters['fm']) {
        $format = $parameters['fm'];
    } else {
        unset($parameters['fm']);
    }

    //Create the filename
    if($cache_dir)
    {
        $path  = $filepath;
        $query = $parameters;

        if(!$parameters['fm'])
        {
            $search  = pathinfo($filepath, PATHINFO_EXTENSION);
            $path    = str_replace($search, $format, $filepath);
        }

        $destination = $cache_dir.'/'.$path.'_'.http_build_query($query, '', '&');
    }

    //Generate image
    $image = null;
    if(!$destination || !file_exists($destination) || $cache_bypass)
    {
        try
        {
            //Load the original
            $image = (new Image())->read($source, $format);

            //Resize
            if(isset($parameters['w']) || isset($parameters['h']))
            {
                $density = $parameters['dpr'] ?? 1;
                $image->resize($parameters['w'] ?? null, $parameters['h'] ?? null, (int) $density);
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
            if($format == 'pjpg' || $format == 'jp2') {
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
            $log = $cache_dir.'/.error_log';
            error_log(sprintf('Could not generate image: %s, error: %s'."\n", $destination, $e->getMessage()), 3, $log);
        }
    }

    //Garbage collect (single folder only for performance reasons)
    $refresh_time = is_string($refresh_time) ? strtotime($refresh_time) - strtotime('now') : $refresh_time;
    foreach (glob(dirname($destination).'/*') as $file)
    {
        if (is_file($file))
        {
            if (time() - fileatime($file) >= $refresh_time) {
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
    header('Vary: Accept');

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

    readfile($file);
}


Class Image
{
    protected $_image;
    protected $_path;
    protected $_format;

    public function __destruct()
    {
        if(is_resource($this->_image)) {
            imagedestroy($this->_image);
        }
    }

    public function read($file, $format = null)
    {
        if (!is_file($file)){
            throw new Exception('Image not found');
        }

        $file_format = pathinfo($file, PATHINFO_EXTENSION);

        $this->_file = $file;
        $this->_format = $format ?? $file_format;

        //Use Imagick if supported
        if(class_exists('Imagick'))
        {
            $output = strtoupper($this->_format);
            $input  = strtoupper($file_format);

            if(Imagick::queryFormats($input) && Imagick::queryFormats($output)) {
                $this->_image = new Imagick($file);
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
                case 'png'  : $this->_image = @imagecreatefrompng($file); break;
                case 'webp' : $this->_image = @imagecreatefromwebp($file); break;
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
                case 'jp2' :
                case 'jpg' :
                case 'jpeg': imagejpeg($this->_image, $file, $quality); break;
                case 'gif' : imagegif($this->_image, $file); break;
                case 'png' : imagepng($this->_image, $file,  (int)(9 - round(($quality/100) * 9))); break;
                case 'webp': imagewebp($this->_image, $file,  $quality); break;
            }
        }
        //Imagick
        else
        {
            if($format == 'png') {
                $this->_image->setOption('png:compression-level', (int)(9 - round(($quality/100) * 9)));
            } else {
                $this->_image->setImageCompressionQuality($quality);
            }

            $this->_image->setImageFormat($format);

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
            $width = (int) (($this->getWidth() / $this->getHeight()) * $height * $density);
        } else {
            $height = (int) (($this->getHeight() / $this->getWidth()) * $width * $density);
        }

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
            if(method_exists('Imagick', 'adaptiveResizeImage')) {
                $this->_image->adaptiveResizeImage($width, $height);
            } else {
                $this->_image->resizeImage($width, $height, Imagick::FILTER_LANCZOS);
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

    public function enhance()
    {
        if($this->_image instanceof Imagick)
        {
            $this->_image->enhanceImage();
            $this->_image->sharpenimage(0, 1.25);
        }

        return $this;
    }

    public function compress()
    {
        if($this->_image instanceof Imagick) {
            $this->_image->stripImage();
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
        return $this->_path;
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
}
