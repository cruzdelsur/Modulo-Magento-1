<?php
/**
 *
 *
 */
class Iurco_Cruzdelsur_Model_Source_Config_Recipient
{

   /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array = array();

        $array[] = array(
            'value' => '',
            'label' => Mage::helper('cruzdelsur')->__('Select an option...')
        );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::RECIPIENT_FROM_ORDER,
            'label' => Mage::helper('cruzdelsur')->__('Capture from Order')
        );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::RECIPIENT_FROM_ORDER_SHIPPING_ADDRESS,
            'label' => Mage::helper('cruzdelsur')->__('Capture from Order Shipping Address')
        );

        return $array;
    }

}
