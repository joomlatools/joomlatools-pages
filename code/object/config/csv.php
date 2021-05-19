<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesObjectConfigCsv extends KObjectConfigFormat
{
    /**
     * The format
     *
     * @var string
     */
    protected static $_format = 'text/csv';

    /**
     * Character used for enclosing fields
     *
     * @var string
     */
    const ENCLOSURE = '"';

    /**
     * Character used for separating fields
     *
     * @var string
     */
    const DELIMITER = ',';

    /**
     * The escape character
     *
     * @var string
     */
    const ESCAPE_CHAR = '\\';

    /**
     * Read from a CSV string and create a config object
     *
     * @param  string $string
     * @param  bool    $object  If TRUE return a ConfigObject, if FALSE return an array. Default TRUE.
     * @throws DomainException
     * @throws RuntimeException
     * @return KObjectConfigCsv|array
     */
    public function fromString($string, $object = true)
    {
        $data = preg_split("/\r\n|\n|\r/", trim($string));

        //Parse the csv
        array_walk($data, function(&$row) {
            $row = str_getcsv($row, static::DELIMITER, static::ENCLOSURE, static::ESCAPE_CHAR);
        });

        //Get the header
        $header = array_shift($data);

        //Combine row and header
        array_walk($data, function(&$row) use ($header) {
            $row = array_combine($header, array_map('trim', $row));
        });

        return $object ? $this->merge($data) : $data;
    }

    /**
     * Write a config object to a CSV string.
     *
     * @return string|false     Returns a CSV encoded string on success. False on failure.
     */
    public function toString()
    {
        $result = array();

        if($data   = $this->toArray())
        {
            $fp = fopen('php://temp', 'r+b');

            fputcsv($fp, array_keys($data[0]), static::DELIMITER, static::ENCLOSURE, static::ESCAPE_CHAR);
            foreach($data as $row) {
                fputcsv($fp, $row, static::DELIMITER, static::ENCLOSURE, static::ESCAPE_CHAR);
            }

            $result = rtrim(stream_get_contents($fp), "\n");
            fclose($fp);
        }

        return $result;
    }
}