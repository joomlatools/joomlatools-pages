<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Sparsable Model Behavior
 *
 * By making a model sparsable, you enables the ability for clients to choose the returned properties of a model
 * entity with URL query parameters. This is useful for optimizing requests, making API calls more efficient
 * and fast.
 *
 * A client can request to get only specific fields in the response by including a fields[TYPE] parameter. The
 * value of the fields parameter MUST be a comma-separated (U+002C COMMA, “,”) list that refers to the name(s)
 * of the fields to be returned.
 *
 * The behavior will ALAWYS include the identity key property of the specific type in the returned properties.
 *
 * Based on the Sparse Fieldsets specification in the JSON API
 * @link http://jsonapi.org/format/#fetching-sparse-fieldsets
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 */
class ComPagesModelBehaviorSparsable extends ComPagesModelBehaviorQueryable
{
    /**
     * Insert the model states
     *
     * @param KObjectMixable $mixer
     */
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $mixer->getState()
            ->insert('fields', 'cmd', array());
    }

    /**
     * Parse the fields state
     *
     * @param   KModelContextInterface $context A model context object
     * @return  void
     */
    protected function _afterReset(KModelContextInterface $context)
    {
        if($context->modified->contains('fields'))
        {
            $fields = $context->state->fields;

            foreach ($fields as $type => $value)
            {
                if(is_string($value)) {
                    $fields[$type] = array_unique(explode(',', $value));
                }
            }

            $context->state->fields = $fields;
        }
    }

    /**
     * Filter data fields
     *
     * @param   KModelContextInterface $context A model context object
     * @return  void
     */
    protected function _queryArray(array $data, KModelStateInterface $state)
    {
        $fields = $state->fields;
        $type   = $this->getType();

        if(isset($fields[$type]))
        {
            //Always include the unique states (required for routing)
            $fields = array_flip(array_merge($fields[$type], $state->getNames(true)));

            foreach($data as $key => $item) {
                $data[$key] = array_intersect_key($item, $fields);
            }
        }

        return $data;
    }

    /**
     * Add query colums based on fields
     *
     * @param   KModelContextInterface $context A model context object
     * @return  void
     */
    protected function _queryDatabase(KDatabaseQuerySelect $query, KModelStateInterface $state)
    {
        $table   = $this->getTable();
        $columns = $table->getColumns(true);

        $fields = $state->fields;
        $type   = $this->getType();

        if(isset($fields[$type]))
        {
            $result  = array();
            $columns = array_keys($table->getColumns());

            foreach($fields[$type] as $field)
            {
                if(in_array($field, $columns))
                {
                    $column = $table->mapColumns($field);
                    $result[] = 'tbl.'.$column;
                }
            }

            if(!empty($result))
            {
                $query->columns = array();

                //Always include the identity column
                $result[] = 'tbl.'.$table->getIdentityColumn();
                $query->columns($result);
            }
        }

        return $query;
    }
}