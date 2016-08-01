<?php
/**
 * This class is the parser for the Solr field type org.apache.solr.schema.TrieDateField
 * @author Simon Scholl
 *
 */
class SScholl_SolrCatalog_Model_Product_Attribute_Datetime
	extends SScholl_SolrCatalog_Model_Product_Attribute_Abstract
{
	
	protected $_postfixType = '_dtt';
	
	public function beforeSave(&$attributeName, &$attributeValue, $attribute)
	{
		$attributeName = $attributeName . $this->getPostfix($attribute);
		$attributeValue = $this->getSolrDatetime($attributeValue);
	}
	
	public function afterLoad(&$attributeName, &$attributeValue, $attribute)
	{
		$attributeName = str_replace($this->getPostfix($attribute), '', $attributeName);
		$attributeValue = $this->getMagentoDatetime($attributeValue);
	}
	
	public function getSolrDatetime($value) {
		$value = trim($value);
		$date = date_create_from_format('Y-m-d H:i:s', $value);
		if ( !$date instanceof DateTime ) {
			$date = date_create_from_format('d.m.Y', $value);
		}
		$date = $date->format('Y-m-d H:i:s');
		$date = str_replace(' ', 'T', $date) . 'Z';
		return $date;
	}
	
	public function getMagentoDatetime($value) {
		$value = trim($value);
		$value = str_replace('T', ' ', $value);
		$value = str_replace('Z', '', $value);
		return $value;
	}
	
}