<?php

class Iurco_Cruzdelsur_Block_Calculador extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cruzdelsur/cart/calculador.phtml');
    }

    /**
     *
     * @return string
     */
    public function getCityFromQuote()
    {
        $address = Mage::helper('cruzdelsur')->getAddressFromQuote();
        if($address && $address->getId()) {
            return $address->getCity();
        }

        return '';
    }

}
