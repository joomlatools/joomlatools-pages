<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorPageable extends KModelBehaviorAbstract
{
    private $__page;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__page = $config->page;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'page' => null,
        ));

        parent::_initialize($config);
    }

    public function getPage()
    {
        if($this->__page && !$this->__page instanceof ComPagesModelEntityPage)
        {
            $this->__page = $this->getObject('com://site/pages.model.entity.page',
                array('data'  =>  $this->__page->toArray())
            );
        }

        return  $this->__page;
    }

    public function getType()
    {
        $identifier = $this->getMixer()->getIdentifier();
        $type = $identifier->getPackage() .'-'. KStringInflector::pluralize($identifier->getName());

        return $type;
    }
}