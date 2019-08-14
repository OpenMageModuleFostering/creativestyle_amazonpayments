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
abstract class Creativestyle_AmazonPayments_Model_Api_Abstract {

    protected
        $_api = null;

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getConnectionData() {
        return $this->_getConfig()->getConnectionData();
    }

    public function getMerchantId() {
        return $this->_getConfig()->getMerchantValues()->getMerchantId();
    }

    abstract protected function _getApi();
}
