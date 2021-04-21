<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerProcessorCsv extends ComPagesControllerProcessorAbstract
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';
    const ESCAPE_CHAR = '\\';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'path' => $this->getObject('pages.config')->getSitePath('logs'),
        ]);

        parent::_initialize($config);
    }

    public function getFile()
    {
        return $this->getConfig()->path . '/' . $this->getChannel() . '.csv';
    }

    public function processData(array $data)
    {
        $file = $this->getFile();

        $fields = array();
        foreach ($data as $key => $value)
        {
            //Cast objects to string
            if (is_object($value))
            {
                if (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } else {
                    $value = null;
                }
            }

            //Implode array's
            if (is_array($value))
            {
                if (is_numeric(key($value))) {
                    $value = implode(',', $value);
                } else {
                    $value = json_encode($value);
                }
            }

            $fields[$key] = $value;
        }

        //Write the header
        if (!file_exists($file) && !is_numeric(key($fields))) {
            $this->_writeCsvFile($file, array_keys($fields));
        }

        //Write the data
        $this->_writeCsvFile($file, $fields);
    }

    protected function _writeCsvFile($file, $data)
    {
        if(!$fp = fopen($file, 'a')) {
            throw new RuntimeException(sprintf('Could not open CSV file for writing: %s', $file));
        }

        if(!fputcsv($fp, $data, static::DELIMITER, static::ENCLOSURE, static::ESCAPE_CHAR)) {
            throw new RuntimeException(sprintf('Could not write to CSV file: %s', $file));
        }

        fclose($fp);
    }
}
