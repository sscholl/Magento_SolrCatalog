<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Catalog_Layer_Filter_Price extends Mage_Catalog_Model_Layer_Filter_Price
{
	
	public function getProductCollection()
	{
		return $this->getLayer()->getProductCollection();
	}
	
	public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
	{
		if ( $this->getLayer()->getProductCollection() instanceof SScholl_SolrCatalog_Model_Resource_Product_Collection ) {
			$this->getProductCollection()->setPriceFilterModel($this);
		}
		return parent::apply($request, $filterBlock);
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
		if (Mage::app()->getStore()->getConfig(self::XML_PATH_RANGE_CALCULATION) == self::RANGE_CALCULATION_IMPROVED) {
			return $this->_getCalculatedItemsData();
		} elseif ($this->getInterval()) {
			return array();
		}
		$facet = $this->getProductCollection()->getPriceFacet();
		$priceRanges = $facet->getValues();
		$after = $facet->getAfter();
		$data = array();
		if (!empty($priceRanges)) {
			$keys = array_keys($priceRanges);
			$maxInterval = $this->getMaxIntervalsNumber();
			foreach ( $keys as $index => $value ) {
				$fromPrice = ($value > 0.0) ? $value : '';
				if ( ($index + 1) == $maxInterval ) {
					$toPrice = '';
					$count = $priceRanges[$value] + $after;
				} else {
					$toPrice = $keys[$index + 1];
					$count = $priceRanges[$value];
				}
	
				$data[] = array(
						'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
						'value' => $fromPrice . '-' . $toPrice,
						'count' => $count,
				);
			}
		}
	
		return $data;
	}

	/**
	 * Apply price range filter to collection
	 *
	 * @return Mage_Catalog_Model_Layer_Filter_Price
	 */
	protected function _applyPriceRange()
	{
		if ( !$this->getLayer()->getProductCollection() instanceof SScholl_SolrCatalog_Model_Resource_Product_Collection ) {
			return parent::_applyPriceRange();
		}
		$interval = $this->getInterval();
		if (!$interval) {
			return $this;
		}
		
		list($from, $to) = $interval;
		if ($from === '' && $to === '') {
			return $this;
		}
		$filter = array();
		if ( is_numeric($to) && $to > 0 )	$filter['to'] = $to - 0.01;
		else								$filter['to'] = '*';
		if ( is_numeric($from) )			$filter['from'] = $from;
		else								$filter['from'] = '*';
		$this->getProductCollection()->addFieldToFilter('price', $filter);
		
		return $this;
	}
	
}