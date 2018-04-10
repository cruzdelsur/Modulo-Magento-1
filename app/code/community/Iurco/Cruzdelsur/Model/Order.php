<?php
/**
 *
 *
 *
 */
class Iurco_Cruzdelsur_Model_Order extends Mage_Core_Model_Abstract
{
    const IS_DISPATCHED_COLUMN_NAME = 'is_dispatched';
    const IS_PROCESSED_COLUMN_NAME  = 'is_processed';
    const IS_ACTIVE_COLUMN_NAME     = 'is_active';
    const ORDER_CANCELED_STATUS     = 'canceled';

    const INCREMENT_ID_COLUMN_NAME  = 'order_increment_id';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('cruzdelsur/order');
    }

    /**
     * Load order by system increment identifier
     *
     * @param string $incrementId
     * @return Iurco_Cruzdelsur_Model_Order
     */
    public function loadByIncrementId($incrementId)
    {
        $this->load($incrementId, self::INCREMENT_ID_COLUMN_NAME);
        return $this;
    }

}
