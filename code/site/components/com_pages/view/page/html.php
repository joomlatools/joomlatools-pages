<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesViewPageHtml extends ComKoowaViewHtml
{
    /**
     * The title of the view
     *
     * @var string
     */
    protected $_title;

    /**
     * The description of the view
     *
     * @var string
     */
    protected $_description;

    /**
     * Constructor
     *
     * Prevent creating instances of this class by making the constructor private
     *
     * @param KObjectConfig $config   An optional ObjectConfig object with configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Set the base path
        $this->setDescription($config->description);

        //Set the base path
        $this->setTitle($config->title);
    }

    /**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param  KObjectConfig $config  An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'auto_fetch'       => false,
            'template_filters' => ['markdown'],
            'title'       => ucfirst($this->getName()),
            'description' => '',
        ]);

        parent::_initialize($config);
    }

    /**
     * Get the page
     *
     * return ComPagesViewPageHtml
     */
    public function setPage(KTemplateInterface $page)
    {
        $this->page = $page;

        //Set the layout
        if($page->getParameters()->has('layout')) {
            $this->setLayout($page->getParameters()->layout);
        };

        //Set the title
        if($page->getParameters()->has('title')) {
            $this->setTitle($page->getParameters()->title);
        };

        //Set the description
        if($page->getParameters()->has('description')) {
            $this->setDescription($page->getParameters()->description);
        };

        return $this;
    }

    /**
     * Get the page
     *
     * @return  KTemplateInterface  The page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the title
     *
     * @return ComPagesViewPageHtml
     */
    public function setTitle($title)
    {
        $this->_title  = $title;
        return $this;
    }

    /**
     * Get the description
     *
     * @return 	string  The description of the view
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * Set the description
     *
     * @return ComPagesViewPageHtml
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }
}