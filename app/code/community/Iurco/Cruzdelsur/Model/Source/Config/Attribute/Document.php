<?php
class Iurco_Cruzdelsur_Model_Source_Config_Attribute_Document
{

   /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array      = array();
        $collection = Mage::getResourceModel('customer/attribute_collection')->getItems();

        array_push($array, array('value' => '', 'label' => Mage::helper('cruzdelsur')->__('--- Address Attributes ---')));

        foreach($collection as $attribute) {
            if(!$attribute->getFrontendLabel()) {
                continue;
            }

            array_push($array, array('value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel()));
        }

        return $array;
    }

}
