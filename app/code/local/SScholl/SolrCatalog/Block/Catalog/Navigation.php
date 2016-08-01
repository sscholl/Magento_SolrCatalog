<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Block_Catalog_Navigation extends Mage_Catalog_Block_Navigation
{

	/**
	 * Retrieve child categories of current category
	 *
	 * @return Varien_Data_Tree_Node_Collection
	 */
	public function getCurrentChildCategories()
	{
		$layer = Mage::getSingleton('catalog/layer');
		$category = $layer->getCurrentCategory();
		/* @var $category Mage_Catalog_Model_Category */
		if ( $this->getCurrentCategory()->getData('sschollsolrcatalog_is_category') ) {
			$categories = $category->getCollection();
			/* @var $collection Mage_Catalog_Model_Resource_Category_Collection */
			$categories->addAttributeToSelect('url_key')
				->addAttributeToSelect('name')
				->addAttributeToSelect('all_children')
				->addAttributeToSelect('is_anchor')
				->addAttributeToFilter('is_active', 1)
				->addAttributeToSelect('sschollsolrcatalog_category_query')
				->addIdFilter($category->getChildren())
				->setOrder('position', Varien_Db_Select::SQL_ASC)
				->joinUrlRewrite()
				->load();
			//$productCollection = Mage::getResourceModel('sschollsolrcatalog/product_collection');
			$productCollection = $layer->getProductCollection();
		} else {
			$categories = $category->getChildrenCategories();
			$productCollection = Mage::getResourceModel('catalog/product_collection');
		}
		$layer->prepareProductCollection($productCollection);
		$productCollection->addCountToCategories($categories);
		return $categories;
	}

}
