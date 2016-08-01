<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Catalog_Layer_Filter_Category extends Mage_Catalog_Model_Layer_Filter_Category
{
	
	/**
	 * Get data array for building category filter items
	 *
	 * @return array
	 */
	protected function _getItemsData()
	{
		if ( !$this->getLayer()->getProductCollection() instanceof SScholl_SolrCatalog_Model_Resource_Product_Collection ) {
			return parent::_getItemsData();
		}
		$key = $this->getLayer()->getStateKey().'_SUBCATEGORIES';
		$data = $this->getLayer()->getAggregator()->getCacheData($key);

		if ($data === null) {
			/** @var $categoty Mage_Catalog_Model_Categeory */
			$categoty   = $this->getCategory();
			
			if ( $categoty->getData('sschollsolrcatalog_is_category') ) {
				$categories = $categoty->getCollection();
				/* @var $collection Mage_Catalog_Model_Resource_Category_Collection */
				$categories->addAttributeToSelect('url_key')
					->addAttributeToSelect('name')
					->addAttributeToSelect('all_children')
					->addAttributeToSelect('is_anchor')
					->addAttributeToFilter('is_active', 1)
					->addAttributeToSelect('sschollsolrcatalog_category_query')
					->addIdFilter($categoty->getChildren())
					->setOrder('position', Varien_Db_Select::SQL_ASC)
					->joinUrlRewrite()
					->load();
			} else {
				$categories = $categoty->getChildrenCategories();
			}
			
			$this->getLayer()->getProductCollection()
				->addCountToCategories($categories);

			$data = array();
			foreach ($categories as $category) {
				if ($category->getIsActive() && $category->getProductCount()) {
					$data[] = array(
						'label' => Mage::helper('core')->htmlEscape($category->getName()),
						'value' => $category->getId(),
						'count' => $category->getProductCount(),
					);
				}
			}
			$tags = $this->getLayer()->getStateTags();
			$this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
		}
		return $data;
	}
	
}