<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserEventSubscriberBootstrapper extends ComPagesEventSubscriberAbstract
{
    public function onAfterPagesBootstrap(KEventInterface $event)
    {
        //Cannot use config.php since object.config.factory is already instantiated
        $this->getObject('object.config.factory')
            ->registerFormat('xml' , 'ext:domparser.object.config.xml')
            ->registerFormat('html', 'ext:domparser.object.config.html');
    }
}