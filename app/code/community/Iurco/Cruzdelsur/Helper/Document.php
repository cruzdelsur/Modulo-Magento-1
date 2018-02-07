<?php
/**
 *
 *
 */
class Iurco_Cruzdelsur_Helper_Document extends Iurco_Cruzdelsur_Helper_Data
{

    /**
     * Returns configured code for DNI
     * @return string
     */
    public function getDocumentNumberAttributeCodeName()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_DOCUMENT_NUMBER_CODE_NAME);
    }

    /**
     * Which type is configured (use order, order_address, or separated fields)
     * @see Iurco_Cruzdelsur_Model_Source_Config_Attribute_Config_Type::toOptionArray()
     *
     * @return string
     */
    public function getDocumentNumberCaptureType()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::CONFIG_TYPE_PATH);
    }

    /**
     * Returns Guest Entity from which attribute code must be retrieved
     * @return string
     */
    public function getGuestEntity()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_GUEST_ENTITY);
    }

    /**
     * Returns configured attribute code for Guest users
     * @return string
     */
    public function getGuestAttributeCode()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_GUEST_CODE);
    }

    /**
     * Returns configured attribute code name to be retrieved from sales_flat_order
     * @return string
     */
    public function getOrderAttributeCode()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_SALES_ORDER_CODE);
    }

    /**
     * Returns configured attribute code name to be retrieved from sales_flat_order
     * @return string
     */
    public function getShippingAddressAttributeCode()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_SHIPPING_ADDRESS_CODE);
    }

    // /**
    //  * Retrieves
    //  * @param Mage_Sales_Model_Order $order
    //  * @param mixed Mage_Customer_Model_Customer|boolean $customer
    //  * @return string
    //  */
    // public function getDocumentNumberFromOrder($order, $customer)
    // {
    //
    // }

    /**
     *
     * @return Varien_Object
     */
    private function _getDocumentFromOrder($order)
    {
        $attributeCode  = $this->getOrderAttributeCode();
        $fieldValue     = $order->getData($attributeCode);

        return $fieldValue;
    }

    /**
     *
     * @return Varien_Object
     */
    private function _getDocumentFromShippingAddress($order)
    {
        $attributeCode  = $this->getShippingAddressAttributeCode();
        $fieldValue     = $order->getShippingAddress()->getData($attributeCode);

        return $fieldValue;
    }


    /**
     * Fills Varien_Object with Customer's data
     * @return string
     */
    private function _getDocumentFromCustomer($customer)
    {
        $attributeCode  = $this->getDocumentNumberAttributeCodeName();
        $fieldValue     = $customer->getData($attributeCode);

        return $fieldValue;
    }

    /**
     *
     * @return mixed string|boolean
     */
    private function _getDocumentFromGuest($order)
    {
        $entityCode     = $this->getGuestEntity();
        $attributeCode  = $this->getGuestAttributeCode();
        $fieldValue     = false;

        // Take it from the sales_flat_order
        if($entityCode === Iurco_Cruzdelsur_Model_Cruzdelsur::GUEST_ENTITY_ORDER) {
            $fieldValue = $order->getData($attributeCode);
        }

        // Take it from the Shipping Address in sales_flat_order_address
        if($entityCode === Iurco_Cruzdelsur_Model_Cruzdelsur::GUEST_ENTITY_SHIPPING_ADDRESS) {
            $fieldValue = $order->getShippingAddress()->getData($attributeCode);
        }

        return $fieldValue;
    }

    /**
     * Gets firstname/lastname/email/document based on which capture mode is configured
     * (so if order_address is selected, email comes from there)
     *
     * @return string $documentNumber
     */
    public function getDocumentNumber($order, $customer)
    {
        $captureType    = $this->getDocumentNumberCaptureType();
        $documentNumber = false;

        switch($captureType) {
            case Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_FROM_ORDER:
                $documentNumber = $this->_getDocumentFromOrder($order);
                break;

            case Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_FROM_ORDER_ADDRESS:
                $documentNumber = $this->_getDocumentFromShippingAddress($order);
                break;

            case Iurco_Cruzdelsur_Model_Cruzdelsur::TYPE_SEPARATED:
                if($order->getCustomerIsGuest()) {
                    $documentNumber = $this->_getDocumentFromGuest($order);
                } else {
                    $documentNumber = $this->_getDocumentFromCustomer($customer);
                }
                break;
        }

$this->log(__METHOD__);
$this->log($documentNumber);

        return $documentNumber;
    }
}
