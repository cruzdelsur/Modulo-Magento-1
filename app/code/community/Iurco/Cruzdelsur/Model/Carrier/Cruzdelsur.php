<?php
class Iurco_Cruzdelsur_Model_Carrier_Cruzdelsur
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    const CARRIER_CODE = 'cruzdelsur';

    protected $_code = 'cruzdelsur';

    /**
     * Check if carrier has shipping tracking option available
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Collect estimation based on Cart
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $helper = Mage::helper('cruzdelsur');
        $helper->log(__METHOD__);

        $result     = Mage::getModel('shipping/rate_result');
        $dimensions = $helper->getCartDimensions();

        // params initialization for API estimation request
        $params = array();
        $params['peso'] = $dimensions['weight_total'];
        $params['volumen'] = $dimensions['volume_total'];
        $params['contrareembolso'] = '0';
        $params['localidad'] = $request->getDestCity();
        $params['codigopostal'] = intval($request->getDestPostcode());
        $params['valor'] = 0;

        // cleanup before execute cotizaciones -- so if fails we dont get anything
        Mage::helper('cruzdelsur')->cleanEstimationInSession();

        // loop cause cant get cart total from helper
        // so if we go back to step 2 and come back here, quote->getGrandTotal() returns 0
        $freeBoxes = 0;

        foreach ($request->getAllItems() as $_item) {
            if(in_array($_item->getProductType(), $helper->getEnabledProductTypes())) {

                //check if item has free shipping attribute 
                if($_item->getFreeShipping()){
                    $freeBoxes += $_item->getQty();
                }
                $price = $_item->getPrice();
                $params['valor'] = ($_item->getQty() * $price) + $params['valor'];
            }
        }
        $this->setFreeBoxes($freeBoxes);

        // convert dot into comma
        $params['valor'] = $helper->convertPrice($params['valor']);
        $params['peso'] = $helper->convertPrice($params['peso']);
        $params['volumen'] = $helper->convertPrice($params['volumen']);

        // call to the API to get rates
        $estimate = $this->cotizarEnvio($params);

        if(
            isset($estimate['Respuesta'])
            && isset($estimate['Respuesta'][0]['Estado'])
            && $estimate['Respuesta'][0]['Estado'] == '0'
            && isset($estimate['Cotizaciones'])
        ) {

            $estimates = $estimate['Cotizaciones'];
            Mage::helper('cruzdelsur')->saveEstimationInSession($estimate);

            foreach($estimates as $key => $estimation) {
                $fulldata = array_merge($estimation, $params);

                switch(strtoupper($estimation['TipoDeEntrega'])) {
                    case Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::RETIRO_SUCURSAL_CODE:
                        $method = $this->_getRetiroSucursal($fulldata, $request);
                        break;
                    case Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::ENTREGA_DOMICILIO_CODE:
                        $method = $this->_getEnvioDomicilio($fulldata, $request);
                        $flag = $helper->getIsDeliveryExpressActive();
                        break;
                    default:
                        $method = false;
                        break;
                }

                if($method) {
                    $result->append($method);
                    if (isset($flag) && $flag && $method = $this->_getEnvioDomicilioQuick($fulldata, $request)) {
                        $result->append($method);
                    }
                } else {
                    $helper->log('switch for selecting delivery type ended up in case `default`. no method...');
                    $helper->log($estimation);
                }
            }

        } else {
            $error_msg = Mage::helper('cruzdelsur')->__('Could not retrieve rates from API for this carrier.');

            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($error_msg);

            return $error;
        }

        return $result;
    }


    /**
     *
     *
     * @return
     */
    protected function _getRetiroSucursal($data, $request)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');
        $helper = Mage::helper('cruzdelsur');
        // check if fixed rate is anabled and override estimation original value
//        if ($helper->isFixedRateEnabled()){
//            $helper->log('Fixed Rate Enabled');
//            $helper->log($helper->getFixedRateamount(). ' applied -- original Shipping Rate: ' . $data['Valor'] );
//            $data['Valor'] = $helper->getFixedRateAmount();
//        }
        //if request has free shipping rule applied or all items has free shipping attribute set shipping price & cost free
        if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
            $helper->log(__METHOD__);
            $helper->log('Free Shipping : true');
            $data['Valor'] = '0,00';
        }
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod(Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_RETIRO_SUCURSAL_CODE);
        $rate->setMethodTitle($data['Descripcion']);
        $rate->setPrice($data['Valor']);
        $rate->setCost($data['Valor']);

        Mage::getModel('core/session')->setCdsRetiroSucursal($data);

        return $rate;
    }


    /**
     *
     *
     * @return
     */
    protected function _getEnvioDomicilio($data, $request)
    {
        $rate = Mage::getModel('shipping/rate_result_method');
        $helper = Mage::helper('cruzdelsur');
        // check if fixed rate is anabled and override estimation original value
//        if ($helper->isFixedRateEnabled()){
//            $helper->log('Fixed Rate Enabled');
//            $helper->log($helper->getFixedRateamount(). ' applied -- original Shipping Rate: ' . $data['Valor'] );
//            $data['Valor'] = $helper->getFixedRateAmount();
//        }

        //if request has free shipping rule applied or all items has free shipping attribute set shipping price & cost free
        if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
            $helper->log(__METHOD__);
            $helper->log('Free Shipping : true');
            $data['Valor'] = '0,00';
        }

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod(Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_ENTREGA_DOMICILIO_CODE);
        $rate->setMethodTitle($data['Descripcion']);
        $rate->setPrice($data['Valor']);
        $rate->setCost($data['Valor']);

        Mage::getModel('core/session')->setCdsEnvioDomicilio($data);

        return $rate;
    }
    /**
     * @param $data
     * @param $request
     * @return
     */
    protected function _getEnvioDomicilioQuick($data, $request)
    {
        $rate = Mage::getModel('shipping/rate_result_method');
        $helper = Mage::helper('cruzdelsur');

        // check if fixed rate is anabled and override estimation original value
        if ($helper->isFixedRateEnabled()){
            $helper->log('Fixed Rate Enabled');
            $helper->log($helper->getFixedRateamount(). ' applied -- original Shipping Rate: ' . $data['Valor'] );
            $data['Valor'] = $helper->getFixedRateAmount();
        }

        //if request has free shipping rule applied or all items has free shipping attribute set shipping price & cost free
        if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {

            $helper->log(__METHOD__);
            $helper->log('Free Shipping : true');
            $data['Valor'] = '0,00';
        }

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod(Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_ENTREGA_DOMICILIO_EXPRESS_CODE);
        $rate->setMethodTitle($helper->getDeliveryExpressTitle());
        $rate->setPrice($data['Valor']);
        $rate->setCost($data['Valor']);

        Mage::getModel('core/session')->setCdsEnvioDomicilio($data);

        return $rate;
    }


    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods() {
        $retirosucursal = 'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_RETIRO_SUCURSAL_CODE;
        $domicilio = 'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_ENTREGA_DOMICILIO_CODE;
        $domicilioExpress = 'cruzdelsur_' . Iurco_Cruzdelsur_Model_Source_Config_Delivery_Types::CARRIER_ENTREGA_DOMICILIO_EXPRESS_CODE;

        return array(
            $this->_code      => $this->getConfigData('name'),
            $domicilio        => 'Envio a Domicilio',
            $retirosucursal   => 'Retiro en Sucursal',
            $domicilioExpress => 'Envio a Domicilio Express',
        );
    }


    /**
     * Cotiza el envio de los productos segun los parametros
     *
     * @param $params
     * @return $costoEnvio
     */
    public function cotizarEnvio($params)
    {

        try {
            $helper = Mage::helper('cruzdelsur');
            $helper->log(__METHOD__);
            $helper->log($params);

            $api = $helper->getApiInstance();
            if($helper->isSandboxEnabled()){
                $url = $helper->getApiSandboxUrl().'NuevaCotXVol';
            }else{
                $url = $helper->getApiProductionUrl().'NuevaCotXVol';
            }

            $response = $api->getData($url, $params);
            $helper->log('response: ');
            $helper->log($response);

            return $response;

        } catch (Exception $e) {
            Mage::helper('cruzdelsur')->log("Error: " . $e);
        }
    }


    /**
     * Dispatch an Order through Cruz del Sur
     *
     * Params example:
     * Array
     *  (
     *      [idlinea] => XXXX-X-X-X
     *      [nombre] => Someone sadfdsa324132123
     *      [documento] => 1233213443423
     *      [telefono] => 123321312312
     *      [email] => someone@mailinator.com
     *      [domicilio] => Av. Testing 123
     *      [referencia] => 100000048
     *  )
     *
     * Return example:
     * [Respuesta] => Array
     * (
     *    [0] => Array
     *      (
     *          [Estado] => 3
     *          [Descripcion] => No se puede despachar: Ya esta pedido el despacho.
     *          [NIC] =>
     *      )
     *  )
     * Array (
     *   [Respuesta] => Array (
     *      [0] => Array (
     *          [Estado] => 0
     *          [Descripcion] =>
     *          [NIC] =>
     *      )
     *   )
     * )
     *
     * @param array $params
     * @return array api dispatch result
     *
     */
    public function dispatchEstimation($params)
    {
        try {
            $helper = Mage::helper('cruzdelsur');
            $helper->log(__METHOD__);
            $helper->log($params);

            $api = $helper->getApiInstance();
            //@deprecated
            //TODO BEGIN workaround/fix for this specific endpoint
            // this `id_cliente` should be removed in favor of `idcliente`
            // already included within `getData()`
            //$creds = Mage::helper('cruzdelsur')->getApiCredentials();
            //$params['id_cliente'] = $creds->getClientId();
            // END workaround

            //TODO encapsulate API response within current model using getters/setters
            if($helper->isSandboxEnabled()){
                $url = $helper->getApiSandboxUrl().'PedirDespachoCotizacion';
            }else{
                $url = $helper->getApiProductionUrl().'PedirDespachoCotizacion';
            }

            $response = $api->getData($url, $params);

            $helper->log('response:');
            $helper->log($response);

            return $response;

        } catch (Exception $e) {
            Mage::helper('cruzdelsur')->log(__METHOD__);
            Mage::helper('cruzdelsur')->log($e);
            Mage::helper('cruzdelsur')->log('Error:');
        }
    }

    /**
     *
     * @return JSON
     */
    public function getTrackingCodeFromEstimation($estimateNumber)
    {
        if(!$estimateNumber) {
            return false;
        }

        try {
            $helper = Mage::helper('cruzdelsur');
            $helper->log(__METHOD__);
            $helper->log($estimateNumber);

            $params = array();
            $params['id'] = $estimateNumber;

            $api = Mage::getModel('cruzdelsur/api');
            if($helper->isSandboxEnabled()){
                $url = $helper->getApiSandboxUrl().'TrackingCotizacion';
            }else{
                $url = $helper->getApiProductionUrl().'TrackingCotizacion';
            }

            $response = $api->getData($url, $params);

            Mage::helper('cruzdelsur')->log('response:');
            Mage::helper('cruzdelsur')->log($response);

            return $response;

        } catch (Exception $e) {
            Mage::helper('cruzdelsur')->log($e);
            Mage::helper('cruzdelsur')->log('Error:');
        }
    }

}
