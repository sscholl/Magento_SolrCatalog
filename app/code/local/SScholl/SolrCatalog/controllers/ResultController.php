<?php

include_once("Mage/CatalogSearch/controllers/ResultController.php");
/**
 * Catalog Search Controller
 */
class SScholl_SolrCatalog_ResultController extends Mage_CatalogSearch_ResultController
{
	
	/**
	 * Display search result
	 */
	public function indexAction()
	{
		if ( !Mage::helper('sschollsolrcatalog/config')->searchActive() ) return parent::indexAction();
		$query = Mage::helper('catalogsearch')->getQuery();
		/* @var $query Mage_CatalogSearch_Model_Query */
		$query->setStoreId(Mage::app()->getStore()->getId());

		if ($query->getQueryText() != '') {

			Mage::helper('catalogsearch')->checkNotes();

			$this->loadLayout();
			$this->_initLayoutMessages('catalog/session');
			$this->_initLayoutMessages('checkout/session');
			$this->renderLayout();

			if (!Mage::helper('catalogsearch')->isMinQueryLength()) {
				$query->save();
			}
		}
		else {
			$this->_redirectReferer();
		}
	}
}
