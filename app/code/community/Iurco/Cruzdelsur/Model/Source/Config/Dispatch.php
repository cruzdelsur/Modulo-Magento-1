<?php
class Iurco_Cruzdelsur_Model_Source_Config_Dispatch
{
    const CONFIG_PATH_DISPATCH_MODE = 'dispatch_mode';

    const MODE_DISABLED         = 0;
    const MODE_ORDER_SHIPPED    = 1;
    const MODE_ORDER_PLACED     = 2;
    const MODE_ORDER_STATUS     = 3;

   /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::MODE_DISABLED, 'label' => Mage::helper('cruzdelsur')->__('Disabled')),
            array('value' => self::MODE_ORDER_SHIPPED, 'label' => Mage::helper('cruzdelsur')->__('On Order Shipped')),
            array('value' => self::MODE_ORDER_PLACED, 'label' => Mage::helper('cruzdelsur')->__('On Order Placed')),
            array('value' => self::MODE_ORDER_STATUS, 'label' => Mage::helper('cruzdelsur')->__('Depends on Order Status'))
        );

    }

}
