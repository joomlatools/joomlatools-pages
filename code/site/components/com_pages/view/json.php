<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewJson extends KViewAbstract
{
    /**
     * JSON API version
     *
     * @var string
     */
    protected $_version;

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_version = $config->version;
    }

    /**
     * Initializes the config for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors'   => ['routable'],
            'version'     => '1.0',
        ]);

        parent::_initialize($config);
    }

    /**
     * Check if we are rendering an entity collection
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool) !$this->getModel()->getState()->isUnique();
    }

    /**
     * Render and return the views output
     *
     * If the view 'content'  is empty the output will be generated based on the model data, if it set it will
     * be returned instead.
     *
     * @param KViewContext  $context A view context object
     * @return string A RFC4627-compliant JSON string, which may also be embedded into HTML.
     */
    protected function _actionRender(KViewContext $context)
    {
        //Get the content
        $content = $context->content;

        if (is_array($content) || $content instanceof \Traversable) {
            $content = new ComPagesObjectConfigJson($content);
        }

        $this->setContent($content);

        return $content;
    }
    /**
     * Returns the JSON data
     *
     * It converts relative URLs in the content to relative before returning the result
     *
     * @see http://jsonapi.org/format/#document-structure
     *
     * @param KModelEntityInterface  $entity
     * @return array
     */
    protected function _fetchData(KViewContext $context)
    {
        $route = $this->getRoute();

        $document = new \ArrayObject(array(
            'jsonapi' => array('version' => $this->_version),
            'links'   => array('self' => (string) $this->getRoute($route)),
            'data'    => array()
        ));

        if ($this->isCollection())
        {
            $context->parameters->total = $this->getModel()->count();

            $page = $this->getModel()->getPage();

            foreach ($this->getModel()->fetch() as $entity) {
                $document['data'][] = $this->_createResource($entity);
            }

            if($this->getModel()->isPaginatable())
            {
                $paginator = $this->getModel()->getPaginator();

                $total  = (int) $paginator->total;
                $limit  = (int) $paginator->limit;
                $offset = (int) $paginator->offset;

                $document['meta'] = [
                    'offset'   => $offset,
                    'limit'    => $limit,
                    'total'	   => $total,
                ];

                if($title = $page->title) {
                    $document['meta']['title'] = $title;
                }

                if($description = $page->metadata->get('description')) {
                    $document['meta']['description'] = $description;
                }

                if($image = $page->image) {
                    $document['meta']['image'] = (string) $this->getUrl((string)$image);
                }

                if ($limit && $total > count($this->getModel()->fetch())) {
                    $document['links']['first'] = (string) $this->getRoute($route, array('offset' => 0));
                }

                if ($limit && $total-($limit + $offset) > 0) {
                    $document['links']['next'] = (string) $this->getRoute($route, array('offset' => $limit+$offset));
                }

                if ($limit && $offset && $offset >= $limit) {
                    $document['links']['prev'] = (string) $this->getRoute($route, array('offset' => max($offset-$limit, 0)));
                }
            }
        }
        else $document['data'] = $this->_createResource($entity = $this->getModel()->fetch()->top());

        $context->content = $document;
    }

    /**
     * Creates a resource object specified by JSON API
     *
     * @see   http://jsonapi.org/format/#document-resource-objects
     *
     * @param KModelEntityInterface  $entity   Document row
     * @param array $config Resource configuration.
     * @return array The array with data to be encoded to json
     */
    protected function _createResource(KModelEntityInterface $entity)
    {
        //Data
        $resource = [
            'type'       => $this->_getEntityType($entity),
            'id'         => $this->_getEntityId($entity),
            'attributes' => $this->_getEntityAttributes($entity),
        ];

        //Links
        if($links = $this->_getEntityLinks($entity)) {
            $resource['links'] = $links;
        }

        //Relationships
        if ( $relationships = $this->_getEntityRelationships($entity)) {
            $resource['relationships'] = $relationships;
        }

        return $resource;
    }

    /**
     * Get the entity id
     *
     * @param KModelEntityInterface  $entity
     * @return string
     */
    protected function _getEntityId(KModelEntityInterface $entity)
    {
        $values = array();
        foreach($this->getModel()->getPrimaryKey() as $key){
            $values[] = $entity->{$key};
        }

        return implode('/', $values);
    }

    /**
     * Get the entity type
     *
     * @param KModelEntityInterface  $entity
     * @return string
     */
    protected function _getEntityType(KModelEntityInterface $entity)
    {
        return $this->getModel()->getType();
    }

    /**
     * Get the entity attributes
     *
     * @param KModelEntityInterface  $entity
     * @return array
     */
    protected function _getEntityAttributes(KModelEntityInterface $entity)
    {
        $attributes = $entity->toArray();

        //Cast objects to string
        foreach($attributes as $key => $value)
        {
            //Qualify the url's
            if($value instanceof KHttpUrlInterface) {
                $value = $this->getUrl($value);
            }

            if(is_object($value))
            {
                if(!method_exists($value, '__toString')) {
                    unset($attributes[$key]);
                } else {
                    $attributes[$key] = (string) $value;
                }
            }
        }

        //Remove the identity key from the attributes
        $key = $entity->getIdentityKey();
        if(isset($attributes[$key])) {
            unset($attributes[$key]);
        }

        if(!$this->isCollection() && method_exists($entity, 'getContent'))
        {
            $attributes['content'] = [
                'body' => $entity->getContent(),
                'type' => $entity->getContentType(),
            ];
        }

        return $attributes;
    }

    /**
     * Get the entity links
     *
     * @param KModelEntityInterface  $entity
     * @return array
     */
    protected function _getEntityLinks(KModelEntityInterface $entity)
    {
        $links = array();

        if($this->isCollection())
        {
            $query = array();
            foreach($this->getModel()->getPrimaryKey() as $key){
                $query[$key] = $entity->{$key};
            }

            $url = $this->getRoute($this->getModel()->getPage(), $query);
            $links = ['self' => (string) $url];
        }

        return $links;
    }

    /**
     * Get the entity relationships
     *
     * @param KModelEntityInterface  $entity
     * @return array
     */
    protected function _getEntityRelationships(KModelEntityInterface $entity)
    {
        return array();
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
}