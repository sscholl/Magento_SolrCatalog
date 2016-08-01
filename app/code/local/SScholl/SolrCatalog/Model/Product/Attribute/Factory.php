<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Product_Attribute_Factory
{
	
	static protected $_types = array (
		'datetime'	=>	'sschollsolrcatalog/product_attribute_datetime',
		'decimal'	=>	'sschollsolrcatalog/product_attribute_double', 
		'int'		=>	'sschollsolrcatalog/product_attribute_integer', 
		'static'	=>	'sschollsolrcatalog/product_attribute_static',
		'varchar'	=>	'sschollsolrcatalog/product_attribute_string',
		'text'		=>	'sschollsolrcatalog/product_attribute_text',
	);

	protected function __construct() {}

	/**
	 * generates a backend model by datatyp 
	 * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
	 * @return SScholl_SolrCatalog_Model_Product_Attribute_Abstract
	 */
	static public function getAttributeModel(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
	{
		if ( $attribute && isset(self::$_types[$attribute->getBackendType()]) ) {
			try {
				if ( $attribute->getAttributeCode() === 'category_name' ) {
					$postfix = 'Mv';
				}
				$backendAttribute = Mage::getSingleton(
					self::$_types[$attribute->getBackendType()] . $postfix
				);
			} catch ( Exception $e ) {}
		}
		if ( !$backendAttribute instanceof SScholl_SolrCatalog_Model_Product_Attribute_Abstract ) {
			$backendAttribute = Mage::getSingleton(
				'sschollsolrcatalog/product_attribute_string'
			);
		}
		return $backendAttribute;
	}

}