<?php
/**
 * Iurco_Cruzdelsur
 *
 * @category    IURCO
 * @package     Iurco_Cruzdelsur
 * @copyright   Copyright (c) 2017 IURCO
 */

class Iurco_Cruzdelsur_Model_Source_Config_Cron_Limit
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 50, 'label' => Mage::helper('cruzdelsur')->__('50')),
            array('value' => 100, 'label' => Mage::helper('cruzdelsur')->__('100')),
            array('value' => 200, 'label' => Mage::helper('cruzdelsur')->__('200')),
            array('value' => 500, 'label' => Mage::helper('cruzdelsur')->__('500')),
            array('value' => 1000, 'label' => Mage::helper('cruzdelsur')->__('1000')),
        );
    }

}
