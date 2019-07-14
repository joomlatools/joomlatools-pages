<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerBehaviorProcessable extends KControllerBehaviorAbstract
{
    public function processData($data, $processors, $channel = 'form')
    {
        foreach($processors as $identifier)
        {
            if(is_array($identifier)) {
                $processor = $this->getProcessor(key($identifier), current($identifier));
            }  else {
                $processor = $this->getProcessor($identifier);
            }

            $processor->setChannel($channel)->processData($data);
        }
    }

    public function getProcessor($processor, $config = array())
    {
        //Create the complete identifier if a partial identifier was passed
        if (is_string($processor) && strpos($processor, '.') === false)
        {
            $identifier = $this->getIdentifier()->toArray();
            $identifier['path'] = array('controller', 'processor');
            $identifier['name'] = $processor;

            $identifier = $this->getIdentifier($identifier);
        }
        else $identifier = $this->getIdentifier($processor);

        $processor = $this->getObject($identifier, $config);

        if (!($processor instanceof ComPagesControllerProcessorInterface))
        {
            throw new UnexpectedValueException(
                "Processor $identifier does not implement ComPagesControllerProcessorInterface"
            );
        }

        return $processor;
    }

    public function isSupported()
    {
        $mixer   = $this->getMixer();
        $request = $mixer->getRequest();

        return $mixer->isDispatched() && $request->isFormSubmit();
    }
}