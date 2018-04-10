<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL).
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category
 * @package
 * @author
 * @copyright
 * @license
 */

class Iurco_Cruzdelsur_Model_Api
{
    const API_RESPONSE_KEY_NAME = 'Respuesta';
    const API_RESPONSE_STATUS_KEY_NAME = 'Estado';
    const API_RESPONSE_STATUS_OK_VALUE = 0;
    const API_RESPONSE_DESCRIPTION_KEY_NAME = 'Descripcion';

    protected $_baseUrl;
    protected $_username;
    protected $_password;
    protected $_clientId;


    public function __construct($client_id = "", $username="", $password="")
    {
        $this->_clientId = $client_id;
        $this->_username = $username;
        $this->_password = $password;
    }


    /**
     * Log facility
     *
     * @return Mage_Core_Model_Log_Adapter
     */
    private function log($message)
    {
        return Mage::helper('cruzdelsur')->log($message, 'Cruzdelsur_Api.log');
    }


    /**
     * Sets base url for API
     * @param string $url
     * @return void
     */
    public function set_base_url($url)
    {
        $this->_baseUrl = $url;
    }


    /**
     * Makes GET call to the API
     *
     * @param string $url
     * @param array $params
     * @param boolean $decodeJson
     *
     * @return mixed
     */
    public function getData($url, $params = array(), $decodeJson = true)
    {
        $params['idcliente']    = $this->_clientId;
        $params['ulogin']       = $this->_username;
        $params['uclave']       = $this->_password;

        $queryString = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                if(is_array($value)) {
                    $this->log('ISARRAY');
                    $this->log($value);
                }

                $value = urlencode($value);
                $queryString .= "$key=$value&";
            }
        }

        if ($queryString) $url .= '?' . rtrim($queryString, "&");

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        //Estas dos instrucciones son necesarias para no validar ssl por ahora
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);


        $jsonResponse = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

$this->log('status: ' . $status);
$this->log($url);

        if ($status != 200) {
            $this->log('Error: call to URL failed with status ' . $status);
            $this->log('response: ' . $jsonResponse);
            $this->log('curl_error ' . curl_error($curl));
            $this->log('curl_errno ' . curl_errno($curl));

            // we couldn't contact the endpoint
            // curl_error Couldn't resolve host
            // status = 0, curl_errno() == 6
        }

        $response = $decodeJson ? json_decode($jsonResponse, true) : $jsonResponse;

$this->log($response);

        curl_close($curl);

        return $response;
    }

    /**
     * Extracts status array from api response
     * @param array $apiResponse
     * @return array usually contains `Estado` and `Description`
     */
    private function _extractResponseStatus($apiResponse)
    {
        return isset($apiResponse[self::API_RESPONSE_KEY_NAME]) ? $apiResponse[self::API_RESPONSE_KEY_NAME] : false;
    }

    /**
     * Receives api response key array content and returns boolean
     * @return bool
     */
    public function isResponseSuccess($apiResponse)
    {
        $response = $this->_extractResponseStatus($apiResponse);
        if(!$response || !isset($response[self::API_RESPONSE_STATUS_KEY_NAME])) {
            return false;
        }

        return $response[self::API_RESPONSE_STATUS_KEY_NAME] ? true : false;
    }

    /**
     * Returns an array of arrays with `reference` field and `NIC` value
     * Array (
     *      Array (
     *          [Referencia] => 100000022
     *          [NIC] => 506309104
     *      )
     *      ...
     * )
     * @param array list of fields `Referencias`
     * @return array
     */
    public function obtenerNICsDeCotizacionesPorReferencia($references)
    {
$this->log(__METHOD__);
        $helper = Mage::helper('cruzdelsur');
        if($helper->isSandboxEnabled()){
            $url = $helper->getApiSandboxUrl().'ObtenerNICsDeCotizacionesPorReferencia';
        }else{
            $url = $helper->getApiProductionUrl().'ObtenerNICsDeCotizacionesPorReferencia';
        }
        

        // prepare unique string with values separated by ;
        if(is_array($references)) {
            $references = implode(';', $references);
        }

        $params['referencias'] = $references;
        $return = $this->getData($url, $params);

        return isset($return['NICs']) ? $return['NICs'] : array();
    }

    /**
     *
     * @param array
     * @return
     */
    public function nuevaCotXVol($params)
    {
        $helper = Mage::helper('cruzdelsur');
        if($helper->isSandboxEnabled()){
            $url = $helper->getApiSandboxUrl().'NuevaCotXVol';
        }else{
            $url = $helper->getApiProductionUrl().'NuevaCotXVol';
        }

        return $this->getData($url, $params);
    }

    /**
     * Devuelve un string en formato JSON con la info del Tracking del NIC .
     * @param string
     * @return
     */
    public function getTrackingDeUnNIC($params)
    {
$this->log(__METHOD__);
        $helper = Mage::helper('cruzdelsur');
        if($helper->isSandboxEnabled()){
            $url = $helper->getApiSandboxUrl().'TrackingDeUnNIC';
        }else{
            $url = $helper->getApiProductionUrl().'TrackingDeUnNIC';
        }
        
        $result = $this->getData($url, $params);

$this->log($result);

        return $result;
    }


}
