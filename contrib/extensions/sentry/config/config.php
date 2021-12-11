<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComSentryConfig extends ComPagesConfigAbstract implements KObjectSingleton
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'dsn'         => getenv('SENTRY_DSN'),
            'environment' => null,
            'release'     => null,
            'tags'        => array(),
            'init'        => true,
            'scope'       => null,
            'tunnel'      => null,
            'traces_sample_rate' => 1.0,
        ));
    }

    public function getTags(array $tags = [])
    {
        if(is_callable($this->tags)) {
            $defaults = call_user_func($this->getConfig()->tags);
        } else {
            $defaults = KObjectConfig::unbox($this->getConfig()->tags);
        }

        return array_replace_recursive($defaults, $tags);
    }
}