<?php
class Iurco_Cruzdelsur_Model_Source_Config_Attribute_Config_Type
{
   /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array = array();

        array_push($array, array('value' => '', 'label' => Mage::helper('cruzdelsur')->__('--- Document Number Capture Mode ---')));

        // $array[] = array(
        //     'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::CONFIG_TYPE_ALWAYS_FROM_CUSTOMER,
        //     'label' => Mage::helper('cruzdelsur')->__('Always from Customer (%s)', Iurco_Cruzdelsur_Model_Cruzdelsur::CONFIG_TYPE_ALWAYS_FROM_CUSTOMER)
        // );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_FROM_ORDER,
            'label' => Mage::helper('cruzdelsur')->__('Always from Order (%s)', Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_FROM_ORDER)
        );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_FROM_ORDER_ADDRESS,
            'label' => Mage::helper('cruzdelsur')->__('Always from Order Shipping Address (%s)', Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_FROM_ORDER_ADDRESS)
        );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_SEPARATED,
            'label' => Mage::helper('cruzdelsur')->__('Separated Attributes (Customers and Guests)')
        );

        return $array;
    }

}
