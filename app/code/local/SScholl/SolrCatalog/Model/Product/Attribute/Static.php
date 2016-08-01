<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Product_Attribute_Static
	extends SScholl_SolrCatalog_Model_Product_Attribute_Abstract
{
	
	protected $_postfixType = '';

	protected $_staticSolrFields = array(
		//'entity_id',
		//'entity_type_id',
		'attribute_set_id',
		'type_id',
		'created_at',
		'updated_at',
		'category_ids',
		//'has_options',
		//'required_options',
		'sku',
	);
	
	public function isSaveable($attributeName, $attributeValue, $attribute)
	{
		if ( in_array($attributeName, $this->_staticSolrFields) ) {
			return true;
		}
		return false;
	}
	
	public function beforeSave(&$attributeName, &$attributeValue, $attribute)
	{
		if ( in_array($attributeName, array('created_at', 'updated_at')) ) {
			$attributeValue = Mage::getSingleton(
				'sschollsolrcatalog/product_attribute_datetime'
			)->getSolrDatetime($attributeValue);
		}
		$attributeName = $attributeName . $this->getPostfix($attribute);
	}
	
	public function afterLoad(&$attributeName, &$attributeValue, $attribute)
	{
		if ( in_array($attributeName, array('created_at', 'updated_at')) ) {
			$attributeValue = Mage::getSingleton(
				'sschollsolrcatalog/product_attribute_datetime'
			)->getMagentoDatetime($attributeValue);
		}
		$attributeName = str_replace($this->getPostfix($attribute), '', $attributeName);
	}
	
}