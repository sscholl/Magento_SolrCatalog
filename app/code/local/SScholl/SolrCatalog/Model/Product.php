<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Product
	extends Mage_Catalog_Model_Product
{
	
	/**
	 * Initialize resources
	 */
	protected function _construct()
	{
		$this->_init('sschollsolrcatalog/product');
	}

	/**
	 * Load entity by attribute
	 *
	 * @param Mage_Eav_Model_Entity_Attribute_Interface|integer|string|array $attribute
	 * @param null|string|array $value
	 * @param string $additionalAttributes
	 * @return bool|Mage_Catalog_Model_Abstract
	 */
	public function loadByAttribute($attribute, $value, $additionalAttributes = '*')
	{
		$collection = $this->getResourceCollection()
			//->addAttributeToSelect($additionalAttributes)
			->addAttributeToFilter($attribute, $value)
			->setPage(1,1);
		foreach ($collection as $object) {
			return $object;
		}
		return false;
	}

	/**
	 * Get collection instance
	 *
	 * @return object
	 */
	public function getResourceCollection()
	{
		if (empty($this->_resourceCollectionName)) {
			Mage::throwException(Mage::helper('core')->__('Model collection resource name is not defined.'));
		}
		return Mage::getResourceModel($this->_resourceCollectionName, $this->_getResource());
	}

	/**
	 * @return Mage_Core_Model_Abstract
	 */
	public function _beforeSave()
	{
		return $this;
	}

	/**
	 * @return Mage_Core_Model_Abstract
	 */
	public function _afterSave()
	{
		return $this;
	}
	
	public function isDeleteable()
	{
		return false;
	}
	
}