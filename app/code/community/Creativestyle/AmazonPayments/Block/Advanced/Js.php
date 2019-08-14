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
class Creativestyle_AmazonPayments_Block_Advanced_Js extends Creativestyle_AmazonPayments_Block_Advanced_Abstract {

    /**
     * 
     */
    protected $_addressBookWidgetSize = null;
    protected $_walletWidgetSize = null;

    protected $_widgetHtmlId = 'buttonWidget';

    protected $_idSuffixes = null;

    public function addIdSuffix($suffix) {
        if (null === $this->_idSuffixes) {
            $this->_idSuffixes = array();
        }
        $this->_idSuffixes[] = $suffix;
    }

    public function getAddressBookWidgetSize() {
        if (null === $this->_addressBookWidgetSize) {
            $this->_addressBookWidgetSize = $this->_getConfig()->getAddressBookWidgetSize();
        }
        return $this->_addressBookWidgetSize;
    }

    public function getWalletWidgetSize() {
        if (null === $this->_walletWidgetSize) {
            $this->_walletWidgetSize = $this->_getConfig()->getWalletWidgetSize();
        }
        return $this->_walletWidgetSize;
    }

    public function getQuoteBaseGrandTotal() {
        return (float)$this->_getQuote()->getBaseGrandTotal();
    }

    public function getWidgetHtmlIds($asJson = false) {
        $ids = array();
        if (!empty($this->_idSuffixes)) {
            foreach ($this->_idSuffixes as $suffix) {
                $ids[] = $this->_widgetHtmlId . ucfirst($suffix);
            }
        }
        if ($asJson) return Mage::helper('core')->jsonEncode($ids);
        return $ids;
    }

    public function isVirtual() {
        return $this->_getQuote()->isVirtual();
    }

}
