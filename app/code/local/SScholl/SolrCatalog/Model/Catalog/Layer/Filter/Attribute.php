<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Catalog_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
	
	public function getProductCollection()
	{
		return $this->getLayer()->getProductCollection();
	}
	
	public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
	{
		if ( !$this->getLayer()->getProductCollection() instanceof SScholl_SolrCatalog_Model_Resource_Product_Collection ) {
			return parent::apply($request, $filterBlock);
		}
		
		$this->getProductCollection()->addAttributeFilterModel($this);
	
		$filter = $request->getParam($this->_requestVar);
		if (is_array($filter)) {
			return $this;
		}
		$text = $this->_getOptionText($filter);
		if ($filter && strlen($text)) {
			$this->getProductCollection()->addFieldToFilter($this->getAttributeModel()->getAttributeCode(), $filter);
			$this->getLayer()->getState()->addFilter($this->_createItem($text, $filter));
			$this->_items = array();
		}
		return $this;
	}
	
	/**
	 * Get data for build price filter items
	 *
	 * @return array
	 */
	protected function _getItemsData()
	{
		if ( !$this->getLayer()->getProductCollection() instanceof SScholl_SolrCatalog_Model_Resource_Product_Collection ) {
			return parent::_getItemsData();
		}
		$attribute = $this->getAttributeModel();
		$facetData = $this->getProductCollection()->getAttributeFacet($attribute->getAttributeCode());
		if ( !$facetData ) {
			return array();
		} else {
			$facetData = $facetData->getValues();
		}
		$this->_requestVar = $attribute->getAttributeCode();
		$options = $attribute->getFrontend()->getSelectOptions();
		$data = array();
		foreach ($options as $option) {
			if (is_array($option['value'])) {
				continue;
			}
			if (Mage::helper('core/string')->strlen($option['value'])) {
				// Check filter type
				if ($this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
					if (!empty($facetData[$option['value']])) {
						$data[] = array(
								'label' => $option['label'],
								'value' => $option['value'],
								'count' => $facetData[$option['value']],
						);
					}
				}
				else {
					$data[] = array(
							'label' => $option['label'],
							'value' => $option['value'],
							'count' => isset($facetData[$option['value']]) ? $facetData[$option['value']] : 0,
					);
				}
			}
		}
		return $data;
	}
	
}