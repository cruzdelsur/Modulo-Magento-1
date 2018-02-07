<?php

class Iurco_Cruzdelsur_Model_Resource_Order_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
 {

    protected function _construct()
    {
        parent::_construct();

        $this->_init('cruzdelsur/order');
    }

}
