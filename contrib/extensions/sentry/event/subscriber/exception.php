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
    public function onException(KEvent $event)
    {
        $exception = $event->exception;

        if($exception->getCode() >= 500) {
            \Sentry\captureException($exception);
        }
    }

    public function isEnabled()
    {
        return getenv('SENTRY_DSN') && function_exists('\Sentry\captureException') && parent::isEnabled();
    }
}