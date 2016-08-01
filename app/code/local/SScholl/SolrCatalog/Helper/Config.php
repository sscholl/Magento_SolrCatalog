<?php /**************** Copyright notice ************************
 *  (c) 2011 Simon Eric Scholl <simon@sdscholl.de>
 *  All rights reserved
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 ***************************************************************/

class SScholl_SolrCatalog_Helper_Config
{
	
	/**
	 * contains the xml path to section
	 * @var string
	 */
	const SECTION = 'sschollsolrcatalog';
	
	/**
	 * contains the xml path to group solr 
	 * @var string
	 */
	const GROUP_GENERAL = '/general';
	
	/**
	 * contains the xml path to field active
	 * @var string
	 */
	const FIELD_ACTIVE = '/search_active';

	/**
	 * returns the configured active flag by solr number
	 * @return bool
	 */
	public function searchActive()
	{
		return (bool) Mage::getStoreConfig(self::SECTION . self::GROUP_GENERAL . self::FIELD_ACTIVE);
	}
	
}