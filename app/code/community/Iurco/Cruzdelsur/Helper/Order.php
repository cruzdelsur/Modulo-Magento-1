<?php
/**
 * Handles all common tasks related to Orders
 *
 */
class Iurco_Cruzdelsur_Helper_Order extends Iurco_Cruzdelsur_Helper_Data
{

    /**
     * Process dispatch
     *
     * @param Mage_Sales_Model_Order $order
     * @param Iurco_Cruzdelsur_Model_Cruzdelsur $cdsOrder
     * @return bool whether dispatch was generated
     */
    public function processDispatch(Mage_Sales_Model_Order $order, Iurco_Cruzdelsur_Model_Order $cdsOrder = null)
    {
        $this->log(__METHOD__);

        // double check we're getting the Order correcly
        if(!$order || !$order->getId()) {
            $this->log('Didnt received the Order, heading off...');
            return false;
        }

        // if its beign fired with `Ship`, we've to load the cruzdelsur_order
        if(!$cdsOrder) {
            $cdsOrder = Mage::getModel('cruzdelsur/order')->load($order->getId(), 'order_id');
        }

        if(!$cdsOrder || !$cdsOrder->getId() ) {
            $this->log('Couldnt retrieve Order from `cruzdelsur_order` table. Check shipping method from Order.');
            $this->log('Order ID: ' . $order->getId());

            return false;
        }

        /**
         * this double check exists cause when Cron saves shipment_track
         * will cause this event to be fired. we need to avoid dispatch the order twice
         * @see Iurco_Cruzdelsur_Model_Cron::_updateOrders()
         */
        if($cdsOrder->getIsDispatched()) {
            $this->log('CDS Order is already dispatched within cruzdelsur_order table');
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
        $this->log($params);
        $carrier    = Mage::getModel('cruzdelsur/carrier_cruzdelsur');
        $result     = $carrier->dispatchEstimation($params);

        //TODO refactor so response can be retrieved by getters within the $carrier model
        //@see Iurco_Cruzdelsur_Model_Carrier_Cruzdelsur::dispatchEstimation()
        if(
            !is_array($result)
            || !isset($result['Respuesta'])
            || !isset($result['Respuesta'][0]['Estado'])
            || !$result['Respuesta'][0]['Estado'] == 0
        ) {
            $this->log('Tried to display estimation but Dispatch failed:');
            $this->log($result);

            $result         = is_array($result['Respuesta']) ? $result['Respuesta'][0] : $result['Respuesta'];
            $statusCode     = isset($result['Estado']) ? $result['Estado'] : Mage::helper('cruzdelsur')->__('No status');
            $errorMessage   = isset($result['Descripcion']) ? $result['Descripcion'] : Mage::helper('cruzdelsur')->__('No description');

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
     *
     * @param $order Mage_Sales_Model_Order
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function createShipmentForOrder($order)
    {
        $this->log(__METHOD__);

        if(!$order->hasShipments()) {
            $shipment = $order->prepareShipment($order->getItemQtys());
            $shipment->register();

            $order->addStatusHistoryComment($this->__('Automatically SHIPPED by Cruz del Sur.'), false);
            $shipment->addComment($this->__('Automatically Shipped by Cruz del Sur.'), true, true);

            // Check if Shipment has an email sent and if module is configured for sending it
            if(!$shipment->getEmailSent() && $this->isNotificationForShipmentEnabled()) {
                $shipment->sendEmail(true, $this->__('Your Order was successfully shipped !'));
                $shipment->setEmailSent(true);
            }

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($order)
                ->save();

        } else {
            $this->log('Order already has a Shipment. skipping...');
            $shipment = $order->getShipmentsCollection()->getFirstItem();
        }

        return $shipment;
    }

}
