<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtJoomlaModelEntityField extends ComPagesModelEntityItem
{
    private $__prepared_value;

    public function setPropertyParams($value)
    {
        if($value && is_string($value)) {
            $value = json_decode($value, true);
        }

        return new ComPagesObjectConfig($value);
    }

    public function getPropertyValue()
    {
        $value = $this->_value;

        if($value && $this->multi)
        {
            if(is_string($value)) {
                $value = explode(',', $value);
            }
        }
        else $value = [];

        return $value;
    }

    public function getContent()
    {
        if(!$this->__prepared_value)
        {
            JPluginHelper::importPlugin('fields');

            $dispatcher = JEventDispatcher::getInstance();

            $context = 'com_content.'.$this->getIdentifier()->getName();

            $content = new stdClass;
            $content->text = $this->content;

            $field = (object) $this->toArray();
            $field->context  = $context;
            $field->rawvalue = $field->value;

            $field->fieldparams = new JRegistry($field->params);
            unset($field->params);

            //Allow plugins to modify the output of the field before it is prepared
            $dispatcher->trigger('onCustomFieldsBeforePrepareField', [$context, $content, &$field]);

            // Gathering the value for the field
            $value = $dispatcher->trigger('onCustomFieldsPrepareField', [$context, $content, &$field]);

            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            // Allow plugins to modify the output of the prepared field
            $dispatcher->trigger('onCustomFieldsAfterPrepareField', [$context, $content, $field, &$value]);

            $this->__prepared_value = $value;
        }

        return $this->__prepared_value;
    }

    public function __toString()
    {
        return $this->getContent();
    }
}