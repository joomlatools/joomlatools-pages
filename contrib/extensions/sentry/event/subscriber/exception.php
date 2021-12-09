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
        $config->append(array(
            'dsn'         => getenv('SENTRY_DSN'),
            'environment' => getenv('SENTRY_ENVIONMENT'),
            'traces_sample_rate' => 1.0,
            'release'     => getenv('SENTRY_RELEASE') ?: $this->getObject('com:pages.version')->getVersion(),
            'tags'        => array()
        ));

        parent::_initialize($config);
    }

    public function onAfterKoowaBootstrap(KEventInterface $event)
    {
        \Sentry\init([
            'dsn'                => $this->getConfig()->dsn,
            'environment'        => $this->getConfig()->environment ?: null,
            'traces_sample_rate' => $this->getConfig()->traces_sample_rate,
            'release'            => $this->getConfig()->release,
        ]);

        //Configure Sentry
        \Sentry\configureScope(function (\Sentry\State\Scope $scope): void
        {
            foreach($this->getConfig()->tags as $key => $value) {
                $scope->setTag($key, $value);
            }
        });
    }

    public function onException(KEventInterface $event)
    {
        $exception = $event->exception;

        if($exception->getCode() >= 500) {
            \Sentry\captureException($exception);
        }
    }

    public function isEnabled()
    {
        return $this->getConfig()->dsn && function_exists('\Sentry\captureException') && parent::isEnabled();
    }
}