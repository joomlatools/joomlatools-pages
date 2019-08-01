<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelStateCollection extends KModelState
{
    public function getNames($unique = false)
    {
        $data = array();

        foreach ($this->_data as $name => $state)
        {
            //Only return unique data
            if($unique)
            {
                //Unique values cannot be null or an empty string
                if($state->unique)
                {
                    $data[] = $state->name;

                    foreach($state->required as $required) {
                        $data[] = $this->_data[$required]->name;
                    }
                }
            }
            else $data[] = $state->name;
        }

        return $data;
    }
}