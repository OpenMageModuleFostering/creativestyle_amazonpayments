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
abstract class Creativestyle_AmazonPayments_Block_Advanced_Abstract extends Mage_Core_Block_Template {

    protected $_quote = null;

    protected $_widgetHtmlId = null;

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getQuote() {
        if (null === $this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    protected function _isActive() {
        if ($this->_getConfig()->isActive() && $this->_getConfig()->isCurrentIpAllowed() && $this->_getConfig()->isCurrentLocaleAllowed()) {
            $methodInstance = $this->isLive() ? Mage::getModel('amazonpayments/payment_advanced') : Mage::getModel('amazonpayments/payment_advanced_sandbox');
            return $methodInstance->isAvailable($this->_getQuote());
        }
        return false;
    }

    public function getCheckoutUrl() {
        return Mage::getUrl('amazonpayments/advanced_checkout');
    }

    public function getOrderReferenceId() {
        return $this->_getCheckoutSession()->getOrderReferenceId();
    }

    public function getEnvironment() {
        return $this->_getConfig()->getConnectionData('environment');
    }

    public function getMerchantId() {
        return $this->_getConfig()->getConnectionData('merchantId');
    }

    public function getRegion() {
        return $this->_getConfig()->getConnectionData('region');
    }

    public function getWidgetHtmlId() {
        if ($this->getIdSuffix()) {
            return $this->_widgetHtmlId . ucfirst($this->getIdSuffix());
        }
        return $this->_widgetHtmlId;
    }

    public function getWidgetClass() {
        return $this->_widgetHtmlId;
    }

    public function isLive() {
        return $this->getEnvironment() != 'sandbox';
    }

    /**
     * Render Amazon Payments block
     *
     * @return string
     */
    protected function _toHtml() {
        try {
            if ($this->_isActive()) {
                return parent::_toHtml();
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return '';
    }

}
