<?php
// @var $setup Mage_Eav_Model_Entity_Setup
$setup = $this;

$setup->startSetup();


// ****************************************
// Install `volumen` attribute if not exists
$entityType = 'catalog_product';
$attributeCode  = 'volumen';
$attributeLabel = 'Volumen';
$volumeAttributeExists = true;

$result = $setup->getAttribute($entityType, $attributeCode);
if(!$result) {
    $volumeAttributeExists = false;
}

if(!$volumeAttributeExists) {
    $applyTo = array(
        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
    );

    $setup->addAttribute('catalog_product', 'volumen', array(
            'group'         => 'General',
            'type'          => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'backend'       => '',
            'frontend'      => '',
            'class'         => '',
            'default'       => '',
            'label'         => 'Volumen',
            'input'         => 'text',
            'source'        => '',
            'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'visible'       => 1,
            'required'      => 1,
            'searchable'    => 0,
            'filterable'    => 1,
            'unique'        => 0,
            'comparable'    => 0,
            'visible_on_front'          => 0,
            'is_html_allowed_on_front'  => 1,
            'user_defined'  => 1,
            'apply_to'      => implode(',',$applyTo)
    ));

}

$setup->endSetup();
