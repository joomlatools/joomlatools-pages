<?php


/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ExtDomparserDocumentFactory extends KObject implements KObjectSingleton
{
    /**
     * Load from a string either as xml or html
     *
     * @param string $string The string to load
     * @param bool $xml Load string as xml. Default false
     * @return ExtDomparserDocument
     */
    public static function fromString($string, $xml = false)
    {
        //Create dom document
        $document = new ExtDomparserDocument();

        if (is_string($string) && trim($string) !== '')
        {
            $errors = libxml_use_internal_errors(true);
            $entities = libxml_disable_entity_loader(true);

            if ($xml)
            {
                if (substr($string, 0, 5) !== '<?xml') {
                    $string = '<?xml version="1.0" encoding="UTF-8" ?>' . $string;
                }

                $result = $document->loadXml($string, LIBXML_COMPACT);
            }
            else
            {
                if (substr($string, 0, 9) !== '<!DOCTYPE') {
                    $string = '<!DOCTYPE html>' . $string;
                }

                $result = $document->loadHtml($string, LIBXML_COMPACT | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            }

            //Throw an exception in case an error was found loading the xml/html
            if ($result === false)
            {
                $messages = array();
                foreach (libxml_get_errors() as $error) {
                    $message = '';

                    switch ($error->level) {
                        case LIBXML_ERR_WARNING:
                            $message = "Warning $error->code: ";
                            break;
                        case LIBXML_ERR_ERROR  :
                            $message = "Error $error->code: ";
                            break;
                        case LIBXML_ERR_FATAL  :
                            $message = "Fatal Error $error->code: ";
                            break;
                    }

                    $messages[] = sprintf("%s %s on line: %s, column: %s", $message, trim($error->message), $error->line, $error->column);
                }

                //Do not show the same message twice
                throw new \DomainException(implode('<br>', array_unique($messages)));
            }

            libxml_clear_errors();

            libxml_use_internal_errors($errors);
            libxml_disable_entity_loader($entities);

            $document->normalizeDocument();
        }

        $document->xml = $xml;

        return $document;
    }
    /**
     * Load from an array
     *
     * @param array                The data to load
     * @param bool $html Load string as html. Default false
     * @return ExtDomparserDocument
     */
    public static function fromArray(array $data, $xml = false)
    {
        //Create dom document
        $document = new ExtDomparserDocument();

        $fromArray = function (\DOMNode $node, $data) use ($document, &$fromArray)
        {
            //Create value and attributes
            if (is_array($data))
            {
                if (array_key_exists('@attributes', $data) && is_array($data['@attributes']))
                {
                    if ($node instanceof \DOMElement)
                    {
                        foreach ($data['@attributes'] as $key => $value) {
                            $node->setAttribute($key, $document->encodeValue($value));
                        }
                    }

                    unset($data['@attributes']);
                }

                if (array_key_exists('@fragment', $data))
                {
                    $value = $document->encodeValue($data['@fragment']);

                    $fragment = $document->createDocumentFragment();
                    $fragment->appendXML($value);

                    $node->appendChild($fragment);

                    return $node;
                }
            }

            if (is_array($data))
            {
                //Create child nodes using recursion
                if (!is_numeric(key($data)))
                {
                    // recurse to get the node for that key
                    foreach ($data as $key => $value)
                    {
                        if (is_array($value) && is_numeric(key($value)))
                        {
                            foreach ($value as $k => $v) {
                                $node->appendChild($fromArray($document->createElement($key), $v));
                            }
                        }
                        else
                        {
                            if ($key != '@fragment') {
                                if ($key == '@value') {
                                    $node->appendChild($document->createTextNode($value));
                                } else {
                                    $node->appendChild($fromArray($document->createElement($key), $value));
                                }
                            }
                        }

                        unset($data[$key]); //remove the key from the array once done.
                    }
                }
                else
                {
                    //Create siblling nodes using recursion
                    foreach ($data as $item) {
                        $node = $fromArray($node, $item);
                    }
                }

            } //Append any text values
            else $node->appendChild($document->createTextNode($document->encodeValue($data)));

            return $node;
        };

        //Import the array
        $fromArray($document, $data);

        $document->normalizeDocument();
        $document->xml = $xml;

        return $document;
    }
}