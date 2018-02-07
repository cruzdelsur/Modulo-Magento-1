<?php

class Iurco_Cruzdelsur_Helper_Data extends Mage_Core_Helper_Abstract
{

    protected $_apiInstance;

    /**
     * Get module configuration value
     *
     * @param string $value
     * @param string $store
     * @return mixed Configuration setting
     */
    public function config($value, $store = null)
    {
        $store = is_null($store) ? Mage::app()->getStore() : $store;

        $configscope = Mage::app()->getRequest()->getParam('store');
        if ($configscope && ($configscope !== 'undefined') && !is_array($configscope)) {
            if (is_array($configscope) && isset($configscope['code'])) {
                $store = $configscope['code'];
            } else {
                $store = $configscope;
            }
        }

        return Mage::getStoreConfig("carriers/cruzdelsur/$value", $store);
    }


    /**
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)((int)$this->config('active') !== 0);
    }


    /**
     * Log facility
     * @return Mage_Core_Model_Log_Adapter
     */
    public function log($message, $filename = 'Iurco_Cruzdelsur.log')
    {
        if($this->isActive() && $this->config('log')) {
            return Mage::getModel('core/log_adapter', $filename)->log($message);
        }
    }


    /**
     * Whether sandbox mode is enabled or not
     *
     * @return boolean
     */
    public function isSandboxEnabled()
    {
        return (bool) $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::CONFIG_PATH_SANDBOX_MODE);
    }


    /**
     *
     * @return string
     */
    public function getApiProductionUrl()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::API_PRODUCTION_URL);
    }


    /**
     *
     * @return string
     */
    public function getApiSandboxUrl()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::API_SANDBOX_URL);
    }


    /**
     *
     * @return string
     */
    public function getApiInstance()
    {
        if(empty($this->_apiInstance)) {
            $creds = $this->getApiCredentials();

            if(!$creds->getClientId() || !$creds->getUser() || !$creds->getPassword()) {
                Mage::throwException("Invalid credentials. Use CLIENT_ID, USER and PASSWORD");
            }

            $api = new Iurco_Cruzdelsur_Model_Api($creds->getClientId(), $creds->getUser(), $creds->getPassword());

            // setting base url to hit
            if($this->isSandboxEnabled()) {
                $api->set_base_url($this->getApiSandboxUrl());
            } else {
                $api->set_base_url($this->getApiProductionUrl());
            }

            $this->_apiInstance = $api;
        }

        return $this->_apiInstance;
    }


    /**
     * Returns configured code for DNI
     * @return string
     * @see Iurco_Cruzdelsur_Helper_Document::getDocumentNumberAttributeCodeName()
     * @deprecated
     */
    public function getDocumentNumberAttributeCodeName()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_DOCUMENT_NUMBER_CODE_NAME);
    }


    /**
     * Returns configured code for DNI
     * @return string
     */
    public function getVolumeAttributeCodeName()
    {
        return $this->config(Iurco_Cruzdelsur_Model_Cruzdelsur::XPATH_ATTRIBUTE_VOLUME_CODE_NAME);
    }


    /**
     * Returns an array with enabled product type names for this Carrier
     * @return array
     */
    public function getEnabledProductTypes()
    {
        return array(
            Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
        );
    }


    /**
     * Replace dot for coma, like carrier expects to receive
     * @return
     */
    public function convertPrice($price)
    {
        return str_replace('.',',',$price);
    }


    /**
     * Returns which dispatch mode is configured for the current store
     *
     * @return integer
     */
    public function getConfiguredDispatchMode()
    {
        return (int) $this->config(Iurco_Cruzdelsur_Model_Source_Config_Dispatch::CONFIG_PATH_DISPATCH_MODE);
    }


    /**
     * Whether Disaptch Mode is enabled when the `Ship` button is clicked
     * @return boolean
     */
    public function isDispatchOnOrderShipped()
    {
        $onship = Iurco_Cruzdelsur_Model_Source_Config_Dispatch::MODE_ORDER_SHIPPED;
        return (bool)($this->getConfiguredDispatchMode() === $onship);
    }


    /**
     * Whether Disaptch Mode is enabled for shipping
     * @return boolean
     */
    public function isDispatchOnOrderPlaced()
    {
        $onplaced = Iurco_Cruzdelsur_Model_Source_Config_Dispatch::MODE_ORDER_PLACED;
        return (bool)($this->getConfiguredDispatchMode() === $onplaced);
    }

    /**
     * Whether Disaptch Mode is enabled for dispatch depending on Order Status
     * @return boolean
     */
    public function isDispatchOnOrderStatus()
    {
        $onstatus = Iurco_Cruzdelsur_Model_Source_Config_Dispatch::MODE_ORDER_STATUS;
        return (bool)($this->getConfiguredDispatchMode() === $onstatus);
    }


    /**
     * Returns Order current Status
     * @return string
     */
    public function getDispatchOrderStatusCurrent()
    {
        return $this->config('dispatch_mode_order_status_current');
    }


    /**
     * Returns the new status to which the Order must be changed afer the dispatch
     * @return string
     */
    public function getDispatchOrderStatusNew()
    {
        return $this->config('dispatch_mode_order_status_new');
    }

    /**
     * Returns if module must send an email to the Customer as soon as a Shipment is created
     * @return bool
     */
    public function isNotificationForShipmentEnabled()
    {
        return (bool)((int)$this->config('email_notification_on_shipment') !== 0);
    }


    /**
     * Returns if module must send an email to the Customer as soon as a Tracking code is received for a specific Shipment
     * @return bool
     */
    public function isNotificationForTrackingCodeEnabled()
    {
        return (bool)((int)$this->config('email_notification_on_tracking_code') !== 0);
    }


    /**
     *
     * @return int
     */
    public function getCartTotals()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $total = 0;

        foreach ($quote->getAllVisibleItems() as $item) {
            if(in_array($item->getProductType(), $this->getEnabledProductTypes())) {
                $qty    = $item->getQty();
                $price  = $item->getPrice();
                $total += ($qty * $price);
            }
        }

        return $total;
    }


    /**
     *
     * @return array
     */
    public function getCartDimensions()
    {
        $this->log(__METHOD__);

        $cart = Mage::getSingleton('checkout/cart')->getQuote();
        $cartData   = array();
        $cartData["weight_total"] = 0;
        $cartData["volume_total"] = 0;
        $volumeCode = $this->getVolumeAttributeCodeName();

        if(!$cart || !$cart->getAllVisibleItems() || !$volumeCode) {
            return false;
        }

        foreach ($cart->getAllVisibleItems() as $item) {
            if(!in_array($item->getProductType(), $this->getEnabledProductTypes())) continue;

            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

            $qty    = $item->getQty();
            $weight = $product->getWeight();
            $volume = $product->getData($volumeCode);

            $data = array();
            $data["qty"]      = $qty;
            $data["weight"]   = $weight;
            $data["volume"]   = $volume;
            $data["name"]     = $item->getProduct()->getName();


            array_push($cartData, $data);

            $cartData["weight_total"] = ($qty * $weight) + $cartData["weight_total"];
            $cartData["volume_total"] = ($qty * $volume) + $cartData["volume_total"];
        }


        $this->log($cartData);

        return $cartData;
    }


    /**
     *
     * @return void
     */
    public function saveEstimationInSession($data)
    {
        Mage::getSingleton('core/session')->setCdsCotizaciones($data);
    }


    /**
     *
     * @return
     */
    public function getEstimationInSession()
    {
        return Mage::getSingleton('core/session')->getCdsCotizaciones();
    }


    /**
     *
     * @return
     */
     public function cleanEstimationInSession()
     {
         Mage::getSingleton('core/session')->unsCdsCotizaciones();
     }


    /**
     *
     * @return
     */
    public function getAddressFromQuote()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $address = $quote->getShippingAddress();

        return $address;
    }


    /**
     * Returns object with clientid, usuario, password values
     *
     * @return Varien_Object
     */
    public function getApiCredentials()
    {
        $object = new Varien_Object();

        if($this->isSandboxEnabled()) {
            $object->setClientId(Iurco_Cruzdelsur_Model_Cruzdelsur::API_SANDBOX_CLIENT_ID);
            $object->setUser(Iurco_Cruzdelsur_Model_Cruzdelsur::API_SANDBOX_USERNAME);
            $object->setPassword(Iurco_Cruzdelsur_Model_Cruzdelsur::API_SANDBOX_PASSWORD);

        } else {
            $object->setClientId($this->config('clientid'));
            $object->setUser($this->config('user'));
            $object->setPassword($this->config('password'));
        }

        return $object;
    }

    /**
     *
     * @param string $reference String with Order Ids concatenated
     * @return string
     */
    public function getTrackingCodes($reference)
    {
        return $this->getApiInstance()->obtenerNICsDeCotizacionesPorReferencia($reference);
    }

    /**
     * Returns cron records limit amount
     * @return int
     */
    public function getCronPageSizeLimit()
    {
        return 50;
    }

}
