<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category	Mage
 * @package	 Mage_CatalogSearch
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license	 http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class SScholl_SolrCatalog_Model_CatalogSearch_Layer extends Mage_CatalogSearch_Model_Layer
{
	/**
	 * Get current layer product collection
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection
	 */
	public function getProductCollection()
	{
		if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
			$collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
		} else {
			if ( Mage::helper('sschollsolrcatalog/config')->searchActive() ) {
				$collection = Mage::getResourceModel('sschollsolrcatalog/product_collection');
				$collection->addFieldToFilter('price', array('from' => 0.01));
				//$collection->setCategory($this->getCurrentCategory());
			} else {
				$collection = Mage::getResourceModel('catalogsearch/fulltext_collection');
			}
			$this->prepareProductCollection($collection);
			$this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
		}
		return $collection;
	}
	
}
