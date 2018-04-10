<?php

class Iurco_Cruzdelsur_Model_Observer extends Mage_Core_Model_Session_Abstract
{
    /**
     * Returns an array with carrier codes
     *
     * @return array
     */
    private function getCarrierCodes()
    {
        return [
            'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_ENTREGA_DOMICILIO_CODE,
            'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_RETIRO_SUCURSAL_CODE
        ];
    }


    /**
     * Validates if current carrier is our own
     *
     * @return $observer
     */
    private function isCdsCarrier($shippingMethod)
    {
        $codes = $this->getCarrierCodes();
        return (bool)in_array($shippingMethod, $codes);
    }


    /**
     * Saves Estimation and Order details into custom table for further reference, when Order is placed
     * @see Magento Event `sales_order_place_after`
     *
     * @return $observer
     */
    public function saveCarrierEstimation($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        $order = $observer->getEvent()->getOrder();
        $method = $order->getShippingMethod();

        if(!$this->isCdsCarrier($method)) {
            $helper->log('Carrier isnt from Cruz del Sur');
            $helper->log($method);

            return $observer;
        }

        $estimation = $helper->getEstimationInSession();

        if(!$estimation) {
            $helper->log('Couldnt retrieve estimation from session');
            $helper->log('Order (' . $order->getId() . ') #' . $order->getIncrementId());

            return $observer;
        }

        $retiro = 'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_RETIRO_SUCURSAL_CODE;
        $domicilio = 'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_ENTREGA_DOMICILIO_CODE;
        $data = false;

        switch($method) {
            case $retiro:
                $data = Mage::getModel('core/session')->getCdsRetiroSucursal();
                break;
            case $domicilio:
                $data = Mage::getModel('core/session')->getCdsEnvioDomicilio();
                break;
            default:
                break;
        }

        if(!$data) {
            $helper->log('Data was not retrieved from session getCdsRetiroSucursal() || getCdsEnvioDomicilio(), getEstimationInSession():');
            $helper->log($estimation);

            return $observer;
        }

        // Get ShippingAddress from Order
        $shippingAddress = $order->getShippingAddress();

        // In case Address is using two fields
        $street = $shippingAddress->getStreet();
        if(is_array($street)) {
            $street = implode(' ', $street);
        }

        // @see Iurco_Cruzdelsur_Model_Source_Config_Attribute_Document::toOptionArray()
        $customer = false;
        if(!$order->getCustomerIsGuest()) {
            $customerId = $order->getCustomerId();
            $customer = Mage::getModel('customer/customer')->load($customerId);
        }

        // try to retrieve document number as per configuration
        $docHelper          = Mage::helper('cruzdelsur/document');
        $recipientHelper    = Mage::helper('cruzdelsur/recipient');

        // gets recipient's data (firstname, lastname, email, telephone)
        $recipientData      = $recipientHelper->getInformation($order);

        // gets document number based on configuration
        $documentNumber     = $docHelper->getDocumentNumber($order, $customer);

        // prepare dispatch data -- taken from saved session in step 3 (shipping method)
        $estimateNumber         = isset($estimation['Respuesta'][0]['NumeroCotizacion']) ? $estimation['Respuesta'][0]['NumeroCotizacion'] : false;
        $codigoLinea            = isset($data['CodigoLinea']) ? $data['CodigoLinea'] : '';
        $deliveryType           = isset($data['TipoDeEntrega']) ? $data['TipoDeEntrega'] : '';
        $estimatePrice          = isset($data['Valor']) ? $data['Valor'] : 0;
        $estimateVolume         = isset($data['volumen']) ? $data['volumen'] : '';
        $estimateWeight         = isset($data['peso']) ? $data['peso'] : '';
        $estimateDescription    = isset($data['Descripcion']) ? $data['Descripcion'] : '';
        $estimateFlat           = json_encode($estimation);

        // now fillout our order data
        $model = Mage::getModel('cruzdelsur/order');

        $model->setOrderId($order->getId());
        $model->setOrderIncrementId($order->getIncrementId());
        $model->setFirstname($recipientData->getFirstname());
        $model->setLastname($recipientData->getLastname());
        $model->setPhone($recipientData->getTelephone());
        $model->setDocument($documentNumber);
        $model->setEmail($recipientData->getEmail());
        $model->setStreet($street);
        $model->setCity($shippingAddress->getCity());
        $model->setRegion($shippingAddress->getRegion());
        $model->setPostcode($shippingAddress->getPostcode());
        $model->setCarrierCode($method);
        $model->setEstimateNumber($estimateNumber);
        $model->setEstimateCodigoLinea($codigoLinea);
        $model->setEstimateDeliveryType($deliveryType);
        $model->setEstimatePrice($estimatePrice);
        $model->setEstimateVolume($estimateVolume);
        $model->setEstimateWeight($estimateWeight);
        $model->setEstimateDescription($estimateDescription);
        $model->setEstimateFlat($estimateFlat);
        $model->setTrackingCode('');

        try {
            $model->save();
        } catch(Exception $e) {
            $helper->log('ERROR while saving `cruzdelsur_order` model');
            $helper->log($e);
        }

        // Check if Dispatch is enabled as soon as the Order is Placed
        if($helper->isDispatchOnOrderPlaced()) {
            $helper->log('Dispatch is enabled ON ORDER PLACED');

            if($model->getId()) {
                $result = $this->_processDispatch($order, $model);

                // if dispatch was created, we've to create Shipment
                if($result) {
                    Mage::helper('cruzdelsur/order')->createShipmentForOrder($order);
                }
            }
        }

        return $observer;
    }


    /**
     * Fired up when new `Ship` is created or saved within an Order via backend
     * also this will be called when we create the Shipment when dispatch is on `Order Status` mode
     * but it will be rejected due to `Dispatch Mode` configuration
     *
     * @see Iurco_Cruzdelsur_Model_Cron::bulkDispatchOnOrderStatus()
     * @return $observer
     */
    public function processCarrierDispatch($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        // Check if its enabled
        if(!$helper->isActive()) {
            return $observer;
        }

        // Check if Dispatch is enabled when Order is `Ship`
        if(!$helper->isDispatchOnOrderShipped()) {
            $helper->log('Dispatch is not enabled ON SHIPPING');

            return $observer;
        }

        $shipment   = $observer->getEvent()->getShipment();
        $order      = $shipment->getOrder();
        $method     = $order->getShippingMethod();

        // not my party, lets go..
        if(!$this->isCdsCarrier($method)) {
            $helper->log('Current order isnt from Cruz del Sur: ' . $method);

            return $observer;
        }

        // if nothing comes before shipment is saved -- we must run
        if(!$shipment || !$order->getId()) {
            $helper->log('Couldnt retrieve $shipment || $order from event observer');

            return $observer;
        }

        //Check if order is already dispatched
        $helper->log('checking if order is already dispatched %s', $shipment->getId());
        if($shipment->getId()) {
            $helper->log('Shipment already exists, exit.');
            return $observer;
        }

        // Execute dispatch, sending the `Order` out..
        $result = $this->_processDispatch($order);

        return $observer;
    }


    /**
     * Process dispatch -- called when `Order is Placed` or when `Order is Shipped`
     * $cdsOrder is received only when `Order is Placed` is enabled
     * @TODO this needs to be removed, in favor of Cruzdelsur_Helper_Order::processDispatch()
     *
     * @deprecated
     * @param Mage_Sales_Model_Order $order
     * @param Iurco_Cruzdelsur_Model_Cruzdelsur $cdsOrder
     * @return bool whether dispatch was generated
     */
    private function _processDispatch($order, $cdsOrder = null)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        // double check we're getting the Order correcly
        if(!$order || !$order->getId()) {
            $helper->log('Didnt received the Order, heading off...');
            return false;
        }

        // if its beign fired with `Ship`, we've to load the cruzdelsur_order
        if(!$cdsOrder) {
            $cdsOrder = Mage::getModel('cruzdelsur/order')->load($order->getId(), 'order_id');
        }

        if(!$cdsOrder || !$cdsOrder->getId() ) {
            $helper->log('Couldnt retrieve order shipment details from cruzdelsur_order table');
            $helper->log('Order ID: ' . $order->getId());

            return false;
        }

        /**
         * this double check exists cause when Cron saves shipment_track
         * will cause this event to be fired. we need to avoid dispatch the order twice
         * @see Iurco_Cruzdelsur_Model_Cron::_updateOrders()
         */
        if($cdsOrder->getIsDispatched()) {
            $helper->log('CDS Order is already dispatched from cruzdelsur_order table');
            return true;
        }

        $params = array();
        $params['idlinea']      = $cdsOrder->getEstimateCodigoLinea();
        $params['nombre']       = $cdsOrder->getFirstname() . ' ' . $cdsOrder->getLastname();
        $params['documento']    = $cdsOrder->getDocument();
        $params['telefono']     = $cdsOrder->getPhone();
        $params['email']        = $cdsOrder->getEmail();
        $params['domicilio']    = $cdsOrder->getStreet();
        $params['referencia']   = $cdsOrder->getOrderIncrementId();

        // execute dispatch
        $carrier    = Mage::getModel('cruzdelsur/carrier_cruzdelsur');
        $result     = $carrier->dispatchEstimation($params);

        if(
            !is_array($result)
            || !isset($result['Respuesta'])
            || !isset($result['Respuesta'][0]['Estado'])
            || !$result['Respuesta'][0]['Estado'] == 0
        ) {
            $helper->log('Tried to display estimation but API failed:');
            $helper->log($result);

            $statusCode     = isset($result['Respuesta']['Estado']) ? $result['Respuesta']['Estado'] : Mage::helper('cruzdelsur')->__('No status');
            $errorMessage   = isset($result['Respuesta']['Descripcion']) ? $result['Descripcion'] : Mage::helper('cruzdelsur')->__('No description');

            $order->addStatusHistoryComment(Mage::helper('cruzdelsur')->__('Order couldnt be dispatched by Cruz del Sur. (%s) %s', $statusCode, $errorMessage));
            $order->save();
            return false;
        }

        // flag as dispatched so later cron grabs it to check its tracking code
        $cdsOrder->setIsDispatched(1);
        $cdsOrder->save();

        $order->addStatusHistoryComment(Mage::helper('cruzdelsur')->__('Order was dispatched by Cruz del Sur!'));
        $order->save();

        return true;
    }

    /**
     * If `Dispatch Mode` is ON_ORDER_PLACED then shipment must be created
     * @TODO replace by Helper_Order::createShipmentForOrder()
     * @see Cruzdelsur_Helper_Order::createShipmentForOrder()
     *
     * @deprecated
     * @param $order Mage_Sales_Model_Order
     * @return Mage_Sales_Model_Order_Shipment
     */
    private function _createShipmentForOrder($order)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        if(!$order->hasShipments()) {
            $shipment = $order->prepareShipment();
            $shipment->register();

            $order->setIsInProcess(true);
            $order->addStatusHistoryComment($helper->__('Automatically SHIPPED by Cruz del Sur.'), false);
            $shipment->addComment($helper->__('Automatically Shipped by Cruz del Sur.'), true, true);

            // Check if Shipment has an email sent
            if(!$shipment->getEmailSent()) {
                $shipment->sendEmail(true, $helper->__('Your Order was successfully shipped !'));
                $shipment->setEmailSent(true);
            }

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($order)
                ->save();
        } else {
            $helper->log('Order already has a shipment. skipping...');
            $shipment = $order->getShipmentsCollection()->getFirstItem();
        }

        return $shipment;
    }

    /**
     * Captures Shipping Method -- OnePage checkout step 3
     *
     * @return $observer
     */
    public function saveShippingOnePage($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        $quote      = $observer->getEvent()->getQuote();
        $request    = $observer->getEvent()->getRequest();

        $estimations = Mage::helper('cruzdelsur')->getEstimationInSession();

        // $helper->log($estimations);

        return $observer;
    }

    public function saveShippingMethodSetShippingAmount($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        return $observer;
    }

    public function estimateUpdatePostSetShippingAmount($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        return $observer;
    }

    public function estimatePostSetShippingAmount($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        return $observer;
    }

    /**
     *
     * @return $observer
     */
    public function cleanUpSessionData($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        $helper->cleanEstimationInSession();
        Mage::getModel('core/session')->unsCdsRetiroSucursal();
        Mage::getModel('core/session')->unsCdsEnvioDomicilio();

        return $observer;
    }

    public function salesQuoteCollectTotalsBefore($observer)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        return $observer;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return Varien_Event_Observer
     * Check when order status change
     * if status canceled && shipping method is cruzdelsur
     * set order as disable | is_active = 0
     */
    public function disableCanceledOrders(Varien_Event_Observer $observer)
    {

        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        if ($helper->isActive()) {

            $order = $observer->getEvent()->getOrder();
            $orderStatus = $order->getStatus();
            $orderShippingMethod = $order->getShippingMethod();
            
            if ($orderStatus == Iurco_Cruzdelsur_Model_Order::ORDER_CANCELED_STATUS && in_array($orderShippingMethod, $this->getCarrierCodes())) {
                $cdsOrder = Mage::getModel('cruzdelsur/order')->load($order->getIncrementId(), Iurco_Cruzdelsur_Model_Order::INCREMENT_ID_COLUMN_NAME);
                if ($cdsOrder->getOrderIncrementId()) {
                    try {
                        $helper->log('Disabling Order #' . $order->getIncrementId());
                        $cdsOrder->setIsActive(0);
                        $cdsOrder->setComment('Order Canceled : (' . $order->getUpdatedAt() . ')');
                        $cdsOrder->save();
                        $helper->log('Order canceled by magento, disabling #' . $cdsOrder->getOrderIncrementId());
                        return $observer;
                    } catch (Exception $e) {
                        $helper->log('Couldn\'t save order' . $cdsOrder->getOrderIncrementId());
                        $helper->log($e->getMessage());
                        return $observer;
                    }
                } else {
                    $helper->log('Order not found in cruzdelsur_orders #' . $cdsOrder->getOrderIncrementId());
                    return $observer;
                }
            } else {
                $helper->log('Status (' . $orderStatus . ') or shipping method (' . $orderShippingMethod . ') out of scope. Keep moving...');
                return $observer;
            }
        }
        return $observer;
    }

}
