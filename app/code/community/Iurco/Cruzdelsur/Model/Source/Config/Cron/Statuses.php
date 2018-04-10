<?php
/**
 * Iurco_Cruzdelsur
 *
 * @category    Ez
 * @package     Iurco_Cruzdelsur
 * @copyright   Copyright (c) 2018 IURCO
 */

class Iurco_Cruzdelsur_Model_Source_Config_Cron_Statuses
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $statusesOptions = [];
        $statuses = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
        foreach ($statuses as $status){
            $statusesOptions[]= array(
                'value' => $status['status'], 'label' => $status['label']
            );
        }
        return $statusesOptions;
    }

}
