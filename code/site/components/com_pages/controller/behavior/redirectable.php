<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerBehaviorRedirectable extends KControllerBehaviorAbstract
{
    protected function _beforeRender(KControllerContextInterface $context)
    {
        if($url = $this->getModel()->fetch()->redirect)
        {
            if(!parse_url($url, PHP_URL_SCHEME))
            {
                $base = $context->request->getSiteUrl();

                if(strpos($context->request->getUrl(), 'index.php') !== false) {
                    $base .= '/index.php';
                }
                
                $url = $base .'/'.ltrim($url, '/');
            }

            $context->response->setRedirect($url);
            return false;
        }
    }

    public function isSupported()
    {
        return $this->getMixer()->isDispatched();
    }
}