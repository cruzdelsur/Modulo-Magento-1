<?php
// @var $setup Mage_Eav_Model_Entity_Setup
$setup = $this;

$connection = $setup->getConnection();

// ****************************************
// adds columns for processed/tracking order relaced
$connection->addColumn($setup->getTable('cruzdelsur_order'), 'comment', 'varchar(255) AFTER `is_processed`');


$setup->endSetup();
