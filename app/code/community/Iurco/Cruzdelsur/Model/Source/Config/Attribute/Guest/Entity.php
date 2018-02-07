<?php

class Iurco_Cruzdelsur_Model_Source_Config_Attribute_Guest_Entity
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
            'label' => Mage::helper('cruzdelsur')->__('--- Guests Entity Attribute ---')
        );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::GUEST_ENTITY_ORDER,
            'label' => Mage::helper('cruzdelsur')->__('From Order (%s)', Iurco_Cruzdelsur_Model_Cruzdelsur::GUEST_ENTITY_ORDER)
        );

        $array[] = array(
            'value' => Iurco_Cruzdelsur_Model_Cruzdelsur::GUEST_ENTITY_SHIPPING_ADDRESS,
            'label' => Mage::helper('cruzdelsur')->__('From Order Address (%s)', Iurco_Cruzdelsur_Model_Cruzdelsur::GUEST_ENTITY_SHIPPING_ADDRESS)
        );

        return $array;
    }

}
