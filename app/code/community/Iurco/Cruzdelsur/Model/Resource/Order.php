<?php

class Iurco_Cruzdelsur_Model_Resource_Order extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('cruzdelsur/order', 'id');
    }
}
