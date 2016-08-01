<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Block_Catalog_Product_List
	extends Mage_Catalog_Block_Product_List
{

	protected $_isCollectionLoaded = false;

	/**
	 * Need use as _prepareLayout - but problem in declaring collection from
	 * another block (was problem with search result)
	 */
	protected function _beforeToHtml()
	{
		$this->prepareCollection();
		return Mage_Catalog_Block_Product_Abstract::_beforeToHtml();
	}
	
	public function prepareCollection()
	{
		if ( !$this->_isCollectionLoaded ) {
			$toolbar = $this->getToolbarBlock();
		
			// called prepare sortable parameters
			$collection = $this->_getProductCollection();
		
			// use sortable parameters
			if ($orders = $this->getAvailableOrders()) {
				$toolbar->setAvailableOrders($orders);
			}
			if ($sort = $this->getSortBy()) {
				$toolbar->setDefaultOrder($sort);
			}
			if ($dir = $this->getDefaultDirection()) {
				$toolbar->setDefaultDirection($dir);
			}
			if ($modes = $this->getModes()) {
				$toolbar->setModes($modes);
			}
		
			// set collection to toolbar and apply sort
			$toolbar->setCollection($collection);
		
			$this->setChild('toolbar', $toolbar);
			Mage::dispatchEvent('catalog_block_product_list_collection', array(
			'collection' => $this->_getProductCollection()
			));
		
			$this->_getProductCollection()->load();
			$this->_isCollectionLoaded = true;
		}
	}
	
	/**
	 * Retrieve loaded category collection
	 *
	 * @return Mage_Eav_Model_Entity_Collection_Abstract
	 */
	public function getLoadedProductCollection()
	{
		$this->prepareCollection();
		return $this->_getProductCollection();
	}
	
}