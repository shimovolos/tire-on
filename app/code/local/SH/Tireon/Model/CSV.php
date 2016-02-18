<?php

/**
 * Class SH_Tireon_Model_CSV
 */
class SH_Tireon_Model_CSV
{
    /**
     * @const file name
     */
    const CSV_FILE_NAME = 'tyres.csv';

    /**
     * @var $csvPath
     */
    private $_csvPath;

    /**
     * @var array
     */
    private $_category = array();

    /**
     * @var array
     */
    private $_product = array();

    /**
     * Parse CSV File
     */
    public function parseCsv()
    {
        $csvFile = $this->_getCsvPath() . DS . self::CSV_FILE_NAME;

        $csv = new Varien_File_Csv();
        /* @var $csv Varien_File_Csv*/
        $csv->setDelimiter(';');
        $data = $csv->getData($csvFile);

        $this->_category = current($data);
        unset($data[0]);

        $this->_product = $data;
    }

    /**
     * @return string
     */
    private function _getCsvPath()
    {
        return $this->_csvPath = Mage::getBaseDir('code') . DS . 'local' . DS .'SH' . DS .'Tireon' . DS . 'data' . DS;
    }

}