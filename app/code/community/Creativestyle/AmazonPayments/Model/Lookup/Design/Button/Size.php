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
class Creativestyle_AmazonPayments_Model_Lookup_Design_Button_Size extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const SIZE_MEDIUM   = 'medium';
    const SIZE_LARGE    = 'large';
    const SIZE_XLARGE   = 'x-large';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::SIZE_MEDIUM, 'label' => Mage::helper('amazonpayments')->__('Medium (126 x 24 pixels)')),
                array('value' => self::SIZE_LARGE, 'label' => Mage::helper('amazonpayments')->__('Large (151 x 27 pixels)')),
                array('value' => self::SIZE_XLARGE, 'label' => Mage::helper('amazonpayments')->__('X-Large (173 x 27 pixels)'))
            );
        }
        return $this->_options;
    }
}
