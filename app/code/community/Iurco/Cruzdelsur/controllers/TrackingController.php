<?php

class Iurco_Cruzdelsur_TrackingController extends Mage_Core_Controller_Front_Action
{

    public function statusAction()
    {
$helper = Mage::helper('cruzdelsur');
$helper->log(__METHOD__);

        $result = array();
        $data = $this->getRequest()->getParams();

        if(!is_array($data) || !isset($data['number'])) {
            $result['error']    = true;
            $result['message']  = Mage::helper('cruzdelsur')->__('Please insert a valid tracking number.');

            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        // pass through data in API format
        $params['nic'] = $data['number'];

        // Call api to get status for this particular NIC
        $api    = $helper->getApiInstance();
        $result = $api->getTrackingDeUnNIC($params);

        // API failed to respond
        if(count($result) == 0) {
            $response['error']      = true;
            $response['message']    = Mage::helper('cruzdelsur')->__('Cannot process your request at this moment, please try again later.');

            $this->getResponse()->setHeader('Content-type', 'application/json');
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        }

        // API returned `Estado != 0` which has failed
        if(
            isset($result['Respuesta']) &&
            isset($result['Respuesta'][0]['Estado']) &&
            $result['Respuesta'][0]['Estado'] != '0'
        ) {
            $realErrorMessage       = isset($result['Respuesta'][0]['Descripcion']) ? $result['Respuesta'][0]['Descripcion'] : Mage::helper('cruzdelsur')->__('Cannot process the item');
            $errorTemplate          = Mage::helper('cruzdelsur')->__('No records found.');

            $response['error']      = true;
            $response['message']    = $errorTemplate;

            $this->getResponse()->setHeader('Content-type', 'application/json');
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        }

        // everything cool -- rendering results now
        $tableRowsBlock = $this->getLayout()->createBlock('cruzdelsur/order_tracking_rows');
        $tableRowsBlock->loadTrackingData($result['Tracking']);

        $response['table_rows'] = $tableRowsBlock->toHtml();
        $response['error'] = false;

        $this->getResponse()->setHeader('Content-type', 'application/json');
        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

}
