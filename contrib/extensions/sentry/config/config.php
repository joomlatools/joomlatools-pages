<?php
/**
 * Joomlatools Pages
 *
 * @copyright  Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net).
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtSentryConfig extends ComPagesConfigAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'user'    => null,
            'context' => null,
            'tags'    => null,
            'options' => [
                'dsn'         => getenv('SENTRY_DSN') ?: null,
                'environment' => getenv('SENTRY_ENVIRONMENT') ?: null,
                'release'     => getenv('SENTRY_RELEASE') ?: null,
            ],
            'disable_on' => [401, 403, 404],
        ));

        //A number between 0 (false|off) and 1 (true|on), controlling the percentage chance a
        //given transaction will be sent to Sentry. (0 represents 0% while 1 represents 100%.)
        $tracing = $config->options->traces_sample_rate ?? getenv('SENTRY_TRACING');
        $tracing = filter_var($tracing, FILTER_VALIDATE_FLOAT) ?:
            filter_var($tracing, FILTER_VALIDATE_BOOL);

        $config->options->traces_sample_rate = floatval($tracing);

        parent::_initialize($config);
    }

    public function getOptions()
    {
        return new ExtSentryConfigOptions($this->getConfig()->options->toArray());
    }

    public function getUser()
    {
        if(is_callable($this->getConfig()->user))
        {
            $user = new ExtSentryConfigOptions();
            call_user_func($this->getConfig()->user, $user);
        }
        else $user = new ExtSentryConfigOptions($this->getConfig()->user ?? []);

        return $user;
    }

    public function getContext()
    {
        if(is_callable($this->getConfig()->context))
        {
            $context = new ExtSentryConfigOptions();
            call_user_func($this->getConfig()->context, $context);
        }
        else $context = new ExtSentryConfigOptions($this->getConfig()->context ?? []);

        return $context;
    }

    public function getTags()
    {
        if(is_callable($this->getConfig()->tags))
        {
            $tags = new ExtSentryConfigOptions();
            call_user_func($this->getConfig()->tags, $tags);
        }
        else $tags = new ExtSentryConfigOptions($this->getConfig()->tags ?? []);

        return $tags;
    }

    public function isTracing()
    {
        return (bool) $this->getConfig()->options->traces_sample_rate;
    }
}