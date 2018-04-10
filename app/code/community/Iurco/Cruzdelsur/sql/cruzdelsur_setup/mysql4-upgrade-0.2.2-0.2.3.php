<?php
// @var $setup Mage_Eav_Model_Entity_Setup
$setup = $this;

$connection = $setup->getConnection();

// ****************************************
// adds columns for processed/tracking order relaced
$connection->addColumn($setup->getTable('cruzdelsur_order'), 'is_active', 'tinyint(1) DEFAULT 1 AFTER `is_processed`');


$setup->endSetup();
