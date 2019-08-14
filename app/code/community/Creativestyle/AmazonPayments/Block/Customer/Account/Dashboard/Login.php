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
 * @copyright  Copyright (c) 2017 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Block_Customer_Account_Dashboard_Login extends Creativestyle_AmazonPayments_Block_Abstract {

    protected function _isActive() {
        return $this->_getConfig()->isLoginActive();
    }

    protected function isCustomerConnectedWithAmazon() {
        return (bool)$this->_getCustomerSession()->getCustomer()->getAmazonUserId();
    }
}
