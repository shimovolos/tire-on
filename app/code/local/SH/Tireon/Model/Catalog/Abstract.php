<?php

/**
 * Class SH_Tireon_Model_Catalog_Abstract
 */
abstract class SH_Tireon_Model_Catalog_Abstract
{
    /**
     * @param $var
     * @return string
     */
    public function encoding($var)
    {
        return mb_convert_encoding($var, 'UTF-8', 'Windows-1251');
    }

    /**
     * @param $model
     * @param array $data
     * @return mixed
     */
    public function checkExistingModel($model, $data)
    {
        $collection = Mage::getModel($model)
            ->getCollection()
            ->addAttributeToFilter($data['field'], array('eq' => $data['value']))
            ->getFirstItem();

        return $collection;
    }
}