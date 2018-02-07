<?php
// @var $setup Mage_Eav_Model_Entity_Setup
$setup = $this;

$setup->startSetup();

$setup->run("
    CREATE TABLE IF NOT EXISTS `{$setup->getTable('cruzdelsur_order')}` (
      `id` int(11) NOT NULL AUTO_INCREMENT UNIQUE PRIMARY KEY,
      `order_id` int(11) NOT NULL,
      `order_increment_id` int(11) NOT NULL,

      `firstname` varchar(255) NOT NULL,
      `lastname` varchar(255) NOT NULL,
      `phone` varchar(255) NOT NULL,
      `document` varchar(255) NOT NULL,
      `email` varchar(255) NOT NULL,

      `street` varchar(255) NOT NULL,
      `city` varchar(255) NOT NULL,
      `region` varchar(255) NOT NULL,
      `postcode` varchar(255) NOT NULL,

      `carrier_code` varchar(50) NOT NULL,

      `estimate_number` int(11) NOT NULL,
      `estimate_codigo_linea` varchar(255) NOT NULL,
      `estimate_delivery_type` varchar(10) NOT NULL,
      `estimate_price` float NOT NULL,
      `estimate_volume` float NOT NULL,
      `estimate_weight` float NOT NULL,
      `estimate_description` varchar(255) NOT NULL,
      `estimate_flat` TEXT NOT NULL,

      `tracking_code` VARCHAR(255) NOT NULL

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");


$setup->endSetup();
