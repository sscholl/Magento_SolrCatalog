<?php

/**
 * Shopping cart helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class SScholl_SolrCatalog_Helper_Checkout_Cart extends Mage_Checkout_Helper_Cart
{

    /**
     * Retrieve url for add product to cart
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  string
     */
    public function getAddUrl($product, $additional = array(), $paramsOnly = false)
    {
    	if ( !$product instanceof SScholl_SolrCatalog_Model_Product ) {
    		if ( $paramsOnly ) {
    			$continueUrl    = Mage::helper('core')->urlEncode($this->getCurrentUrl());
    			$urlParamName   = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
    			
    			$routeParams = array(
    					$urlParamName   => $continueUrl,
    					'product'       => $product->getEntityId()
    			);
    			
    			if (!empty($additional)) {
    				$routeParams = array_merge($routeParams, $additional);
    			}
    			
    			if ($product->hasUrlDataObject()) {
    				$routeParams['_store'] = $product->getUrlDataObject()->getStoreId();
    				$routeParams['_store_to_url'] = true;
    			}
    			
    			if ($this->_getRequest()->getRouteName() == 'checkout'
    					&& $this->_getRequest()->getControllerName() == 'cart') {
    				$routeParams['in_cart'] = 1;
    			}
    			
    			return $routeParams;
    		} else {
    			return parent::getAddUrl($product, $additional);
    		}
    	}
        $continueUrl    = Mage::helper('core')->urlEncode($this->getCurrentUrl());
        $urlParamName   = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;

        $routeParams = array(
            $urlParamName   => $continueUrl,
            'product'       => $product->getId()
        );

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product->hasUrlDataObject()) {
            $routeParams['_store'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_store_to_url'] = true;
        }

        if ($this->_getRequest()->getRouteName() == 'checkout'
            && $this->_getRequest()->getControllerName() == 'cart') {
            $routeParams['in_cart'] = 1;
        }

        return $this->_getUrl('sschollsolrcatalog/checkout/add', $routeParams);
    }
    
}
