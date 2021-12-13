<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtSentryTemplateHelperBehavior extends ComPagesTemplateHelperBehavior
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append($this->getConfig('ext:sentry.config'));
        $config->append(array(
            'version' => null,
            'options' => [
                'debug'   => $this->getObject('pages.config')->debug,
                'tunnel'  => null,
            ]
        ));

        parent::_initialize($config);
    }

    /**
     * Sentry.io javascript integration
     *
     * Supports:
     *
     * - Error Tracking
     * - Performance Tracing
     *
     * Uses https://unpkg.com to load the bundle.tracing.js script
     *
     * For more info see: https://docs.sentry.io/platforms/javascript/
     */
    public function tracing($config = array())
    {
        $config = new ComPagesObjectConfig($config);
        $config->append($this->getConfig());

        //Get Sentry Config Object
        $sentry = $this->getObject('ext:sentry.config', $config->toArray());

        $html = '';
        if (!static::isLoaded('tracing') && $sentry->options->dsn)
        {
            $version = $sentry->version ? '@'.$sentry->version : '';

            $options = $sentry->options;
            $options->initialScope = [];
            if($tags = $sentry->getTags()) {
                $options->initialScope->tags = $tags;
            }

            if($user = $sentry->getUser()) {
                $options->initialScope->user = $user;
            }

            if($context = $sentry->getContext()) {
                $options->initialScope->context = $context;
            }

            $html .= '<ktml:script src="https://unpkg.com/@sentry/tracing'. $version .'/build/bundle.tracing.'.(!$sentry->debug ? 'min.js' : 'js').'" crossorigin="anonymous" />';
            $html .= <<<SENTRY
<script>
Sentry.init({... $options, ... { integrations: [new Sentry.Integrations.BrowserTracing()]}} )
</script>
SENTRY;

            static::setLoaded('tracing');
        }

        return $html;
    }
}