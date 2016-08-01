<?php

/**
 * Catalog Cart Controller
 */
class SScholl_SolrCatalog_CheckoutController extends Mage_Core_Controller_Front_Action
{
	
	/**
	 * Display search result
	 */
	public function addAction()
	{
		$params = $this->getRequest()->getParams();
		
		$id = $this->getRequest()->getParam('product');
		$solrProduct = Mage::getModel('sschollsolrcatalog/product')->load($id);
		$solrProduct->unsetData('entity_id');
		$solrProduct->unsetData('stock_item');
		$solrProduct->setData('is_salable', true);
		$catalogProduct = Mage::getModel('catalog/product');
		$catalogProduct->load($catalogProduct->getIdBySku($solrProduct->getSku()));
		if ( !$catalogProduct->getId() ) {
			$catalogProduct = Mage::getModel('catalog/product');
		}
		$catalogProduct->setData($solrProduct->getData());
		$catalogProduct->setAttributeSetId(Mage::helper('sschollbooks')->getAttributeSetId());
		$catalogProduct->setTypeId($solrProduct->getTypeId());
		
		$catalogProduct->setWebsiteIds(array());
		if (Mage::app()->isSingleStoreMode()) {
			$catalogProduct->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
		}
		$catalogProduct->setCategoryIds(array());
		$catalogProduct->save();

		if ( !($stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($catalogProduct)) ) {
			$stockItem = Mage::getModel('cataloginventory/stock_item');
			$stockItem->assignProduct($catalogProduct);
		}
		$stockItem->setData('stock_id', 1);
		$stockItem->setData('use_config_manage_stock', true);
		$stockItem->setData('product_id', (int) $catalogProduct->getId());
		$stockItem->save();
		
		unset($params['product']);
		$routeParams = Mage::helper('checkout/cart')->getAddUrl($catalogProduct, $params, true);
		$this->_redirect('checkout/cart/add', $routeParams);
	}
}
