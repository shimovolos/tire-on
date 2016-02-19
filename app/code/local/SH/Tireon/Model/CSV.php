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

    const CSV_COLUMN_CATEGORY = 'Категория';
    const CSV_COLUMN_PRODUCT_NAME = 'Наименование';
    const CSV_COLUMN_PRODUCT_PRICE = 'Цена';
    const CSV_COLUMN_PRODUCT_COUNT = 'Количество';

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

    public function __construct()
    {
        $this->_parseCsv();
    }

    /**
     * Parse CSV File
     */
    private function _parseCsv()
    {
        $csvFile = $this->_getCsvPath() . DS . self::CSV_FILE_NAME;

        try {
            $csv = new Varien_File_Csv();
            /* @var $csv Varien_File_Csv*/
            $csv->setDelimiter(';');
            $data = $csv->getData($csvFile);

            $columns = $data[0];
            unset($data[0]);
            foreach($data as $key => $value) {
                $this->_category[] = current($value);
                $this->_product[] = array_combine($columns, $value);
            }
            $this->_category = array_unique($this->_category);

        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

    }

    /**
     * @return string
     */
    private function _getCsvPath()
    {
        return $this->_csvPath = Mage::getBaseDir('code') . DS . 'local' . DS .'SH' . DS .'Tireon' . DS . 'data' . DS;
    }

    /**
     * Build Category Tree
     * @throws Exception
     */
    public function buildCategory()
    {
        $categories = $this->_category;

        $defaultStore = Mage::getModel('core/store')->load(Mage_Core_Model_App::DISTRO_STORE_ID);
        $rootCategoryId = $defaultStore->getRootCategoryId();

        foreach ($categories as $category) {
            $urlKey = Mage::helper('sh_tireon')->transliterate($category);
            $categoryModel = Mage::getModel('catalog/category');
            /* @var $categoryModel Mage_Catalog_Model_Category*/

            try {
                $categoryCollection = $categoryModel
                    ->getCollection()
                    ->addFieldToFilter('url_key', array('eq' => $urlKey))
                    ->getFirstItem();
                if($categoryCollection->isEmpty()) {
                    $categoryModel
                        ->setName($category)
                        ->setUrlKey($urlKey)
                        ->setIsActive(1)
                        ->setDisplayMode('PRODUCTS')
                        ->setIsAnchor(0)
                        ->setStoreId(Mage::app()->getStore()->getId());

                    $parentCategory = Mage::getModel('catalog/category')->load($rootCategoryId);
                    $categoryModel->setPath($parentCategory->getPath());
                    $categoryModel->save();
                }
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        }
    }

    /**
     * Create Products
     * @throws Exception
     */
    public function buildProducts()
    {
        $products = $this->_product;
        foreach ($products as $value) {
            $productModel = Mage::getModel('catalog/product');
            /* @var $productModel Mage_Catalog_Model_Product */

            try {
                $productModel
                    ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
                    ->setAttributeSetId($productModel->getDefaultAttributeSetId())
                    ->setWebsiteIDs(array(1));

                if (!$this->_checkProductIfExist($productModel, Mage::helper('sh_tireon')->transliterate($value[$this->_encoding(self::CSV_COLUMN_PRODUCT_NAME)]))) {

                    foreach ($value as $key => $productValue) {

                        if ($key === $this->_encoding(self::CSV_COLUMN_CATEGORY)) {

                            $productCategoryId = $this->_getProductCategoryId($productValue);
                            $productModel
                                ->setCategoryIds(array($productCategoryId))
                                ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                                ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

                        } elseif ($key == $this->_encoding(self::CSV_COLUMN_PRODUCT_NAME)) {

                            $productModel
                                ->setSku(Mage::helper('sh_tireon')->transliterate($productValue))
                                ->setName($productValue)
                                ->setShortDescription($productValue);

                        } elseif ($key == $this->_encoding(self::CSV_COLUMN_PRODUCT_COUNT)) {

                            $qty = (strpos($productValue, '>') !== false) ? Mage::getStoreConfig('sh_tireon_settings/general/count') : str_replace('>', '', $productValue);

                            $productModel->setStockData(array(
                                    'use_config_manage_stock' => 0,
                                    'manage_stock' => 1,
                                    'is_in_stock' => 1,
                                    'qty' => $qty,
                                )
                            );
                        } elseif ($key == $this->_encoding(self::CSV_COLUMN_PRODUCT_PRICE)) {

                            $finalPrice = $productValue + ($productValue/100) * Mage::getStoreConfig('sh_tireon_settings/general/price_percent');
                            $finalPrice = round($finalPrice, -Mage::getStoreConfig('sh_tireon_settings/general/round'));
                            $productModel
                                ->setPrice($finalPrice)
                                ->setWeight(0);

                        } else {
                            $productModel->setData(Mage::helper('sh_tireon')->transliterate($key), $productValue);
                        }
                    }
                    $productModel->save();
                }
            } catch (Exception $e) {
                Mage::throwException($e->getMessage());
            }
        }
    }

    /**
     * @param $categoryName
     * @return mixed
     */
    private function _getProductCategoryId($categoryName)
    {
        $urlKey = Mage::helper('sh_tireon')->transliterate($categoryName);
        $categoryCollection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToFilter('url_key', array('eq' => $urlKey))
            ->getFirstItem();

        return $categoryCollection->getId();
    }

    /**
     * @param $var
     * @return string
     */
    private function _encoding($var)
    {
        return mb_convert_encoding($var, 'UTF-8', 'Windows-1251');
    }

    /**
     * @param Mage_Catalog_Model_Product $productModel
     * @param $sku
     * @return bool
     */
    private function _checkProductIfExist($productModel, $sku)
    {
        $productCollection = $productModel
            ->getCollection()
            ->addAttributeToFilter('sku', array('eq' => $sku))
            ->getFirstItem();

        $flag = $productCollection->isEmpty() ? false : true;

        return $flag;
    }
}