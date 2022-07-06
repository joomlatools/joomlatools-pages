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
    use ComPagesViewTraitUrl, ComPagesViewTraitRoute, ComPagesViewTraitPage;

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
        return !$this->getState()->isUnique();
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
        $format = $this->getPage()->getFormat();

        $document = new \ArrayObject([
            'jsonapi' => ['version' => $this->_version],
            'links'   => [],
            'data'    => []
        ]);

        if($this->isCollection())
        {
            $context->parameters->total = $this->getModel()->count();

            if($format != 'json')
            {
                //Add format specific link
                $document['links'][$format] =  (string) $this->getRoute($route);

                //Set format to json
                $route->query['format'] = 'json';
            }

            $document['links']['self'] = (string) $this->getRoute($route);

            foreach ($this->getModel()->fetch() as $entity) {
                $document['data'][] = $this->_createResource($entity);
            }

            $page = $this->getPage();

            $document['meta'] = array();
            $document['meta']['total'] = $this->getModel()->count();

            if($title = $page->title) {
                $document['meta']['title'] = $title;
            }

            if($description = $page->metadata->get('description')) {
                $document['meta']['description'] = $description;
            }

            if($page->image && $url = $page->image->url) {
                $document['meta']['image'] = (string) $this->getUrl($url);
            }

            if($language = $page->language) {
                $document['meta']['language'] = $language;
            }

            if($this->getModel()->isPaginatable())
            {
                $paginator = $this->getModel()->getPaginator();

                $total  = (int) $paginator->total;
                $limit  = (int) $paginator->limit;
                $offset = (int) $paginator->offset;

                if($limit)
                {
                    $document['meta']['offset'] = $offset;
                    $document['meta']['limit']  = $limit;
                }

                if ($limit && $total > count($this->getModel()->fetch()))
                {
                    $route->query['offset'] = 0;
                    $document['links']['first'] = (string) $this->getRoute($route);
                }

                if ($limit && $total-($limit + $offset) > 0)
                {
                    $route->query['offset'] =  $limit+$offset;
                    $document['links']['next'] = (string) $this->getRoute($route);
                }

                if ($limit && $offset && $offset >= $limit)
                {
                    $route->query['offset'] =  max($offset-$limit, 0);
                    $document['links']['prev'] = (string) $this->getRoute($route);
                }
            }
        }
        else
        {
            $document['links']['self'] = (string) $this->getUrl();
            $document['data'] = $this->_createResource($this->getModel()->fetch()->top());
        }

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
        if(!$id = $entity->{$entity->getIdentityKey()})
        {
            $values = array();
            if($keys = $this->getModel()->getPrimaryKey())
            {
                foreach($keys as $key)
                {
                    $value = $entity->{$key};

                    if($value instanceof ComPagesModelEntityInterface) {
                        $values[] = $value->{$value->getIdentityKey()};
                    } else {
                        $values[] = $value;
                    }

                }

                $id = '/'.trim(implode('/', $values), '/');
            }
        }

        return $id;
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

        //Recursively serialize the attributes
        array_walk_recursive($attributes, function(&$value)
        {
            if(!$value instanceof KModelEntityInterface)
            {
                //Qualify the url's
                if($value instanceof KHttpUrlInterface) {
                    $value = $this->getUrl($value);
                }

                if(is_object($value))
                {
                    if(!method_exists($value, '__toString')) {
                        $value = null;
                    } else {
                        $value = (string) $value;
                    }
                }
            }
            else $value = $this->_getEntityAttributes($value);
        });

        //Remove NULL values
        $filter = function($attributes) use (&$filter)
        {
            foreach($attributes as $k => $v)
            {
                if(!is_array($v))
                {
                    if(is_null($v)) {
                        unset($attributes[$k]);
                    }
                }
                else $attributes[$k] = $filter($v);
            }

            return $attributes;
        };

        $attributes = $filter($attributes);

        //Remove the identity key from the attributes
        $key = $entity->getIdentityKey();
        if(isset($attributes[$key])) {
            unset($attributes[$key]);
        }

        if(!$this->isCollection() && method_exists($entity, 'getContent'))
        {
            if($content = $entity->getContent())
            {
                $attributes['content'] = [
                    'body' => $content,
                    'type' => $entity->getContentType() ?: 'text/plain' ,
                ];
            }
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
        $links  = array();

        if($entity instanceof ComPagesModelEntityPage || $entity instanceof ComPagesModelEntityPages)
        {
            if($self = (string) $this->getRoute($entity))
            {
                if($format = $entity->format)
                {
                    if($format != 'json') {
                        $links[$format] = $self;
                    } else {
                        $links['self'] = $self;
                    }

                    if($format == 'html') {
                        $links['self'] = "$self.json";
                    }

                    $formats = (array) $entity->getModel()->getCollection()->format;
                    foreach($formats as $format)
                    {
                        if($format == 'html') {
                            $links[$format] = "$self";
                        } else {
                            $links[$format] = "$self.$format";
                        }
                    }
                }
            }
        }
        else
        {
            if($self = (string) $this->getRoute($entity))
            {
                if($format = $this->getPage()->getFormat())
                {
                    if($format != 'json') {
                        $links[$format] = $self;
                    } else {
                        $links['self'] = $self;
                    }

                    if($format == 'html') {
                        $links['self'] = "$self.json";
                    }

                    $formats = (array) $entity->getModel()->getCollection()->format;
                    foreach($formats as $format)
                    {
                        if($format == 'html') {
                            $links[$format] = "$self";
                        } else {
                            $links[$format] = "$self.$format";
                        }

                    }
                }

            }
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
}