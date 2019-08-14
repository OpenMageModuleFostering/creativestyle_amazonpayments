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
class Creativestyle_AmazonPayments_Block_Adminhtml_Register extends Mage_Adminhtml_Block_Template {

    protected function _construct() {
        $this->setTemplate('creativestyle/amazonpayments/register.phtml');
        return parent::_construct();
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    public function getAccountRegionOptions() {
        return Mage::getSingleton('amazonpayments/lookup_accountRegion')->toOptionArray();
    }

    public function getLanguageOptions() {
        return Mage::getSingleton('amazonpayments/lookup_language')->toOptionArray();
    }

    public function getDefaultAccountRegion() {
        return Mage::getStoreConfig('currency/options/base');
        switch (Mage::getStoreConfig('currency/options/base')) {
            case 'GBP':
                return 'GBP';
            default:
                return 'EUR';
        }
    }

    public function getDefaultLanguage() {
        return Mage::getSingleton('amazonpayments/lookup_language')->getLanguageByLocale(Mage::app()->getLocale()->getLocaleCode(), false);
    }

    public function getState() {
        if ($this->_getConfig()->getMerchantId()) {
            return 0;
        }
        return 1;
    }
}
