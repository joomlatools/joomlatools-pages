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
            'path'  => Koowa::getInstance()->getRootPath().'/joomlatools-pages/logs',
        ]);

        parent::_initialize($config);
    }

    public function getFile()
    {
        return $this->getConfig()->path.'/'.$this->getChannel().'.csv';
    }

    public function processData(array $data)
    {
        $file = $this->getFile();

        //Write the header
        if(!file_exists($file) && !is_numeric(key($data))) {
            $this->_writeCsvFile($file, array_keys($data));
        }

        //Write the data
        $this->_writeCsvFile($file, $data);
    }

    protected function _writeCsvFile($file, $data)
    {
        $fp = fopen($file, 'a');

        if(!fputcsv($fp, $data, static::DELIMITER, static::ENCLOSURE, static::ESCAPE_CHAR)) {
            throw new RuntimeException('Could not write to CSV file:', $file);
        }

        fclose($fp);
    }
}