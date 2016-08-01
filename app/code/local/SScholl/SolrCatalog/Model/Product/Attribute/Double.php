<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Model_Product_Attribute_Double
	extends SScholl_SolrCatalog_Model_Product_Attribute_Abstract
{
	
	protected $_postfixType = '_dou';
	
	public function beforeSave(&$attributeName, &$attributeValue, $attribute)
	{
		$attributeName = $attributeName . $this->getPostfix($attribute);
		$attributeValue = (double) $attributeValue;
	}
	
	public function afterLoad(&$attributeName, &$attributeValue, $attribute)
	{
		$attributeName = str_replace($this->getPostfix($attribute), '', $attributeName);
	}
	
}