<?php
// @var $setup Mage_Eav_Model_Entity_Setup
$setup = $this;

$connection = $setup->getConnection();

// ****************************************
// adds columns for processed/tracking order relaced
$connection->addColumn($setup->getTable('cruzdelsur_order'), 'is_dispatched', 'tinyint(1) DEFAULT 0 AFTER `tracking_code`');
$connection->addColumn($setup->getTable('cruzdelsur_order'), 'is_processed', 'tinyint(1) DEFAULT 0 AFTER `is_dispatched`');

$setup->endSetup();
