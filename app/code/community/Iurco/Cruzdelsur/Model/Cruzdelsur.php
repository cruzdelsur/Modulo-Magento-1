<?php
/**
 *
 */
 class Iurco_Cruzdelsur_Model_Cruzdelsur extends Mage_Core_Model_Abstract
 {
//     const API_PRODUCTION_URL   = 'http://apicds.com/api/';
//     const API_SANDBOX_URL      = 'http://apicds.com/api/';
//
//     const API_SANDBOX_CLIENT_ID    = '7098d9f8-de4c-4d27-9e93-60823a16d405';
//     const API_SANDBOX_USERNAME     = 'ecommerce_test_api';
//     const API_SANDBOX_PASSWORD     = 'api_test_ecommerce';

    const XPATH_PRODUCTION_URL          = 'carriers/cruzdelsur/api_prod';
    const XPATH_SANDBOX_URL             = 'carriers/cruzdelsur/api_sandbox';
    const CONFIG_PATH_PRODUCTION_URL    = 'api_prod';
    const CONFIG_PATH_SANDBOX_URL       = 'api_sandbox';

    const XPATH_PRODUCTION_CLIENT_ID        = 'carriers/cruzdelsur/clientid';
    const XPATH_PRODUCTION_USER             = 'carriers/cruzdelsur/user';
    const XPATH_PRODUCTION_PASSWORD         = 'carriers/cruzdelsur/password';
    const CONFIG_PATH_PRODUCTION_CLIENT_ID  = 'clientid';
    const CONFIG_PATH_PRODUCTION_USER       = 'user';
    const CONFIG_PATH_PRODUCTION_PASSWORD   = 'password';


    const XPATH_SANDBOX_CLIENT_ID          = 'carriers/cruzdelsur/clientid_sandbox';
    const XPATH_SANDBOX_USER               = 'carriers/cruzdelsur/user_sandbox';
    const XPATH_SANDBOX_PASSWORD           = 'carriers/cruzdelsur/password_sandbox';
    const CONFIG_PATH_SANDBOX_CLIENT_ID    = 'clientid_sandbox';
    const CONFIG_PATH_SANDBOX_USER         = 'user_sandbox';
    const CONFIG_PATH_SANDBOX_PASSWORD     = 'password_sandbox';



     const XPATH_SANDBOX_MODE = 'carriers/cruzdelsur/sandbox_mode';
     const CONFIG_PATH_SANDBOX_MODE = 'sandbox_mode';

     const XPATH_ATTRIBUTE_VOLUME_CODE                  = 'carriers/cruzdelsur/attribute_volume_code';
     const XPATH_ATTRIBUTE_VOLUME_CODE_NAME             = 'attribute_volume_code';
     const XPATH_ATTRIBUTE_DOCUMENT_NUMBER_CODE         = 'carriers/cruzdelsur/attribute_document_number_code';


     // Code values entered by hand by Admin user
     // used to grab value for custom installed attributes
     const XPATH_ATTRIBUTE_DOCUMENT_NUMBER_CODE_NAME    = 'attribute_document_number_code';
     const XPATH_ATTRIBUTE_GUEST_ENTITY                 = 'guest_attribute_document_entity';
     const XPATH_ATTRIBUTE_GUEST_CODE                   = 'guest_attribute_document_code';
     const XPATH_ATTRIBUTE_SALES_ORDER_CODE             = 'attribute_document_number_sales_order';
     const XPATH_ATTRIBUTE_SHIPPING_ADDRESS_CODE        = 'attribute_document_number_sales_order_address';


     // Recipient configuration data (firstname, lastname, email address)
     const CONFIG_RECIPIENT_PATH                    = 'recipient_information';
     const RECIPIENT_FROM_ORDER                     = 'order';
     const RECIPIENT_FROM_ORDER_SHIPPING_ADDRESS    = 'shipping_address';


     // Configuration type for Document Number value retrieval
     const CONFIG_TYPE_PATH         = 'attribute_document_number_config_type';
     const TYPE_FROM_ORDER          = 'sales_flat_order';
     const TYPE_FROM_ORDER_ADDRESS  = 'sales_flat_order_address';
     const TYPE_SEPARATED           = 'separated';
     //  const CONFIG_TYPE_ALWAYS_FROM_CUSTOMER     = 'customer';


     // Guest entities from where we must retrieve Document Number
     const GUEST_ENTITY_ORDER               = 'sales_flat_order';
     const GUEST_ENTITY_SHIPPING_ADDRESS    = 'sales_flat_order_address';


}
