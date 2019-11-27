<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Http Cache Proxy
 *
 * This anonymous function implements a http cache proxy following the https://tools.ietf.org/html/rfc7234
 * specification. It support an array based php file cache with 'headers' and 'content' properties.
 *
 * <code>
 * <?php
 *     return array (
 *          'headers' => array (),
 *          'content' => '',
 * ?>
 * </code>
 *
 * The proxy will forward any requests that are:
 *    - Not GET or HEAD
 *    - Contain and Authorization header
 *    - Contain Cache-Control directives
 *
 * The proxy offers Cache Validation using ETag and Last-Mofified and Cache Expiration using the `max_age`function
 * parameter. Default is FALSE.
 *
 * If the resource was served from cache the proxy will set the Age header with the calculated the response was
 * generated or validated by the origin server. After succesfull validation the proxy will return a 304 Not Modified
 * together with the current date in a Date header to allow clients to freshen their own stored response.
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 */
return function($cache_path = JPATH_ROOT.'/joomlatools-pages/cache/responses', $max_age = false)
{
    //Do not process cache for authorization requests
    if(@$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] || @$_SERVER['HTTP_AUTHORIZATION'] || isset($_SERVER['PHP_AUTH_USER'])) {
        return false;
    }

    //Do not process cache for none GET or HEAD requests
    if(!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
        return false;
    }

    //If request include cache control directives pass it on for validation
    if(isset($_SERVER['HTTP_CACHE_CONTROL'])) {
        return false;
    }

    if(file_exists($cache_path))
    {
        //Get the url
        $host    = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL);
        $request = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $url     = trim($host.$request, '/');

        //Get the format
        $format = pathinfo(parse_url('http://'.$url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'html';

        //Get the user
        $user = 0; //only anonymous requests

        $key = 'url:' .$url. '#format:' . $format . '#user:'.$user;

        $hash = crc32($key . PHP_VERSION);
        $file = $cache_path . '/response_' . $hash . '.php';

        if (file_exists($file))
        {
            $data = require $file;

            $headers = $data['headers'];
            $content = $data['content'];

            //Cache expiration
            if($max_age !== false)
            {
                $age = max(time() - strtotime($headers['Date']), 0);

                if($age > $max_age) {
                    return false;
                }
            }

            //Cache validation
            if(isset($_SERVER['HTTP_IF_NONE_MATCH']) || isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            {
                if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($headers['Etag']))
                {
                    $etags = preg_split('/\s*,\s*/', $_SERVER['HTTP_IF_NONE_MATCH'], null, PREG_SPLIT_NO_EMPTY);

                    //RFC-7232 explicitly states that ETags should be content-coding aware
                    $etags = str_replace('-gzip', '', $etags);

                    if(in_array($headers['Etag'], $etags) || in_array('*', $etags))
                    {
                        header('HTTP/1.1 304 Not Modified');
                        header('Cache-Status: PROXY');
                        header('Date: '.date_create('now', new DateTimeZone('UTC')));
                        exit();
                    }
                }

                if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($headers['Last-Modified']))
                {
                    if (!(strtotime($headers['Last-Modified']) > strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])))
                    {
                        header('HTTP/1.1 304 Not Modified');
                        header('Cache-Status: PROXY');
                        header('Date: '.date_create('now', new DateTimeZone('UTC')));
                        exit();
                    }
                }
            }
            else
            {
                //Send the headers
                foreach ($headers as $name => $value) {
                    header($name . ': ' . $value);
                }

                //Set response code
                header('HTTP/1.1 200 OK');

                //Set Age
                header('Age: '.max(time() - strtotime($headers['Date']), 0));

                //Set Cache-Status
                header('Cache-Status: PROXY');

                //Send the content
                if($_SERVER['REQUEST_METHOD'] == 'GET') {
                    echo $content;
                }
            }

            return true;
        }
    }

    return false;
};