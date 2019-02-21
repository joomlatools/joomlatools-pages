<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewCollectionXml extends ComPagesViewXml
{
    protected function _fetchData(KViewContext $context)
    {
        $context->data->append(array(
            'collection' => $this->getModel()->limit(0)->fetch(),
            'total'      => $this->getModel()->count(),
        ));

        parent::_fetchData($context);
    }

    public function isCollection()
    {
        return (bool) !$this->getModel()->getState()->isUnique();
    }
}