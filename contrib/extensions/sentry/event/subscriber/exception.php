<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtSentryEventSubscriberException extends ComPagesEventSubscriberAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append($this->getConfig('ext:sentry.config'));
        $config->append(array(
            'priority' => KEvent::PRIORITY_HIGH,
            'bootstrap'  => true,
            'options'    => [
                'server_name'  => gethostname(),
                'logger'       => 'php.pages',
                'default_integrations' => false,
                'integrations'         => [
                    new \Sentry\Integration\RequestIntegration(),
                    new \Sentry\Integration\TransactionIntegration(),
                    new \Sentry\Integration\FrameContextifierIntegration(),
                    new \Sentry\Integration\EnvironmentIntegration(),
                ],
            ],
            'ignore_exceptions' => [
                'ComPagesControllerExceptionRequestBlocked',
                'ComPagesControllerExceptionRequestInvalid',
            ],
            'ignore_tags' => [],
            'disable_on' => [401, 403],
        ));

        if(count($config->ignore_tags) || count($config->ignore_exceptions))
        {
            $config->options->integrations[] = new \Sentry\Integration\IgnoreErrorsIntegration([
                'ignore_exceptions' => KObjectConfig::unbox($config->ignore_exceptions),
                'ignore_tags'       => KObjectConfig::unbox($config->ignore_tags),
            ]);
        }

        parent::_initialize($config);
    }

    public function onAfterPagesBootstrap(KEventInterface $event)
    {
        //Get Sentry Config Object
        $sentry = $this->getObject('ext:sentry.config', $this->getConfig()->toArray());

        if(function_exists('\Sentry\init') && $sentry->bootstrap && $sentry->options->dsn)
        {
            //Initialise Options
            $options = $sentry->options;

            if(is_callable($sentry->bootstrap)) {
                call_user_func($sentry->bootstrap, $options);
            };

            \Sentry\init(ExtSentryConfigOptions::unbox($options));

            //Configure Scope
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use($sentry): void
            {
                foreach($sentry->getContext() as $name => $context) {
                    $scope->setContext($name, (array) ExtSentryConfigOptions::unbox($context));
                }

                foreach($sentry->getTags() as $name => $value) {
                    $scope->setTag($name, $value);
                }

                foreach($sentry->getUser() as $key => $value) {
                    $scope->setUser([$key => $value]);
                }
            });
        }
    }

    public function onException(KEventInterface $event)
    {
        $exception = $event->exception;

        //If the error code does not correspond to a status message, use 500
        $code = $exception->getCode();
        if(!isset(KHttpResponse::$status_messages[$code])) {
            $code = '500';
        }

        if(!in_array($code, ExtSentryConfigOptions::unbox($this->getConfig()->disable_on))) {
            \Sentry\captureException($exception);
        }
    }

    public function isEnabled()
    {
        return function_exists('\Sentry\init') && parent::isEnabled();
    }
}