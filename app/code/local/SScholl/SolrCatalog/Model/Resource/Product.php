<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Resource_Product
	extends Mage_Catalog_Model_Resource_Product
{
	
	private $_solr = null;
	
	/**
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

	/**
	 * Initialize resource
	 */
	public function __construct()
	{
		$this->_construct();
		$this->setType(Mage_Catalog_Model_Product::ENTITY)
			->setConnection('catalog_read');
	}
	
	/**
	 * Load entity's attributes into the object
	 *
	 * @param   Mage_Core_Model_Abstract $object
	 * @param   integer $entityId
	 * @param   array|null $attributes
	 * @return  Mage_Eav_Model_Entity_Abstract
	 */
	public function load($object, $entityId, $attributes = array())
	{
		$select = $this->_solr()->getSelect();
		$select->setRows(1);
		$select->setQuery('id:' . $entityId);
		$select->setFields($this->getQueryFields());
		try {
			$result = $this->_solr()->getSolarium()->select($select);
		} catch (Exception $e) {
			Zend_debug::dump($e->getMessage());
			Zend_debug::dump($e);exit;
			return $this;
		}
		try {
			if (is_null($result) || is_null($result->getResponse())) {
				Zend_Debug::dump($result);exit;
			}
			$documents = $result->getDocuments();
			foreach ($documents as $doc) {
				$object = $this->loadSolrFields($doc, $object);
			}
		} catch (Exception $e) {
			Zend_debug::dump($e->getMessage());
			Zend_debug::dump($e);exit;
			Mage::log($select->getOption('query') . ' throws error: ' . $e->getMessage(), null, 'solr_error.log');
			return $this;
		}
		return $this;
		
		
		
		
		
		
		exit;
		
		Varien_Profiler::start('__EAV_LOAD_MODEL__');
		/**
		 * Load object base row data
		*/
		$select  = $this->_getLoadRowSelect($object, $entityId);
		$row	 = $this->_getReadAdapter()->fetchRow($select);
	
		if (is_array($row)) {
			$object->addData($row);
		} else {
			$object->isObjectNew(true);
		}
	
		if (empty($attributes)) {
			$this->loadAllAttributes($object);
		} else {
			foreach ($attributes as $attrCode) {
				$this->getAttribute($attrCode);
			}
		}
	
		$this->_loadModelAttributes($object);
	
		$object->setOrigData();
		Varien_Profiler::start('__EAV_LOAD_MODEL_AFTER_LOAD__');
	
		$this->_afterLoad($object);
		Varien_Profiler::stop('__EAV_LOAD_MODEL_AFTER_LOAD__');
	
		Varien_Profiler::stop('__EAV_LOAD_MODEL__');
		return $this;
	}
	
	public function loadSolrFields(Solarium_Document_ReadOnly $doc, Varien_Object $object = null)
	{
		if ( is_null($object) ) $object = Mage::getModel('sschollsolrcatalog/product');
		foreach ( $doc->getFields() as $attributeName => $attributeValue ) {
			if ( $attributeValue === '' ) continue;
			if ( (!$attribute = $this->getAttribute($attributeName)) ) continue;
			$backendAttribute = SScholl_SolrCatalog_Model_Product_Attribute_Factory
				::getAttributeModel($attribute);
			if ( $backendAttribute instanceof SScholl_SolrCatalog_Model_Product_Attribute_StringMv ) {
				$object->setData($attributeName . '_all', $attributeValue);
			}
			$backendAttribute->afterLoad($attributeName, $attributeValue, $attribute);
			$object->setData($attributeName, $attributeValue);
		}
		return $object;
	}
	
	public function getQueryFields()
	{
		$this->loadAllAttributes();
		$fields = array('entity_id:id');
		foreach ( $this->getAttributesByCode() as $attributeCode => $attribute ) {
			$backendAttribute = SScholl_SolrCatalog_Model_Product_Attribute_Factory
				::getAttributeModel($attribute);
			$solrFieldName = $attributeCode . $backendAttribute->getPostfix($attribute);
			$fields[] = $attributeCode . ':' . $solrFieldName;
		}
		return $fields;
	}
	
	public function getQueryFieldsProductListing()
	{
		$this->loadAllAttributes();
		$fields = array('entity_id:id');
		foreach ( $this->getAttributesByCode() as $attributeCode => $attribute ) {
			$backendAttribute = SScholl_SolrCatalog_Model_Product_Attribute_Factory
				::getAttributeModel($attribute);
			$solrFieldName = $attributeCode . $backendAttribute->getPostfix($attribute);
			if ( $attribute->getBackendType() == 'static' || $attribute->getUsedInProductListing() ) {
				$fields[] = $attributeCode . ':' . $solrFieldName;
			}
		}
		return $fields;
	}
	
	/**
	 * 
	 * @param string|integer|Mage_Core_Model_Config_Element $attribute
	 * @return string|bool
	 */
	public function getSolrFieldName($attribute)
	{
		if ( is_string($attribute) || is_int($attribute) ) {
			$attribute = $this->getAttribute($attribute);
		}
		if ( $attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute ) {
			$backendAttribute = SScholl_SolrCatalog_Model_Product_Attribute_Factory
				::getAttributeModel($attribute);
			$solrFieldName = $attribute->getAttributeCode() . $backendAttribute->getPostfix($attribute);
			return $solrFieldName;
		} else {
			return false;
		}
	}

	/**
	 * Save entity's attributes into the object's resource
	 *
	 * @param   Varien_Object $object
	 * @return  Mage_Eav_Model_Entity_Abstract
	 */
	public function save(Varien_Object $object)
	{
		$this->_beforeSave($object);
		
		$doc = $this->_update()->createDocument();
		$doc->id = $object->getSku();
		$doc->file_name = $object->getFileName();
		foreach ( $object->getData() as $attributeName => $attributeValue ) {
			if ( $attributeValue === '' ) continue;
			if ( (!$attribute = $this->getAttribute($attributeName)) ) continue;
			$backendAttribute = SScholl_SolrCatalog_Model_Product_Attribute_Factory
				::getAttributeModel($attribute);
			if ( $backendAttribute->isSaveable($attributeName, $attributeValue, $attribute) ) {
				$backendAttribute->beforeSave($attributeName, $attributeValue, $attribute);
				if ( $backendAttribute->isMultivalued() ) {
					foreach ( $attributeValue as $item ) {
						$doc->addField($attributeName, $item);
					}
				} else {
					$doc->setField($attributeName, $attributeValue);
				}
			}
		}
		$this->_update()->addDocument($doc);
		$this->_documents = $this->_documents + 1;
		if ( !$this->getCollectionSave() || $this->_documents > 1000 ) {
			$this->_commitUpdate();
		}
		
		return $this;
		/*
		if ($object->isDeleted()) {
			return $this->delete($object);
		}
	
		if (!$this->isPartialSave()) {
			$this->loadAllAttributes($object);
		}
	
		if (!$object->getEntityTypeId()) {
			$object->setEntityTypeId($this->getTypeId());
		}
	
		$object->setParentId((int) $object->getParentId());
	
		$this->_beforeSave($object);
		$this->_processSaveData($this->_collectSaveData($object));
		$this->_afterSave($object);
	
		return $this;*/
	}
	
	protected $_documents = 0;
	
	protected $_collectionSave = false;
	
	public function getCollectionSave()
	{
		return $this->_collectionSave;
	}
	
	public function setCollectionSave($set)
	{
		$this->_collectionSave = $set;
	}
	
	public function saveCollection()
	{
		$this->_commitUpdate();
	}
	
	protected $_update = null;
	
	protected function _update()
	{
		if ( is_null($this->_update) ) {
			$this->_update = $this->_solr()->getSolarium()->createUpdate();
		}
		return $this->_update;
	}
	
	protected function _commitUpdate()
	{
		try {
			$this->_update()->addCommit();
			$response = $this->_solr()->getSolarium()->update($this->_update());
			
			$responseCode = $response->getResponse()->getStatusCode();
			if ($responseCode === '200') {
				$this->_update = null;
				$this->_documents = 0;
			} else {
				throw new Exception(' connection error: ' . $responseCode . ' Response: ' . serialize($response));
			}
		} catch (Exception $e) {
			Zend_debug::dump($update);
			Zend_debug::dump($e->getMessage());
			//Zend_debug::dump($e);
			throw new Exception('connection error: ' . $e->getMessage());
		}
	}

	/**
	 * Delete entity using current object's data
	 *
	 * @return Mage_Eav_Model_Entity_Abstract
	 */
	public function delete($object)
	{
		Zend_Debug::dump("SScholl_SolrCatalog_Model_Resource_Product");
		Zend_Debug::dump("save");
		Zend_Debug::dump($object);
		
		exit;
		
		if (is_numeric($object)) {
			$id = (int)$object;
		} elseif ($object instanceof Varien_Object) {
			$id = (int)$object->getId();
		}

		$this->_beforeDelete($object);

		try {
			$where = array(
				$this->getEntityIdField() . '=?' => $id
			);
			$this->_getWriteAdapter()->delete($this->getEntityTable(), $where);
			$this->loadAllAttributes($object);
			foreach ($this->getAttributesByTable() as $table => $attributes) {
				$this->_getWriteAdapter()->delete($table, $where);
			}
		} catch (Exception $e) {
			throw $e;
		}

		$this->_afterDelete($object);
		return $this;
	}
	
	

	/**
	 * Process product data before save
	 *
	 * @param Varien_Object $object
	 * @return Mage_Catalog_Model_Resource_Product
	 */
	protected function _beforeSave(Varien_Object $object)
	{
		$attributeCode = 'created_at';
		if ($object->isObjectNew() && is_null($object->getData($attributeCode))) {
			$object->setData($attributeCode, Varien_Date::now());
		}
		$object->setData('updated_at', Varien_Date::now());
		return $this;
	}
	
	public function getWebsiteIds($product)
	{
		return $product->getData('website_ids');
	}
	
	public function getWebsiteIdsByProductIds($productIds)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getWebsiteIdsByProductIds
		");
	}
	
	public function getCategoryIds($product)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getCategoryIds
		");
	}
	
	public function getIdBySku($sku)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getIdBySku
		");
	}
	
	public function refreshIndex($product)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			refreshIndex
		");
	}
	
	public function refreshEnabledIndex($store = null, $product = null)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			refreshEnabledIndex
		");
	}
	
	public function getCategoryCollection($product)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getCategoryCollection
		");
	}
	
	public function getAvailableInCategories($object)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getAvailableInCategories
		");
	}
	
	public function getDefaultAttributeSourceModel()
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getDefaultAttributeSourceModel
		");
	}
	
	public function canBeShowInCategory($product, $categoryId)
	{
		$category = Mage::getModel('catalog/category')->load($categoryId);
		if ( in_array($category->getData('sschollsolrcatalog_category_query'), $product->getCategoryNameAll()) ) {
			return true;
		} else {
			return false;
		}
	}
	
	public function duplicate($oldId, $newId)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			duplicate
		");
	}
	
	public function getProductsSku(array $productIds)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getProductsSku
		");
	}
	
	public function getParentProductIds($object)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			depecated function getParentProductIds
		");
	}
	
	public function getProductEntitiesInfo($columns = null)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getProductEntitiesInfo
		");
	}
	
	public function getAssignedImages($product, $storeIds)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getAssignedImages
		");
	}
	
	public function getAttributeRawValue($entityId, $attribute, $store)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getAttributeRawValue
		");
	}
	
	public function getEntityTable()
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getEntityTable
		");
	}

	public function getValueTablePrefix()
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getValueTablePrefix
		");
	}

	public function getEntityTablePrefix()
	{
		return parent::getEntityTablePrefix();
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			getEntityTablePrefix
		");
	}

	public function checkAttributeUniqueValue(Mage_Eav_Model_Entity_Attribute_Abstract $attribute, $object)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			checkAttributeUniqueValue
		");
	}
	
	public function saveAttribute(Varien_Object $object, $attributeCode)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			saveAttribute
		");
	}
	
	public function walkAttributes($partMethod, array $args = array())
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			walkAttributes
		");
	}

	protected function _saveWebsiteIds($product)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			_saveWebsiteIds
		");
	}

	protected function _saveCategories(Varien_Object $object)
	{
		throw new Exception("
			SScholl_SolrCatalog_Model_Resource_Product
			_saveCategories
		");
	}
	
}