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
class Creativestyle_AmazonPayments_Model_Config {

    const XML_PATH_ACCOUNT_MERCHANT_ID          = 'amazonpayments/account/merchant_id';
    const XML_PATH_ACCOUNT_ACCESS_KEY           = 'amazonpayments/account/access_key';
    const XML_PATH_ACCOUNT_SECRET_KEY           = 'amazonpayments/account/secret_key';
    const XML_PATH_ACCOUNT_REGION               = 'amazonpayments/account/region';

    const XML_PATH_GENERAL_ACTIVE               = 'amazonpayments/general/active';
    const XML_PATH_GENERAL_SANDBOX              = 'amazonpayments/general/sandbox';
    const XML_PATH_GENERAL_SANDBOX_TOOLBOX      = 'amazonpayments/general/sandbox_toolbox';
    const XML_PATH_GENERAL_PAYMENT_ACTION       = 'amazonpayments/general/payment_action';
    const XML_PATH_GENERAL_IPN_ACTIVE           = 'amazonpayments/general/ipn_active';
    const XML_PATH_GENERAL_ORDER_STATUS         = 'amazonpayments/general/authorized_order_status';

    const XML_PATH_EMAIL_ORDER_CONFIRMATION     = 'amazonpayments/email/order_confirmation';
    const XML_PATH_EMAIL_DECLINED_TEMPLATE      = 'amazonpayments/email/authorization_declined_template';
    const XML_PATH_EMAIL_DECLINED_IDENTITY      = 'amazonpayments/email/authorization_declined_identity';

    const XML_PATH_DESIGN_BUTTON_SIZE           = 'amazonpayments/design/button_size';
    const XML_PATH_DESIGN_BUTTON_COLOR          = 'amazonpayments/design/button_color';
    const XML_PATH_DESIGN_ADDRESS_WIDTH         = 'amazonpayments/design/address_width';
    const XML_PATH_DESIGN_ADDRESS_HEIGHT        = 'amazonpayments/design/address_height';
    const XML_PATH_DESIGN_PAYMENT_WIDTH         = 'amazonpayments/design/payment_width';
    const XML_PATH_DESIGN_PAYMENT_HEIGHT        = 'amazonpayments/design/payment_height';

    const XML_PATH_DEVELOPER_ALLOWED_IPS        = 'amazonpayments/developer/allowed_ips';
    const XML_PATH_DEVELOPER_LOG_ACTIVE         = 'amazonpayments/developer/log_active';
    const XML_PATH_DEVELOPER_AVAILABILITY_LOG   = 'amazonpayments/developer/log_availability';

    protected $_config = null;
    protected $_globalData = null;
    protected $_merchantValues = null;

    protected function _getConfig()  {
        if (null === $this->_config) {
            $this->_config = array(
                'merchantId' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_MERCHANT_ID)),
                'accessKey' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_ACCESS_KEY)),
                'secretKey' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_SECRET_KEY)),
                'applicationName' => 'Creativestyle Amazon Payments Advanced Magento Extension',
                'applicationVersion' => Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version'),
                'region' => Mage::getStoreConfig(self::XML_PATH_ACCOUNT_REGION),
                'environment' => Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_SANDBOX) ? 'sandbox' : 'live',
                'serviceURL' => '',
                'widgetURL' => '',
                'caBundleFile' => '',
                'clientId' => ''
            );
        }
        return $this->_config;
    }

    protected function _getGlobalData()  {
        if (null === $this->_globalData) {
            $this->_globalData = Mage::getConfig()->getNode('global/creativestyle/amazonpayments')->asArray();
        }
        return $this->_globalData;
    }

    public function getConnectionData($key = null) {
        if (null !== $key) {
            $config = $this->_getConfig();
            if (array_key_exists($key, $config)) {
                return $config[$key];
            }
            return null;
        }
        return $this->_getConfig();
    }

    public function getGlobalDataValue($key = null) {
        if (null !== $key) {
            $data = $this->_getGlobalData();
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
            return null;
        }
        return $this->_getGlobalData();
    }

    public function getMerchantValues() {
        if (null === $this->_merchantValues) {
            $this->_merchantValues = new OffAmazonPaymentsService_MerchantValues(
                $this->getConnectionData('merchantId'),
                $this->getConnectionData('accessKey'),
                $this->getConnectionData('secretKey'),
                $this->getConnectionData('applicationName'),
                $this->getConnectionData('applicationVersion'),
                $this->getConnectionData('region'),
                $this->getConnectionData('environment'),
                $this->getConnectionData('serviceURL'),
                $this->getConnectionData('widgetURL'),
                $this->getConnectionData('caBundleFile'),
                $this->getConnectionData('clientId')
            );
        }
        return $this->_merchantValues;
    }

    public function showSandboxToolbox() {
        return $this->getConnectionData('environment') == 'sandbox' && Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_SANDBOX_TOOLBOX);
    }

    public function getButtonColor() {
        return Mage::getStoreConfig(self::XML_PATH_DESIGN_BUTTON_COLOR);
    }

    public function getButtonSize() {
        return Mage::getStoreConfig(self::XML_PATH_DESIGN_BUTTON_SIZE);
    }

    public function getAddressBookWidgetSize() {
        return new Varien_Object(array(
            'width' => Mage::getStoreConfig(self::XML_PATH_DESIGN_ADDRESS_WIDTH) . 'px',
            'height' => Mage::getStoreConfig(self::XML_PATH_DESIGN_ADDRESS_HEIGHT) . 'px'
        ));
    }

    public function getWalletWidgetSize() {
        return new Varien_Object(array(
            'width' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAYMENT_WIDTH) . 'px',
            'height' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAYMENT_HEIGHT) . 'px'
        ));
    }

    public function authorizeImmediately() {
        return in_array(Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION), array(
            Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE,
            Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE
        ));
    }

    public function captureImmediately() {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION) == Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE;
    }

    public function isManualAuthorizationAllowed() {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION) == Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_MANUAL;
    }

    public function isPaymentProcessingAllowed() {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION) != Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_ERP;
    }

    public function getAuthorizedOrderStatus() {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_ORDER_STATUS);
    }

    public function sendEmailConfirmation() {
        return Mage::getStoreConfigFlag(self::XML_PATH_EMAIL_ORDER_CONFIRMATION);
    }

    public function getAuthorizationDeclinedEmailTemplate() {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_DECLINED_TEMPLATE);
    }

    public function getAuthorizationDeclinedEmailIdentity() {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_DECLINED_IDENTITY);
    }

    public function getLogDelimiter() {
        return ';';
    }

    public function getLogEnclosure() {
        return '"';
    }

    /**
     * Checks if Pay with Amazon is enabled in the extension settings
     *
     * @return bool
     */
    public function isActive() {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_ACTIVE);
    }

    public function isIpnActive() {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_IPN_ACTIVE);
    }

    public function isCurrentIpAllowed() {
        $allowedIps = trim(Mage::getStoreConfig(self::XML_PATH_DEVELOPER_ALLOWED_IPS), ' ,');
        if ($allowedIps) {
            $allowedIps = explode(',', str_replace(' ', '', $allowedIps));
            if (is_array($allowedIps) && !empty($allowedIps)) {
                $currentIp = Mage::helper('core/http')->getRemoteAddr();
                if (Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR')) {
                    $currentIp = Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR');
                }
                return in_array($currentIp, $allowedIps);
            }
        }
        return true;
    }

    public function isCurrentLocaleAllowed() {
        $currentLocale = Mage::app()->getLocale()->getLocaleCode();
        $language = strtolower($currentLocale);
        if (strpos($language, '_')) {
            $language = substr($language, 0, strpos($language, '_'));
        }
        switch ($this->getConnectionData('region')) {
            case 'de':
                return ($language == 'de');
            case 'uk':
            case 'us':
                return ($language == 'en');
            default:
                return false;
        }
    }

    public function isLogEnabled() {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEVELOPER_LOG_ACTIVE);
    }

    public function isPaymentAvailabilityLogEnabled() {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEVELOPER_AVAILABILITY_LOG);
    }
}
