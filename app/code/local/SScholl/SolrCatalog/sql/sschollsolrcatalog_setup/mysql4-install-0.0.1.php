<?php
/* @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();

$typeId = $this->getEntityTypeId('catalog_category');
$this->addAttribute(
	$typeId,
	'sschollsolrcatalog_category_query',
	array(
		'type'					   => 'varchar',
		'label' 					=> 'Category Solr Query',
		'input'					  => 'text',
		'sort_order'				 => 3,
		'global'					 => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'group'					  => 'General Information',
	)
);
$this->addAttribute(
	$typeId,
	'sschollsolrcatalog_is_category',
	array(
		'type'					   => 'int',
		'label' 					 => 'Is Solr Catalog Category',
		'input'					  => 'select',
		'source'					 => 'eav/entity_attribute_source_boolean',
		'sort_order'				 => 3,
		'global'					 => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'group'					  => 'General Information',
	)
);

$this->endSetup();
