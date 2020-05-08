<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewCsv extends KViewCsv
{
    public function isCollection()
    {
        return (bool) !$this->getModel()->getState()->isUnique();
    }

    public function getRoute($page = null, $query = array(), $escape = false)
    {
        return $this->getBehavior('routable')->getRoute($page, $query, $escape);
    }

    public function getUrl($url = null)
    {
        if(!empty($url))
        {
            if($url instanceof KHttpUrlInterface)
            {
                $result = clone $url;
                $result->setUrl(parent::getUrl()->toString(KHttpUrl::AUTHORITY));
            }
            else
            {
                $result = clone parent::getUrl();;
                $result->setUrl($url);
            }
        }
        else $result = parent::getUrl();

        return $result;
    }

    protected function _fetchData(KViewContext $context)
    {
        parent::_fetchData($context);

        if($this->isCollection()) {
            $context->parameters->total = $this->getModel()->count();
        }
    }
}