<?php

abstract class SScholl_SolrCatalog_Model_Product_Attribute_Abstract
{
	
	protected $_postfixType = '_uuu';
	
	protected $_postfixApp = '_magento';
	
	protected $_postfixMultivalued = '_mv';
	
	protected $_postfixIndexed = '_indexed';
	
	protected $_postfixStored = '_stored';
	
	protected $_isMultivalued = false;
	
	public function getPostfix($attribute)
	{
		return $this->_postfixApp . $this->_postfixType . $this->getMultivalued();
	}
	
	public function isSaveable($attributeName, $attributeValue, $attribute)
	{
		return true;
	}
	
	public function getMultivalued()
	{
		if ( $this->_isMultivalued ) return $this->_postfixMultivalued;
		else return '';
	}
	
	public function isMultivalued()
	{
		return $this->_isMultivalued;
	}
	
	abstract public function beforeSave(&$attributeName, &$attributeValue, $attribute);
	
	abstract public function afterLoad(&$attributeName, &$attributeValue, $attribute);
	
}