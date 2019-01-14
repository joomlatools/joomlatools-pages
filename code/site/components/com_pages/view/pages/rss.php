<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesRss extends ComPagesViewXml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'data'     => array(
                'update_period'    => 'hourly',
                'update_frequency' => 1
            )
        ));

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        $context->data->append(array(
            'pages'     => $this->getModel()->fetch(),
            'total'     => $this->getModel()->count(),
            'sitename'  => JFactory::getApplication()->getCfg('sitename'),
            'language'  => JFactory::getLanguage()->getTag(),
            'description'  => $this->getPage()->summary ?: '',
            'image'        => $this->getPage()->image ?: ''
        ));

        parent::_fetchData($context);
    }
}