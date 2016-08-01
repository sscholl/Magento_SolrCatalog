<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Resource_Product_Collection
	extends Varien_Data_Collection
{

	/**
	 * get resource model singleton
	 * @return SScholl_SolrCatalog_Model_Resource_Product
	 */
	public function getResource()
	{
		return Mage::getResourceSingleton('sschollsolrcatalog/product');
	}

	/**
	 * Add search query filter
	 *
	 * @param string $query
	 * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
	 */
	public function addSearchFilter($query)
	{
		$this->_query = $query;
		return $this;
	}
	
	public function setCategory(Mage_Catalog_Model_Category $category)
	{
		$this->_category = $category;
		if ( ($filter = $category->getData('sschollsolrcatalog_category_query')) ) {
			if ( strpos($filter, ' ') ) $filter = '"' . $filter . '"';
			$this->addFieldToFilter('category_name', $filter);
		}
	}
	
	public function getCategory()
	{
		return $this->_category;
	}
	
	public function isFacetOnly()
	{
		return $this->_isFacetOnly;
	}
	
	public function setIsFacetOnly($value)
	{
		$this->_isFacetOnly = (bool) $value;
		return $this;
	}
	
	public function getFacetOnlyCollection()
	{
		if ( !$this->_facetOnlyCollection instanceof SScholl_SolrCatalog_Model_Resource_Product_Collection ) {
			$this->_facetOnlyCollection = clone $this;
			$this->_facetOnlyCollection->setIsFacetOnly(true);
			$this->_facetOnlyCollection->load();
		}
		return $this->_facetOnlyCollection;
	}
	
	private $_category = null;
	private $_query = null;
	
	private $_solr = null;
	private $_select = null;
	private $_stats = array();
	private $_isFacetOnly = false;
	private $_facetOnlyCollection = null;
	
	/**
	 * get solr model
	 * @return SScholl_Solr_Model_Solr
	 */
	protected function _solr()
	{
		if (is_null($this->_solr))
			$this->_solr = Mage::getModel(
				'sschollsolr/solr',
				1
			);
		return $this->_solr;
	}
	
	protected function _select()
	{
		if (is_null($this->_select)) {
			$this->_select = $this->_solr()->createSelect();
		}
		return $this->_select;
	}
	
	protected function _result()
	{
		return $this->_result;
	}
	
	protected function _prepareSelect()
	{
		$this->_setPages();
		$this->_setFields();
		$this->_setFilter();
		$this->_setSort();
		$this->_setFacet();
		$this->_setStats();
		return $this->_select();
	}
	
	protected function _setPages()
	{
		$this->_select()->setRows((int) $this->getPageSize());
		$this->_select()->setStart((int) $this->getPageSize() * ($this->_curPage - 1));
	}
	
	protected function _setFields()
	{
		if ( $this->_allFieldsOnSelect ) {
			$this->_select()->setFields($this->getResource()->getQueryFields());
		} else {
			/* get all fields */
			$this->_select()->setFields($this->getResource()->getQueryFieldsProductListing());
			//$this->_select()->addField('*');
			$this->_select()->addField('score');
		}
	}
	
	protected function _setFilter()
	{

		/*$this->addFieldToFilter(
				'name',
				'*' . $this->_getQuery()->getQueryText() . '*'
		);*/
		$this->_select()->clearFilterQueries();
		if ( !empty($this->_filterAttributes) ) {
			foreach ( $this->_filterAttributes as $attribute => $condition ) {
				$attributeModel = $this->getResource()->getAttribute($attribute);
				$backendAttribute = SScholl_SolrCatalog_Model_Product_Attribute_Factory
					::getAttributeModel($attributeModel);
				if ( $attribute == 'entity_id' ) {
					$solrFieldName = 'id';
				} else {
					$solrFieldName = $attribute . $backendAttribute->getPostfix($attributeModel);
				}
				$this->_select()->createFilterQuery($solrFieldName)->setQuery($solrFieldName . ':' . $condition);
			}
		}
		$query = $this->_getQuery()->getQueryText();
		if ( $query ) {
			/*$queryParts = array();
			foreach ( explode(' ', $query) as $queryPart ) $queryParts[] = '*' . $queryPart . '*';
			$this->_select()->setQuery(implode(' OR ', $queryParts));*/
			$this->_select()->setQuery(implode(' AND ', explode(' ', $query)));
			$dismax = $this->_select()->getEDisMax()->setQueryFields('name_magento_str^2 authors_magento_str^4');
		}
	}
	
	protected function _setSort()
	{
		foreach ( $this->_orders as $attribute => $direction ) {
			if (
				$direction === Solarium\QueryType\Select\Query\Query::SORT_ASC
				|| $direction === Solarium\QueryType\Select\Query\Query::SORT_DESC
			) {
				$solrFieldName = $this->getResource()->getSolrFieldName($attribute);
				if ( $solrFieldName ) {
					$this->_select()->addSort($solrFieldName, $direction);
				}
			}
		}
	}
	
	protected function _setFacet()
	{
		/* get the facetset component */
		$facetSet = $this->_select()->getFacetSet();
		$facetSet->clearFacets();
		/* create a facet field instance and set options */
		if ( $this->isAttributeFacetNeeded() ) {
			$facetSet
				->createFacetField('attribute_set_id')
				->setLimit(10)
				->setMinCount(1)
				->setField($this->getResource()->getSolrFieldName('attribute_set_id'));
		}
		
		if ( !$this->isFacetOnly() ) {
			$facetSet
				->createFacetField('category_name')
				->setLimit(1000)
				->setMinCount(1)
				->setField($this->getResource()->getSolrFieldName('category_name'));
			$priceFilter	= $this->getPriceFilterModel();
			$priceRange		= $priceFilter->getPriceRange();
			$maxPrice		= $priceFilter->getMaxPriceInt();
			$facetSet
				->createFacetRange('price')
				->setStart(0)
				->setGap($priceRange)
				->setEnd($maxPrice)
				->setOther(Solarium\QueryType\Select\Query\Component\Facet\Range::OTHER_AFTER)
				->setField($this->getResource()->getSolrFieldName('price'));
			
			foreach ( $this->getAttributeFilterModels() as $filter ) {
				$attributeCode = $filter->getAttributeModel()->getAttributeCode();
				$facetSet
					->createFacetField($attributeCode)
					->setLimit(10)
					->setMinCount(1)
					->setField($this->getResource()->getSolrFieldName($attributeCode));
			}
		}
	}
	
	protected function _setStats()
	{
		foreach ( $this->_stats as $attribute => $value ) {
			$solrFieldName = $this->getResource()->getSolrFieldName($attribute);
			if ( $solrFieldName ) {
				$stats = $this->_select()->getStats();
				$stats->createField($solrFieldName);
			}
		}
	}
	/**
	 * Retrieve query model object
	 *
	 * @return Mage_CatalogSearch_Model_Query
	 */
	protected function _getQuery()
	{
		return Mage::helper('catalogsearch')->getQuery();
	}
	
	protected $_loadAttempts = 0;

	/**
	 * Load data
	 *
	 * @return  Varien_Data_Collection
	 */
	public function loadData($printQuery = false, $logQuery = false)
	{
		if ( $this->isLoaded() || $this->_loadAttempts > 3 ) return $this;
		++ $this->_loadAttempts;
		$select = $this->_prepareSelect();
		try {
			$this->_result = $this->_solr()->getSolarium()->select($select);
		} catch (Exception $e) {
			//if ( $this->_loadAttempts <= 3 ) $this->loadData($printQuery, $logQuery);
			throw new Exception($e->getMessage());
		}
		try {
			if (is_null($this->_result) || is_null($this->_result->getResponse())) {
				//if ( $this->_loadAttempts <= 3 ) $this->loadData($printQuery, $logQuery);
				throw new Exception("Solr result is invalid.");
			}
			$documents = $this->_result->getDocuments();
			foreach ($documents as $doc) {
				$resource = Mage::getResourceModel('sschollsolrcatalog/product');
				$product = $resource->loadSolrFields($doc);
		//		$product->setData($doc->getFields());
				/*	array(
						'entity_id'	=> $doc->id,
						'name_s' => $doc->name_s,
						'qty' => 'x', //$doc->,
						'price' => $doc->price_s,
					)
				);*/
				//Zend_Debug::dump($doc->score, $doc->name . ' ' . $doc->authors);
				$this->addItem($product);
			}
			
		} catch (Exception $e) {
			if ( $this->_loadAttempts <= 3 ) $this->loadData($printQuery, $logQuery);
			throw new Exception($e->getMessage());
		}
		
		$this->_setIsLoaded();
		$this->_totalRecords = $this->_result->getNumFound();
		return $this;
	}

	/**
	 * Attributes to be filtered
	 * @var array
	 */
	protected $_filterAttributes = array();

	/**
	 * Add attribute filter to collection
	 * @param string $attribute
	 * @param null|string $condition
	 * @return Mage_Eav_Model_Entity_Collection_Abstract
	 */
	public function addAttributeToFilter($attribute, $condition)
	{
		if (is_string($attribute)) {
			$this->_filterAttributes[$attribute] = $this->getSolrCondition($condition);
		} else {
			Mage::throwException('Invalid attribute identifier for filter ('.get_class($attribute).')');
		}
		return $this;
	}
	
	/**
	 * Wrapper for compatibility with Varien_Data_Collection_Db
	 *
	 * @param mixed $attribute
	 * @param mixed $condition
	 */
	public function addFieldToFilter($field, $condition = null)
	{
		$this->addAttributeToFilter($field, $condition);
		return $this;
	}
	
	public function getSolrCondition($condition)
	{
		if (is_array($condition)) {
//	 		if (isset($condition['field_expr'])) {
//	 			$fieldName = str_replace('#?', $this->quoteIdentifier($fieldName), $condition['field_expr']);
//	 			unset($condition['field_expr']);
//	 		}
//	 		$key = key(array_intersect_key($condition, $conditionKeyMap));

			if ( isset($condition['like']) ) {
				$query = (string) $condition['like'];
				$query = str_replace('%', '*', $query);
				$query = str_replace("'", '', $query);
			} elseif (isset($condition['from']) || isset($condition['to'])) {
				if (isset($condition['from'])) $from  = $condition['from'];
				else $from  = '*';
				if (isset($condition['to'])) $to = $condition['to'];
				else $to  = '*';
				$query = "[$from TO $to]";
			} elseif ( isset($condition['eq']) ) {
				$query = (string) $condition['eq'];
			} else {
				$query = '(' . implode(' OR ', $condition) . ')';
			}
			/*  elseif (array_key_exists($key, $conditionKeyMap)) {
				$value = $condition[$key];
				if (($key == 'seq') || ($key == 'sneq')) {
					$key = $this->_transformStringSqlCondition($key, $value);
				}
				$query = $this->_prepareQuotedSqlCondition($conditionKeyMap[$key], $value, $fieldName);
			} else {
				$queries = array();
				foreach ($condition as $orCondition) {
					$queries[] = sprintf('(%s)', $this->prepareSqlCondition($fieldName, $orCondition));
				}
		
				$query = sprintf('(%s)', implode(' OR ', $queries));
			}*/
		} else {
			$query = (string) $condition;
		}
		return $query;
	}

	/**
	 * Set collection page start and records to show
	 *
	 * @param integer $pageNum
	 * @param integer $pageSize
	 * @return Mage_Eav_Model_Entity_Collection_Abstract
	 */
	public function setPage($pageNum, $pageSize)
	{
		$this->setCurPage($pageNum)
			->setPageSize($pageSize);
		return $this;
	}
	
	protected $_priceFilterModel = null;
	
	public function setPriceFilterModel(SScholl_SolrCatalog_Model_Catalog_Layer_Filter_Price $priceFilterModel)
	{
		$this->_priceFilterModel = $priceFilterModel;
		return $this;
	}
	
	public function getPriceFilterModel()
	{
		if ( !$this->_priceFilterModel instanceof SScholl_SolrCatalog_Model_Catalog_Layer_Filter_Price ) {
			$this->_priceFilterModel = Mage::getModel('catalog/layer_filter_price');
		}
		return $this->_priceFilterModel;
	}
	
	protected $_attributefilterModels = array();
	
	public function addAttributeFilterModel(SScholl_SolrCatalog_Model_Catalog_Layer_Filter_Abstract $filterModel)
	{
		$this->_attributefilterModels[] = $filterModel;
		return $this;
	}
	
	public function getAttributeFilterModels()
	{
		return $this->_attributefilterModels;
	}
	
	public function addCountToCategories(Mage_Catalog_Model_Resource_Category_Collection $collection)
	{
		$facet = $this->_result()->getFacetSet()->getFacet('category_name')->getValues();
		foreach ($collection as $category) {
			$query = $category->getData('sschollsolrcatalog_category_query');
			if ( isset($facet[$query]) ) {
				$category->setProductCount($facet[$query]);
			}
		}
		return $this;
	}
	
	protected function _initSelect()
	{
		return $this;
	}
	
	protected $_allFieldsOnSelect = false;
	
	public function addAllFieldsToSelect()
	{
		$this->_allFieldsOnSelect = true;
		return $this;
	}
	
	public function addAttributeToSelect($attributes)
	{
		return $this;
	}
	
	public function addCategoryFilter($category)
	{
		$filter = $category->getData('sschollsolrcatalog_category_query');
		if ( strpos($filter, ' ') ) $filter = '"' . $filter . '"';
		$this->addFieldToFilter('category_name', $filter);
	}
	
	public function addMinimalPrice()
	{
		return $this;
	}
	
	public function addFinalPrice()
	{
		if ( $this->isPriceStatsNeeded() ) $this->_stats['price'] = true;
		return $this;
	}
	
	public function addTaxPercents()
	{
		return $this;
	}
	
	public function addPriceData($customerGroupId = null, $websiteId = null)
	{
		throw new Exception();
	}
	
	public function addUrlRewrite($categoryId = '')
	{
		return $this;
	}
	
	public function setStore($store)
	{
		return $this;
	}
	
	public function addStoreFilter($store = null)
	{
		return $this;
	}
	
	public function setVisibility($visibility)
	{
		$condition = '(' . implode(' ', $visibility) . ')';
		$this->addFieldToFilter('visibility', $condition);
		return $this;
	}
	
	public function getAttributeFacet($attributeCode)
	{
		return $this->_result()->getFacetSet()->getFacet($attributeCode);
	}
	
	public function getPriceFacet()
	{
		return $this->_result()->getFacetSet()->getFacet('price');
	}
	
	public function getMaxPrice()
	{
		return 250;
		$cache = Mage::getSingleton('core/cache'); 
		$result = $cache->load("SScholl_SolrCatalog_Model_Resource_Product_Collection::getMaxPrice");
		if ( $result === false ) {
			if ( !$this->isLoaded() ) {
				$collection = $this->getFacetOnlyCollection();
				return $collection->getMaxPrice();
			}
			$result = $this->_result()->getStats()->getResult(
				$this->getResource()->getSolrFieldName('price')
			)->getMax();
		}
		$cache->save($result, "SScholl_SolrCatalog_Model_Resource_Product_Collection::getMaxPrice", array(), 140);
		return $result;
	}
	
	public function isPriceStatsNeeded()
	{
		return false;
		$cache = Mage::getSingleton('core/cache'); 
		$result = $cache->load("SScholl_SolrCatalog_Model_Resource_Product_Collection::getMaxPrice");
		if ( $result === false ) return true;
		else return false;
	}
	
	public function getSetIds()
	{
		//return array(9);
		$cache = Mage::getSingleton('core/cache'); 
		$attributeSetIds = $cache->load("SScholl_SolrCatalog_Model_Resource_Product_Collection::getSetIds");
		if ( $attributeSetIds === false ) {
			if ( !$this->isLoaded() ) {
				$collection = $this->getFacetOnlyCollection();
				return $collection->getSetIds();
			}
			$attributeSetIds = array();
			$facet = $this->_result()->getFacetSet()->getFacet('attribute_set_id')->getValues();
			foreach ( $facet as $value => $count ) {
				$attributeSetIds[] = (int) $value;
			}
			$cache->save(serialize($attributeSetIds), "SScholl_SolrCatalog_Model_Resource_Product_Collection::getSetIds", array(), 1400);
		} else {
			$attributeSetIds = unserialize($attributeSetIds);
		}
		return $attributeSetIds;
	}
	
	public function isAttributeFacetNeeded()
	{
		$cache = Mage::getSingleton('core/cache'); 
		$result = $cache->load("SScholl_SolrCatalog_Model_Resource_Product_Collection::getSetIds");
		if ( $result === false ) return true;
		else return false;
	}
	
	public function __call($method, $args)	
	{
		throw new Exception();
		Zend_Debug::dump("__call function :$method");
		Zend_Debug::dump($args);
		return $this;
	}
}