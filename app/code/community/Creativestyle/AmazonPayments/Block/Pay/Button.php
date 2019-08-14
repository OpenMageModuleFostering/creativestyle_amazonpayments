<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2014 - 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2014 - 2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */

/**
 * Amazon Pay button block
 *
 * @method int getEnableOr()
 * @method $this setEnableOr(int $value)
 * @method string getButtonType()
 * @method $this setButtonType(string $value)
 * @method string getButtonSize()
 * @method $this setButtonSize(string $value)
 * @method string getButtonColor()
 * @method $this setButtonColor(string $value)
 */
class Creativestyle_AmazonPayments_Block_Pay_Button extends Creativestyle_AmazonPayments_Block_Pay_Abstract
{
    const WIDGET_CONTAINER_ID_PREFIX = 'payButtonWidget';

    /**
     * @inheritdoc
     */
    protected $_containerIdPrefix = self::WIDGET_CONTAINER_ID_PREFIX;

    /**
     * @inheritdoc
     */
    protected $_containerClass = self::WIDGET_CONTAINER_ID_PREFIX;

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        if (!$this->hasData('template')) {
            $this->setTemplate('creativestyle/amazonpayments/pay/button.phtml');
        }
    }

    public function getButtonWidgetUrl()
    {
        return $this->_getConfig()->getPayButtonUrl();
    }

    public function isCustomDesignSet()
    {
        return $this->hasData('button_type') || $this->hasData('button_size') || $this->hasData('button_color');
    }
}
