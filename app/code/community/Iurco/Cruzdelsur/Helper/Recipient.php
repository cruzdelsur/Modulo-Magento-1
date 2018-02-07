<?php
/**
 *
 *
 */
class Iurco_Cruzdelsur_Helper_Recipient extends Iurco_Cruzdelsur_Helper_Data
{

    /**
     * Returns from where Customer's data must be taken from to be saved in `cruzdelsur_order`
     * @return string
     */
    public function getInformationSource()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::CONFIG_RECIPIENT_PATH);
    }


    /**
     * Gets firstname/lastname/email/telephone from Order or Order Shipping Address (sales_flat_order_address)
     * @return mixed Varien_Object|boolean
     */
    public function getInformation($order)
    {
        $source = $this->getInformationSource();
        $recipientData = false;

        switch($source) {
            case Iurco_Cruzdelsur_Model_Cruzdelsur::RECIPIENT_FROM_ORDER:
                $recipientData = $this->_getDataFromOrder($order);
                break;

            case Iurco_Cruzdelsur_Model_Cruzdelsur::RECIPIENT_FROM_ORDER_SHIPPING_ADDRESS:
                $recipientData = $this->_getDataFromShippingAddress($order);
                break;
        }

$this->log(__METHOD__);
$this->log($recipientData);

        return $recipientData;
    }


    /**
     * Fills Varien_Object with Customer Order's data
     * @return Varien_Object
     */
    private function _getDataFromOrder($order)
    {
        $emailAddress   = $order->getCustomerEmail();
        $firstname      = $order->getCustomerFirstname();
        $lastname       = $order->getCustomerLastname();
        $telephone      = $order->getBillingAddress()->getTelephone();

        $data = new Varien_Object();
        $data->setEmail($emailAddress);
        $data->setFirstname($firstname);
        $data->setLastname($lastname);
        $data->setTelephone($telephone);

        return $data;
    }

    /**
     * Fills Varien_Object with Order Shipping Address data
     * @return Varien_Object
     */
    private function _getDataFromShippingAddress($order)
    {
        $address        = $order->getShippingAddress();

        $emailAddress   = $address->getEmail();
        $firstname      = $address->getFirstname();
        $lastname       = $address->getLastname();
        $telephone      = $address->getTelephone();

        $data = new Varien_Object();
        $data->setEmail($emailAddress);
        $data->setFirstname($firstname);
        $data->setLastname($lastname);
        $data->setTelephone($telephone);

        return $data;
    }

}
