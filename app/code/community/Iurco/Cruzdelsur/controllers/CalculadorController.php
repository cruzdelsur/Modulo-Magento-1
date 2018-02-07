<?php

class Iurco_Cruzdelsur_CalculadorController extends Mage_Core_Controller_Front_Action
{

    public function costoenvioAction()
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        $result = array();
        $params = $this->getRequest()->getParams();

        if(!is_array($params) || !isset($params['localidad']) || !isset($params['codigopostal'])) {
            $result['error'] = true;
            $result['message'] = 'Ocurrió un error, por favor intente nuevamente.';

            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        $cartData = $helper->getCartDimensions();

        $params['valor'] = $helper->getCartTotals();
        // convert dot into comma
        $params['valor'] = $helper->convertPrice($params['valor']);

        $params['peso'] = $cartData['weight_total'];
        $params['volumen'] = $cartData['volume_total'];
        $params['contrareembolso'] = '0';

        $result = Mage::getModel('cruzdelsur/carrier_cruzdelsur')->cotizarEnvio($params);

        if(count($result) == 0) {
            $result['error'] = true;
            $result['message'] = 'Ocurrió un error, por favor intente nuevamente.';
        }

        if(isset($result['Respuesta']) && $result['Respuesta'][0]['Estado'] != '0') {
            $result['error'] = true;
            $result['message'] = isset($result['Respuesta'][0]['Descripcion']) ? $result['Respuesta'][0]['Descripcion'] : 'Ocurrió un error, por favor intente nuevamente.';
        }

        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

}
