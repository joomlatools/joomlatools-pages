<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Http Transparent Async Reverse Cache Proxy
 *
 * This anonymous function implements an async http cache proxy following the https://tools.ietf.org/html/rfc7234
 * specification. It supports an array based php file cache with 'headers', 'content' and 'status' properties.
 *
 * The proxy will return the resource from cache immediatly if it exists and validate it async in the background.
 * This ensure that each request is equally fast.
 *
 * <code>
 * <?php
 *     return array (
 *          'headers' => array (),
 *          'content' => '',
 *          'status'  => '',
 * ?>
 * </code>
 *
 * The proxy will forward any requests that are:
 *    - Not GET or HEAD
 *    - Contain Cache-Control directives
 *
 * The proxy offers Cache Validation using ETag
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 *
 * @param string $cache_path The path for the cache responses
 * @param callable $callback The callback to execute the application
 */
return function($cache_path = KOOWA_ROOT.'/sites/default/cache/pages', callable $callback)
{
    ini_set('output_buffering', false);
    ini_set('zlib.output_compression', false);

    //Do not process cache for none GET or HEAD requests
    if(!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD']))
    {
        call_user_func($callback);
        return false;
    }

    //If request include cache control directives pass it on for validation
    if(isset($_SERVER['HTTP_CACHE_CONTROL']))
    {
        call_user_func($callback);
        return false;
    }

    //If the cache path doesn't exist
    if(!file_exists($cache_path))
    {
        call_user_func($callback);
        return false;
    }

    //Get the url
    $host    = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL);
    $request = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
    $parts   = parse_url('https://'.$host.$request);
    $url     = trim($host.$request, '/');

    //Get the format
    $format = pathinfo($parts['path'], PATHINFO_EXTENSION) ?: '';

    //Get the user
    $hash = crc32($parts['path'].$parts['query']);
    $file = $cache_path . '/response_' . $hash . '.php';

    //Clear the cache for the specific file to avoid any errors.
    clearstatcache (true,  $parts);

    if (is_file($file))
    {
        $data = require $file;

        $headers = $data['headers'];
        $content = $data['content'];
        $status  = $data['status'];

        $max_age = false;
        $age     = max(time() - strtotime($headers['Date']), 0);

        //Check the cache is stale
        if(isset($headers['Cache-Control']))
        {
            $cache_control = explode(',', $headers['Cache-Control']);

            foreach ($cache_control as $key => $value)
            {
                if(is_string($value))
                {
                    $parts = explode('=', $value);

                    if (count($parts) > 1)
                    {
                        unset( $cache_control[$key]);
                        $cache_control[trim($parts[0])] = trim($parts[1]);
                    }
                }
            }


            if (isset($cache_control['max-age'])) {
                $max_age = $cache_control['max-age'];
            }

            if (isset($cache_control['s-maxage'])) {
                $max_age = $cache_control['s-maxage'];
            }
        }

        //Divide the max_age in half and set it in the $_SERVER global
        if(!strstr($headers['Cache-Status'], 'EXPIRED') && $max_age) {
            $_SERVER['HTTP_CACHE_CONTROL'] = 'max-age='.(int) ($max_age / 2);
        }

        //Cache validation
        if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($headers['Etag']))
        {
            $etags = preg_split('/\s*,\s*/', $_SERVER['HTTP_IF_NONE_MATCH'], null, PREG_SPLIT_NO_EMPTY);

            //RFC-7232 explicitly states that ETags should be content-coding aware
            $etags = str_replace('-gzip', '', $etags);

            if(in_array($headers['Etag'], $etags) || in_array('*', $etags))
            {
                http_response_code ('304');
                header('Cache-Status: HIT');

                //Generating a 304 response MUST generate any of the following header fields that would have been sent
                //in a 200 (OK) response to the same request: Cache-Control, Content-Location, Date, ETag and Vary
                $required = ['Cache-Control', 'Content-Location', 'ETag', 'Vary'];

                foreach($required as $header)
                {
                    if(isset($headers[$header])) {
                        header($header.': '.$headers[$header]);
                    }
                }

                //Refresh the date
                header('Date: '.date('D, d M Y H:i:s', strtotime('now')).' GMT');

                //Revalidation the cache async
                fastcgi_finish_request();
                call_user_func($callback);

                return true;
            }
        }

        //Send the headers
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        //Set response code
        http_response_code ($status);
        //header('Cache-Status: HIT');

        //Send the content
        if($_SERVER['REQUEST_METHOD'] == 'GET') {
            echo $content;
        }

        //Revalidation the cache async
        fastcgi_finish_request();
        call_user_func($callback);

        return true;
    }
    else call_user_func($callback);
};