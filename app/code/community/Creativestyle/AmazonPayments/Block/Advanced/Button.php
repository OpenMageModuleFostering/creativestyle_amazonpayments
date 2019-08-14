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
class Creativestyle_AmazonPayments_Block_Advanced_Button extends Creativestyle_AmazonPayments_Block_Advanced_Abstract {

    protected $_widgetHtmlId = 'buttonWidget';

    public function getButtonWidgetUrl() {
        $buttonUrls = $this->_getConfig()->getGlobalDataValue('button_urls');
        if (isset($buttonUrls[$this->getRegion()][$this->getEnvironment()])) {
            return sprintf($buttonUrls[$this->getRegion()][$this->getEnvironment()] . '?sellerId=%s&amp;size=%s&amp;color=%s',
                $this->getMerchantId(),
                $this->_getConfig()->getButtonSize(),
                $this->_getConfig()->getButtonColor());
        }
        return '';
    }

}
