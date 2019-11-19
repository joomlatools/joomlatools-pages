<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorPaginatable extends ComPagesModelBehaviorQueryable
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'priority' => self::PRIORITY_LOW,
        ]);

        parent::_initialize($config);
    }

    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('limit', 'int')
            ->insert('offset', 'int')
            ->insert('total', 'int');

    }

    public function isPaginatable()
    {
        return !$this->isAtomic();
    }

    public function getPaginator()
    {
        $paginator = new KModelPaginator(array(
            'offset' => (int)$this->getState()->offset,
            'limit'  => (int)$this->getState()->limit,
            'total'  => (int)$this->getState()->total,
        ));

        return $paginator;
    }

    protected function _beforeFetch(KModelContextInterface $context)
    {
        $state = $context->state;

        if(is_null($state->total) || $state->total > $this->count()) {
            $state->total = $this->count();
        }

        if ($state->limit)
        {
            $limit  = $state->limit;
            $offset = $state->offset;
            $total  = $state->total;

            if ($offset !== 0 && $total !== 0)
            {
                // Recalculate the offset if it is set to the middle of a page.
                if ($offset % $limit !== 0) {
                    $offset -= ($offset % $limit);
                }

                // Recalculate the offset if it is higher than the total
                if ($offset >= $total) {
                    $offset = floor(($total - 1) / $limit) * $limit;
                }

                $state->offset = $offset;
            }

            return parent::_beforeFetch($context);
        }
    }

    protected function _afterReset(KModelContextInterface $context)
    {
        $modified = (array) KObjectConfig::unbox($context->modified);
        if (in_array('limit', $modified))
        {
            $limit = $context->state->limit;

            if ($limit) {
                $context->state->offset = floor($context->state->offset / $limit) * $limit;
            }
        }
    }

    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        return array_slice($data, $state->offset, $state->limit);
    }

    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        return $query->limit($state->limit, $state->offset);
    }
}