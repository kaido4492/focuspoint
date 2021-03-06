<?php
/**
* aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition
 * aheadWorks does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * aheadWorks does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Autorelated
 * @version    2.3.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Autorelated_Test_Block_Blocks_Product extends EcomDev_PHPUnit_Test_Case {
    
    /**
     * @test
     * @doNotIndexAll
     * @dataProvider dataProvider
     */
    public function filterByAtts(Mage_Catalog_Model_Product $currentProduct,$atts,$ids){
        
        $this->_joinedAttributes = array();
        $collection = Mage::getModel('catalog/product')->getCollection();
        foreach($atts as $at){
            //var_dump($at['att']);
            $value = ($at['att'] == 'category_ids')? $currentProduct->getCategory()->getId() : $currentProduct->getData($at['att']);
            $sql = AW_Autorelated_Model_Blocks_Rule::prepareSqlForAtt($at['att'],&$this->_joinedAttributes,$collection,$at['condition'],$value);
            $collection->getSelect()->where($sql);
        }
        //$collection->getSelect()->where('e.entity_id IN(' . implode(',',$ids) . ')');

        $this->assertType('array', $collection->getColumnValues('entity_id'));
    }
    
    public function dataProvider($testame){
        $conditions = Mage::getModel('awautorelated/source_block_product_condition');
        $conditions = $conditions->toOptionArray();
        $att = Mage::getModel('salesrule/rule_condition_product')->loadAttributeOptions()->getAttributeOption();
        
        $productId = 158;
        $ids = array(25157,25155,25138);
        
        $data = array();
        foreach($att as $value=>$name){
            //var_dump($name . '=' . $value);
            array_push($data,array(
            array(
            Mage::getModel('catalog/product')->load($productId),
            array('att'    =>    $value,'condition'    =>    '='),
            $ids)));
        }
        return $data;        

        /*
        return array(
            array(
            Mage::getModel('catalog/product')->load($productId),
            $atts,
            $ids
            ),
        );
        */
    }
}
