<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog category helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class SScholl_SolrCatalog_Helper_Catalog_Product_View extends Mage_Catalog_Helper_Product_View
{

    /**
     * Prepares product view page - inits layout and all needed stuff
     *
     * $params can have all values as $params in Mage_Catalog_Helper_Product - initProduct().
     * Plus following keys:
     *   - 'buy_request' - Varien_Object holding buyRequest to configure product
     *   - 'specify_options' - boolean, whether to show 'Specify options' message
     *   - 'configure_mode' - boolean, whether we're in Configure-mode to edit product configuration
     *
     * @param int $productId
     * @param Mage_Core_Controller_Front_Action $controller
     * @param null|Varien_Object $params
     *
     * @return Mage_Catalog_Helper_Product_View
     */
    public function prepareAndRender($productId, $controller, $params = null)
    {
        // Prepare data
        $productHelper = Mage::helper('catalog/product');
        if (!$params) {
            $params = new Varien_Object();
        }

        // Standard algorithm to prepare and rendern product view page
        $product = $productHelper->initProduct($productId, $controller, $params);
        if (!$product) {
            throw new Mage_Core_Exception($this->__('Product is not loaded'), $this->ERR_NO_PRODUCT_LOADED);
        }

        $buyRequest = $params->getBuyRequest();
        if ($buyRequest) {
            $productHelper->prepareProductOptions($product, $buyRequest);
        }

        if ($params->hasConfigureMode()) {
            $product->setConfigureMode($params->getConfigureMode());
        }

        /* REMOVED EVENT */
        if ( !$product instanceof SScholl_SolrCatalog_Model_Product ) {
        	Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));
    	}

        if ($params->getSpecifyOptions()) {
            $notice = $product->getTypeInstance(true)->getSpecifyOptionMessage();
            Mage::getSingleton('catalog/session')->addNotice($notice);
        }

        Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());

        $this->initProductLayout($product, $controller);

        $controller->initLayoutMessages(array('catalog/session', 'tag/session', 'checkout/session'))
            ->renderLayout();

        return $this;
    }
}
