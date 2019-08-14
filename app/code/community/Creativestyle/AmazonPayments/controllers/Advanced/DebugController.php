<?php

/**
 * This file is part of the official Amazon Payments Advanced extension
 * for Magento (c) creativestyle GmbH <amazon@creativestyle.de>
 * All rights reserved
 *
 * Reuse or modification of this source code is not allowed
 * without written permission from creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  Copyright (c) 2014 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Advanced_DebugController extends Mage_Core_Controller_Front_Action {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    public function indexAction() {
        if ($this->_getConfig()->getMerchantValues()->getEnvironment() == 'sandbox') {
            $productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToFilter('type_id', 'simple')
                ->setPageSize(1)
                ->setCurPage(1);
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($productCollection);
            $productCollection->getSelect()->order(new Zend_Db_Expr('RAND()'));
            if ($productCollection->count()) {
                $product = $productCollection->getFirstItem();
                $addToCartUrl = Mage::helper('checkout/cart')->getAddUrl($product, array());
                $this->_redirectUrl($addToCartUrl);
                return;
            }
        }
        $this->_forward('noRoute');
    }
}
