<?php


class Iurco_Cruzdelsur_Model_Cron
{
    /**
     * @var Iurco_Cruzdelsur_Helper_Data
     */
    private $_helper;

    // private function send

    /**
     * Process
     * @param array $increments array with Order Increment Ids
     */
    private function processTrackingCodes($increments)
    {

        $this->_helper->log(__METHOD__);

        // concatenate all increments
        $string = implode(';', $increments);
        $result = $this->_helper->getTrackingCodes($string);

        $this->_helper->log($increments);
        $this->_helper->log('$string: ' . $string);
        $this->_helper->log($result);

        // $cdsOrder = Mage::getModel('cruzdelsur')->load($incrementId, 'order_increment_id');
        // $cdsOrder->setTrackingCode($trackingId);
        // $cdsOrder->save();

        if(!is_array($result) || !count($result) > 0) {
            return false;
        }

        return $result;
    }

    /**
     * TrackingResultCodes[]
     *  Array(
     *      Array (
     *        [Referencia] => 100000023
     *        [NIC] => 786680039
     *      ),
     *      Array (
     *        [Referencia] => 100000023
     *        [NIC] => 786680039
     *      )
     *      (...)
     *  )
     * @param array $trackingCodes Array<TrackingResultCodes[]>
     */
    private function _updateOrders($trackingCodes)
    {
$this->_helper->log(__METHOD__);
$this->_helper->log($trackingCodes);

        if(!$trackingCodes) {
            $this->_helper->log('ERROR empty $trackingCodes');
            return false;
        }

        // TODO implement check for shipping method
        // double check if shipping method is ours (it MUST be)
        // if(!$shippingMethod) {
        //     return;
        // }

        foreach($trackingCodes as $tracking) {
            $orderIncrementId   = $tracking['Referencia'];
            $trackingCode       = $tracking['NIC'];

            $order      = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
            $cdsOrder   = Mage::getModel('cruzdelsur/order')->loadByIncrementId($orderIncrementId);

            // TODO create Ship if Order isn't
            // We need to check if there's a Shipment created for the Order
            // otherwise, we have to create that
            if (
                $order->getState() == Mage_Sales_Model_Order::STATE_NEW ||
                $order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING
            ) {

                // if order has no shipments
                if(!$order->hasShipments()) {
                    $this->_helper->log($order->getIncrementId() . ' has no shipment.');
                    if($order->canShip()) {
                        $this->_helper->log('Order about to be shipped #' . $orderIncrementId);
                        $shipment = $order->prepareShipment();
                        $shipment->register();
                    } else {
                        $this->_helper->log('Order cant be shipped #' . $orderIncrementId);
                    }

                } else {
                    // load shipment
                    $shipment = $order->getShipmentsCollection()->getFirstItem();
                }

                $this->_helper->log('shipment id: ' . $shipment->getId());
                $this->_helper->log('order id: ' . $order->getId());

                // comment since Order should be in PROCESSING status
                // (since we're asking for tracking numbers for already `dispatched` Orders)
                // $order->setIsInProcess(true);
                // $order->addStatusHistoryComment($this->_helper->__('Automatically SHIPPED by Cruz del Sur.'), false);

                $track = Mage::getModel('sales/order_shipment_track')
                            ->setNumber($trackingCode)
                            ->setCarrierCode(Iurco_Cruzdelsur_Model_Carrier_Cruzdelsur::CARRIER_CODE)
                            ->setTitle('Cruz del Sur - ' . $cdsOrder->getEstimateDescription())
                            ->setDescription($cdsOrder->getEstimateDescription());

                $shipment->addTrack($track);
                $shipment->addComment($this->_helper->__('Shipped by Cruz del Sur. Tracking Id: ' . $trackingCode), true, true);

                // Check if Tracking Code notification is enabled
                if($this->_helper->isNotificationForTrackingCodeEnabled()) {
                    $shipment->sendEmail(true, Mage::helper('cruzdelsur')->__('Cruz del Sur - Tracking Code email sent to Customer.'));
                    $shipment->setEmailSent(true);
                }

                // Save data for current CDS Order
                $cdsOrder->setTrackingCode($trackingCode);
                $cdsOrder->setIsProcessed(1);

                //change order status after tracking code was applied
                $order->setStatus($this->_helper->getStatusToApplyAfterTrackingCode());
                $order->addStatusToHistory($this->_helper->getStatusToApplyAfterTrackingCode(), 'Tracking Code Applied : (' . $trackingCode .')', false);
                $this->_helper->log('Order Modified with Tracking Code ' .$trackingCode );
                $this->_helper->log('New Status: ' . $order->getStatus().' For Order: #' . $order->getIncrementId());

                /**
                 * when shipment is saved, it will fire our observer (since we're listening to it)
                 * so we have added a double check to avoid trying to re-dispatch the shipment twice
                 * @see Iurco_Cruzdelsur_Model_Observer::_processDispatch()
                 */
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($cdsOrder)
                    ->addObject($shipment)
                    ->addObject($order)
                    ->save();

                $this->_helper->log('it should be saved by now...');



            } else {
                $this->_helper->log('CDS Order Increment Id: ' . $cdsOrder->getOrderIncrementId());
                $this->_helper->log('Order getIncrementId: ' . $order->getIncrementId());
                $this->_helper->log('Order hasShipments(): ' . $order->hasShipments());
                $this->_helper->log('Order getState(): ' . $order->getState());

                $cdsOrder->setIsProcessed(1);
                $order->addStatusHistoryComment($this->_helper->__('Cruz del Sur: Cant get Tracking ID. Order is not pending or processing ('.$order->getState().')'), false);

                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($cdsOrder)
                    ->addObject($order)
                    ->save();
            }

        }

    }

    /**
     * Cron in charge of retrieve tracking codes from API
     *
     * @param
     * @return
     */
    public function bulkImportTrackingCodes()
    {
        $this->_helper = Mage::helper('cruzdelsur');
        $this->_helper->log(__METHOD__);

        // grabs all records that were dispatched but not processed by the cron
        $collection = Mage::getModel('cruzdelsur/order')->getCollection();
        $collection->addFieldToFilter(Iurco_Cruzdelsur_Model_Order::IS_DISPATCHED_COLUMN_NAME, array('eq' => '1'));
        $collection->addFieldToFilter(Iurco_Cruzdelsur_Model_Order::IS_PROCESSED_COLUMN_NAME, array('eq' => '0'));
        $collection->addFieldToFilter(Iurco_Cruzdelsur_Model_Order::IS_ACTIVE_COLUMN_NAME, array('eq' => '1'));
        $collection->setPageSize($this->_helper->getCronPageSizeLimit());
        $collection->setCurPage(1);
        $collection->load();

$this->_helper->log('collection count: ' . $collection->count());

        if($collection->count() == 0) {
            $this->_helper->log('Nothing to parse in this run, heading off...');
            return $this;
        }

        // Gets all increment ids from our table
        $increments = array();
        foreach($collection as $cdsOrder) {
            $this->_helper->log($cdsOrder->getOrderIncrementId());
            array_push($increments, $cdsOrder->getOrderIncrementId());
        }

        // Send all increments to CDS API to look for the tracking codes
        $trackingResult = $this->processTrackingCodes($increments);

        // If CDS API returned tracking codes,
        // we to and associate tracking code to each Order and Shipment
        if($trackingResult) {
            $this->_updateOrders($trackingResult);
        } else {
            $this->_helper->log('No tracking results for this run...');
        }

        $this->_helper->log('done');
        return $this;
    }


    /**
     * Executed by cron, parses Orders saved in custom table
     * that matches configured Order Status and dispatches all matching order
     */
     public function bulkDispatchOnOrderStatus()
     {
         $this->_helper = Mage::helper('cruzdelsur');
         $this->_helper->log(__METHOD__);

         // check if module is enabled and configured to use this feature
         if($this->_helper->isActive() && $this->_helper->isDispatchOnOrderStatus()) {

             $collection = Mage::getModel('cruzdelsur/order')->getCollection();
             $collection->addFieldToFilter(Iurco_Cruzdelsur_Model_Order::IS_DISPATCHED_COLUMN_NAME, array('eq' => '0'));
             $collection->addFieldToFilter(Iurco_Cruzdelsur_Model_Order::IS_PROCESSED_COLUMN_NAME, array('eq' => '0'));
             $collection->addFieldToFilter(Iurco_Cruzdelsur_Model_Order::IS_ACTIVE_COLUMN_NAME, array('eq' => '1'));
             $collection->setPageSize($this->_helper->getCronPageSizeLimit());
             $collection->setCurPage(1);

             $collection->load();

             $configuredCurrentStatus   = $this->_helper->getDispatchOrderStatusCurrent();
             $configuredNewStatus       = $this->_helper->getDispatchOrderStatusNew();

             foreach($collection as $cdsOrder) {
                 $orderIncrementId = $cdsOrder->getOrderIncrementId();
                 $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

                 // Check if Order has the same status as the one configured in module
                 if($order->getStatus() == $configuredCurrentStatus) {

                     // Dispatch the Order
                     $result = Mage::helper('cruzdelsur/order')->processDispatch($order, $cdsOrder);

                     if($result) {
                         // Everything worked, now ship the Order
                         $shipment = Mage::helper('cruzdelsur/order')->createShipmentForOrder($order);

                         // and leave the status as per configuration
                         $order->setStatus($configuredNewStatus);
                         $order->save();
                     }
                 }

             }
         }
     }

}
