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
 * @copyright  Copyright (c) 2016 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template {

    /**
     * Store views collection
     */
    protected $_storeCollection = null;

    /**
     * Returns store views collection
     *
     * @return Mage_Core_Model_Resource_Store_Collection
     */
    protected function _getStoreCollection() {
        if (null === $this->_storeCollection) {
            $this->_storeCollection = Mage::getModel('core/store')->getCollection()->load();
        }
        return $this->_storeCollection;
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    public function isLegacyAccount() {
        foreach ($this->_getStoreCollection() as $store) {
            $active = $this->_getConfig()->isActive($store);
            $merchantId = $this->_getConfig()->getMerchantId($store);
            $clientId = $this->_getConfig()->getClientId($store);
            if ($active && $merchantId && !$clientId) {
                return true;
            }
        }
        return false;
    }

}
