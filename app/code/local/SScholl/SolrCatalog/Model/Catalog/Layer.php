<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Catalog_Layer
	extends Mage_Catalog_Model_Layer
{

	/**
	 * Retrieve current layer product collection
	 *
	 * @return SScholl_SolrCatalog_Model_Resource_Product_Collection
	 */
	public function getProductCollection()
	{
		if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
			$collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
		} else {
			if ( $this->getCurrentCategory()->getData('sschollsolrcatalog_is_category') ) {
				$collection = Mage::getResourceModel('sschollsolrcatalog/product_collection');
				$collection->addFieldToFilter('price', array('from' => 0.01));
				$collection->setCategory($this->getCurrentCategory());
			} else {
				$collection = $this->getCurrentCategory()->getProductCollection();
			}
			$this->prepareProductCollection($collection);
			$this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
		}
	
		return $collection;
	}
	
}