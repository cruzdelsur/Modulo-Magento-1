<?php
/**
 *
 *
 */
 class Iurco_Cruzdelsur_Block_Order_Tracking_Rows extends Mage_Core_Block_Template
 {
     protected function _construct()
     {
         parent::_construct();
         $this->setTemplate('cruzdelsur/order/tracking/rows.phtml');
     }

     /**
      * Sets returning data from api for rendering within template
      * [{
      *     "NumeroDeSucursal" : "1",
      *     "NombreDeSucursal" : "Buenos Aires",
      *     "Titulo" : "Salida del cami\u00f3n",
      *     "Titulo_Ingles" : "Truck Departure",
      *     "Fecha" : "16\/08\/2017 21:37",
      *     "FechaSinHora" : "16\/08\/2017",
      *     "Observacion" : "",
      *     "Observacion_Ingles" : "",
      *     "Dialogo" : ""
      * }, { ... }, { ... }]
      *
      * @param array $apiTrackingObject
      * @return array TrackingObject[]
      */
     public function loadTrackingData($apiTrackingObject)
     {
         if(!$apiTrackingObject || !is_array($apiTrackingObject)) {

         }

         $rows = [];

         foreach($apiTrackingObject as $data) {
             $row = new Varien_Object();
             $row->setDate($data['Fecha']);
             $row->setBranchNumber($data['NumeroDeSucursal']);
             $row->setBranchName($data['NombreDeSucursal']);
             $row->setStatus($data['Titulo']);

             $rows[] = $row;
         }

         $this->setTrackingInformation($rows);
     }
 }
