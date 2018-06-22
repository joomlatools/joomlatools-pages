<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewPagesXml extends ComPagesViewXml
{
    public function getLayout()
    {
        $layout = 'page://pages/'. $this->getPage()->path;
        $format = $this->getFormat();

        if(!$this->getObject('com:pages.page.locator')->locate($layout.'.'.$format)) {
            $layout = 'default';
        }

        return $layout;
    }

    protected function _fetchData(KViewContext $context)
    {
        $context->data->append(array(
            'pages'  => $this->getModel()->limit(0)->fetch(),
            'total'  => $this->getModel()->count(),
        ));

        parent::_fetchData($context);
    }
}