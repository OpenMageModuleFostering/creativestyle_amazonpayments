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
class Creativestyle_AmazonPayments_Model_Config
{
    const XML_PATH_ACCOUNT_MERCHANT_ID          = 'amazonpayments/account/merchant_id';
    const XML_PATH_ACCOUNT_ACCESS_KEY           = 'amazonpayments/account/access_key';
    const XML_PATH_ACCOUNT_SECRET_KEY           = 'amazonpayments/account/secret_key';
    const XML_PATH_ACCOUNT_REGION               = 'amazonpayments/account/region';

    const XML_PATH_GENERAL_ACTIVE               = 'amazonpayments/general/active';
    const XML_PATH_GENERAL_SANDBOX              = 'amazonpayments/general/sandbox';
    const XML_PATH_GENERAL_SANDBOX_TOOLBOX      = 'amazonpayments/general/sandbox_toolbox';
    const XML_PATH_GENERAL_PAYMENT_ACTION       = 'amazonpayments/general/payment_action';
    const XML_PATH_GENERAL_AUTHORIZATION_MODE   = 'amazonpayments/general/authorization_mode';
    const XML_PATH_GENERAL_IPN_ACTIVE           = 'amazonpayments/general/ipn_active';
    const XML_PATH_GENERAL_NEW_ORDER_STATUS     = 'amazonpayments/general/new_order_status';
    const XML_PATH_GENERAL_ORDER_STATUS         = 'amazonpayments/general/authorized_order_status';
    const XML_PATH_GENERAL_RECENT_POLLED_TXN    = 'amazonpayments/general/recent_polled_transaction';

    const XML_PATH_LOGIN_ACTIVE                 = 'amazonpayments/general/login_active';
    const XML_PATH_LOGIN_CLIENT_ID              = 'amazonpayments/account/client_id';
    const XML_PATH_LOGIN_LANGUAGE               = 'amazonpayments/general/language';
    const XML_PATH_LOGIN_AUTHENTICATION         = 'amazonpayments/general/authentication';

    const XML_PATH_STORE_NAME                   = 'amazonpayments/store/name';

    const XML_PATH_EMAIL_ORDER_CONFIRMATION     = 'amazonpayments/email/order_confirmation';
    const XML_PATH_EMAIL_DECLINED_TEMPLATE      = 'amazonpayments/email/authorization_declined_template';
    const XML_PATH_EMAIL_DECLINED_IDENTITY      = 'amazonpayments/email/authorization_declined_identity';

    const XML_PATH_DESIGN_BUTTON_SIZE           = 'amazonpayments/design_pay/button_size';
    const XML_PATH_DESIGN_BUTTON_COLOR          = 'amazonpayments/design_pay/button_color';

    const XML_PATH_DESIGN_RESPONSIVE            = 'amazonpayments/design/responsive';
    const XML_PATH_DESIGN_ADDRESS_WIDTH         = 'amazonpayments/design/address_width';
    const XML_PATH_DESIGN_ADDRESS_HEIGHT        = 'amazonpayments/design/address_height';
    const XML_PATH_DESIGN_PAYMENT_WIDTH         = 'amazonpayments/design/payment_width';
    const XML_PATH_DESIGN_PAYMENT_HEIGHT        = 'amazonpayments/design/payment_height';

    const XML_PATH_DESIGN_LOGIN_BUTTON_TYPE     = 'amazonpayments/design_login/login_button_type';
    const XML_PATH_DESIGN_LOGIN_BUTTON_SIZE     = 'amazonpayments/design_login/login_button_size';
    const XML_PATH_DESIGN_LOGIN_BUTTON_COLOR    = 'amazonpayments/design_login/login_button_color';
    const XML_PATH_DESIGN_PAY_BUTTON_TYPE       = 'amazonpayments/design_login/pay_button_type';
    const XML_PATH_DESIGN_PAY_BUTTON_SIZE       = 'amazonpayments/design_login/pay_button_size';
    const XML_PATH_DESIGN_PAY_BUTTON_COLOR      = 'amazonpayments/design_login/pay_button_color';

    const XML_PATH_DEVELOPER_ALLOWED_IPS        = 'amazonpayments/developer/allowed_ips';
    const XML_PATH_DEVELOPER_LOG_ACTIVE         = 'amazonpayments/developer/log_active';

    /**
     * Global config data array
     *
     * @var array|null
     */
    protected $_globalConfigData = null;

    /**
     * Returns configured authentication experience
     *
     * @param mixed|null $store
     * @return string
     */
    protected function _getAuthenticationExperience($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_LOGIN_AUTHENTICATION, $store);
    }

    /**
     * Checks whether Amazon Pay is enabled
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isPayActive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_ACTIVE, $store);
    }

    /**
     * Checks whether Login with Amazon is enabled
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isLoginActive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_LOGIN_ACTIVE, $store);
    }

    /**
     * Checks whether IPN is enabled
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isIpnActive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_IPN_ACTIVE, $store);
    }

    /**
     * Checks whether extension runs in sandbox mode
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isSandboxActive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_SANDBOX, $store);
    }

    /**
     * Checks whether simulation toolbox shall be displayed in the checkout
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isSandboxToolboxActive($store = null)
    {
        return $this->isSandboxActive($store)
            && Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_SANDBOX_TOOLBOX, $store);
    }

    /**
     * Checks whether debug logging is enabled
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isLoggingActive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEVELOPER_LOG_ACTIVE, $store);
    }

    /**
     * Returns Merchant ID for the configured Amazon merchant account
     *
     * @param mixed|null $store
     * @return string
     */
    public function getMerchantId($store = null)
    {
        return trim(strtoupper(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_MERCHANT_ID, $store)));
    }

    /**
     * Returns Amazon app client ID
     *
     * @param mixed|null $store
     * @return string
     */
    public function getClientId($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_LOGIN_CLIENT_ID, $store);
    }

    /**
     * Returns merchant's region
     *
     * @param mixed|null $store
     * @return string|null
     */
    public function getRegion($store = null)
    {
        switch (Mage::getStoreConfig(self::XML_PATH_ACCOUNT_REGION, $store)) {
            case 'EUR':
                return 'de';
            case 'GBP':
                return 'uk';
            default:
                return null;
        }
    }

    /**
     * Returns display language for Amazon widgets
     *
     * @param mixed|null $store
     * @return string|null
     */
    public function getDisplayLanguage($store = null)
    {
        $displayLanguage = Mage::getStoreConfig(self::XML_PATH_LOGIN_LANGUAGE, $store);
        if (!$displayLanguage) {
            /** @var Creativestyle_AmazonPayments_Model_Lookup_Language $languageLookupModel */
            $languageLookupModel = Mage::getSingleton('amazonpayments/lookup_language');
            $displayLanguage = $languageLookupModel->getLanguageByLocale();
        }

        return $displayLanguage;
    }

    /**
     * Checks whether authentication experience is set to automatic mode
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isAutoAuthenticationExperience($store = null)
    {
        return $this->_getAuthenticationExperience($store)
            == Creativestyle_AmazonPayments_Model_Lookup_Authentication::AUTO_EXPERIENCE;
    }

    /**
     * Checks whether authentication experience is set to popup
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isPopupAuthenticationExperience($store = null)
    {
        return $this->_getAuthenticationExperience($store)
            == Creativestyle_AmazonPayments_Model_Lookup_Authentication::POPUP_EXPERIENCE;
    }

    /**
     * Checks whether authentication experience is set to redirect
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isRedirectAuthenticationExperience($store = null)
    {
        return $this->_getAuthenticationExperience($store)
            == Creativestyle_AmazonPayments_Model_Lookup_Authentication::REDIRECT_EXPERIENCE;
    }

    /**
     * Returns configured payment action
     *
     * @param mixed|null $store
     * @return string
     */
    public function getPaymentAction($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION, $store);
    }

    /**
     * Checks whether order amount shall be authorized immediately after the order is placed
     *
     * @param mixed|null $store
     * @return bool
     */
    public function authorizeImmediately($store = null)
    {
        return in_array(
            $this->getPaymentAction($store),
            array(
                Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE,
                Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE
            )
        );
    }

    /**
     * Checks whether order amount shall be captured immediately after the order is placed
     *
     * @param mixed|null $store
     * @return bool
     */
    public function captureImmediately($store = null)
    {
        return $this->getPaymentAction($store)
            == Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Checks whether manual authorization is allowed
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isManualAuthorizationAllowed($store = null)
    {
        return $this->getPaymentAction($store)
            == Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_MANUAL;
    }

    /**
     * Checks whether shop is allowed to process the payment
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isPaymentProcessingAllowed($store = null)
    {
        return $this->getPaymentAction($store)
            != Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_ERP;
    }

    /**
     * Returns authorization request mode (sync, async)
     *
     * @param mixed|null $store
     * @return string
     */
    public function getAuthorizationMode($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_AUTHORIZATION_MODE, $store);
    }

    /**
     * Checks if authorization should be requested synchronously
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isAuthorizationSynchronous($store = null)
    {
        return in_array(
            $this->getAuthorizationMode($store),
            array(
                Creativestyle_AmazonPayments_Model_Lookup_AuthorizationMode::AUTO,
                Creativestyle_AmazonPayments_Model_Lookup_AuthorizationMode::SYNCHRONOUS
            )
        );
    }

    /**
     * Checks if authorization should be re-requested asynchronously,
     * after synchronous authorization fails with TransactionTimedOut
     * declined state
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isAuthorizationOmnichronous($store = null)
    {
        return in_array(
            $this->getAuthorizationMode($store),
            array(
                Creativestyle_AmazonPayments_Model_Lookup_AuthorizationMode::AUTO
            )
        );
    }

    /**
     * Checks whether checkout widgets are configured to be
     * displayed in responsive mode

     * @param mixed|null $store
     * @return bool
     */
    public function isResponsive($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DESIGN_RESPONSIVE, $store);
    }

    /**
     * @param mixed|null $store
     * @return string
     */
    public function getPayButtonSize($store = null)
    {
        if ($this->isLoginActive()) {
            return Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_SIZE, $store);
        }

        return Mage::getStoreConfig(self::XML_PATH_DESIGN_BUTTON_SIZE, $store);
    }

    /**
     * @param mixed|null $store
     * @return string
     */
    public function getPayButtonColor($store = null)
    {
        if ($this->isLoginActive()) {
            return Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_COLOR, $store);
        }

        return Mage::getStoreConfig(self::XML_PATH_DESIGN_BUTTON_COLOR, $store);
    }

    /**
     * @param mixed|null $store
     * @return string|null
     */
    public function getPayButtonUrl($store = null)
    {
        if (!$this->isLoginActive()) {
            $buttonUrls = $this->getGlobalConfigData('button_urls');
            $env = $this->isSandboxActive($store) ? 'sandbox' : 'live';
            if (isset($buttonUrls[$this->getRegion($store)][$env])) {
                return sprintf(
                    '%s?sellerId=%s&amp;size=%s&amp;color=%s',
                    $buttonUrls[$this->getRegion($store)][$env],
                    $this->getMerchantId($store),
                    $this->getPayButtonSize($store),
                    $this->getPayButtonColor($store)
                );
            }
        }

        return null;
    }

    /**
     * Returns Amazon Pay button design params
     *
     * @param mixed|null $store
     * @return array
     */
    public function getPayButtonDesign($store = null)
    {
        return array(
            'type' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_TYPE, $store),
            'size' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_SIZE, $store),
            'color' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_COLOR, $store)
        );
    }

    /**
     * Returns Login with Amazon button design params
     *
     * @param mixed|null $store
     * @return array
     */
    public function getLoginButtonDesign($store = null)
    {
        return array(
            'type' => Mage::getStoreConfig(self::XML_PATH_DESIGN_LOGIN_BUTTON_TYPE, $store),
            'size' => Mage::getStoreConfig(self::XML_PATH_DESIGN_LOGIN_BUTTON_SIZE, $store),
            'color' => Mage::getStoreConfig(self::XML_PATH_DESIGN_LOGIN_BUTTON_COLOR, $store)
        );
    }

    /**
     * Returns status for newly created order
     *
     * @param mixed|null $store
     * @return string
     */
    public function getNewOrderStatus($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_NEW_ORDER_STATUS, $store);
    }

    /**
     * Returns status for order with confirmed authorization
     *
     * @param mixed|null $store
     * @return string
     */
    public function getAuthorizedOrderStatus($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_ORDER_STATUS, $store);
    }

    /**
     * Returns status for the order on hold
     *
     * @param mixed|null $store
     * @return string
     */
    public function getHoldedOrderStatus($store = null)
    {
        return Mage::getModel('sales/order_status')
            ->setStore($store)
            ->loadDefaultByState(Mage_Sales_Model_Order::STATE_HOLDED)
            ->getStatus();
    }

    /**
     * Returns e-mail template for declined authorization
     *
     * @param mixed|null $store
     * @return string
     */
    public function getAuthorizationDeclinedEmailTemplate($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_DECLINED_TEMPLATE, $store);
    }

    /**
     * Returns e-mail sender identity for declined authorization
     *
     * @param mixed|null $store
     * @return string
     */
    public function getAuthorizationDeclinedEmailIdentity($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_DECLINED_IDENTITY, $store);
    }

    /**
     * Checks whether current requester IP is allowed to display Amazon widgets
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isCurrentIpAllowed($store = null)
    {
        $allowedIps = trim(Mage::getStoreConfig(self::XML_PATH_DEVELOPER_ALLOWED_IPS, $store), ' ,');
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

    /**
     * Checks whether Amazon widgets are allowed to be shown
     * in the current shop locale
     *
     * @param mixed|null $store
     * @return bool
     */
    public function isCurrentLocaleAllowed($store = null)
    {
        // no locale restriction when Login is enabled
        if ($this->isLoginActive($store)) {
            return true;
        }

        $currentLocale = Mage::app()->getLocale()->getLocaleCode();
        $language = strtolower($currentLocale);
        if (strpos($language, '_') !== 0) {
            $language = substr($language, 0, strpos($language, '_'));
        }

        switch ($this->getRegion($store)) {
            case 'de':
                return ($language == 'de');
            case 'uk':
            case 'us':
                return ($language == 'en');
            default:
                return false;
        }
    }

    /**
     * Returns global config data
     *
     * @param string|null $key
     * @return string|array
     */
    public function getGlobalConfigData($key = null)
    {
        if (null === $this->_globalConfigData) {
            $this->_globalConfigData = Mage::getConfig()->getNode('global/creativestyle/amazonpayments')->asArray();
        }

        if (null !== $key) {
            if (array_key_exists($key, $this->_globalConfigData)) {
                return $this->_globalConfigData[$key];
            }

            return null;
        }

        return $this->_globalConfigData;
    }

    /**
     * Returns path to the custom CA bundle file
     *
     * @return string|null
     */
    public function getCaBundlePath()
    {
        return $this->getGlobalConfigData('ca_bundle');
    }

    /**
     * Returns array of params needed for API connection
     *
     * @param mixed|null $store
     * @return array
     */
    public function getApiConnectionParams($store = null)
    {
        return array(
            'merchantId' => $this->getMerchantId($store),
            'accessKey' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_ACCESS_KEY, $store)),
            'secretKey' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_SECRET_KEY, $store)),
            'applicationName' => 'Creativestyle Amazon Payments Advanced Magento Extension',
            'applicationVersion' => Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version'),
            'region' => $this->getRegion($store),
            'environment' => $this->isSandboxActive($store) ? 'sandbox' : 'live',
            'serviceUrl' => null,
            'widgetUrl' => null,
            'caBundleFile' => $this->getCaBundlePath(),
            'clientId' => null,
            'cnName' => 'sns.amazonaws.com'
        );
    }

    /**
     * Returns Amazon API Merchant Values object
     *
     * @param mixed|null $store
     * @return OffAmazonPaymentsService_MerchantValues
     */
    public function getApiMerchantValues($store = null)
    {
        /** @var OffAmazonPaymentsService_MerchantValuesBuilder $apiMerchantValuesBuilder */
        $apiMerchantValuesBuilder = OffAmazonPaymentsService_MerchantValuesBuilder::create(
            $this->getApiConnectionParams($store)
        );
        return $apiMerchantValuesBuilder->build();
    }

    /**
     * Returns Widgets JS library URL
     *
     * @param mixed|null $store
     * @return string
     */
    public function getWidgetJsUrl($store = null)
    {
        if ($this->isLoginActive($store)) {
            return $this->getApiMerchantValues($store)->getWidgetUrl();
        }

        return str_replace('lpa/', '', $this->getApiMerchantValues($store)->getWidgetUrl());
    }

    /**
     * Returns Login API URL
     *
     * @param mixed|null $store
     * @return string
     */
    public function getLoginApiUrl($store = null)
    {
        $apiUrls = $this->getGlobalConfigData('login_api_urls');
        if (isset($apiUrls[$this->getRegion($store)][$this->isSandboxActive($store) ? 'sandbox' : 'live'])) {
            return $apiUrls[$this->getRegion($store)][$this->isSandboxActive($store) ? 'sandbox' : 'live'];
        }

        return '';
    }

    /**
     * Returns configured store name
     *
     * @param mixed|null $store
     * @return string
     */
    public function getStoreName($store = null)
    {
        $storeName = Mage::getStoreConfig(self::XML_PATH_STORE_NAME, $store);
        $storeName = $storeName
            ? $storeName
            : sprintf(
                '%s (%s)',
                Mage::app()->getStore($store)->getFrontendName(),
                Mage::app()->getStore($store)->getName()
            );
        return $storeName;
    }

    /**
     * Returns entity ID of the recently polled payment transaction
     *
     * @return string|null
     */
    public function getRecentPolledTransaction()
    {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_RECENT_POLLED_TXN);
    }

    /**
     * Sets recently polled payment transaction
     *
     * @param int $txnId
     */
    public function setRecentPolledTransaction($txnId)
    {
        Mage::getConfig()->saveConfig(self::XML_PATH_GENERAL_RECENT_POLLED_TXN, $txnId)->cleanCache();
    }

    /**
     * Returns CSV log fields delimiter character
     *
     * @return string
     */
    public function getLogDelimiter()
    {
        return ';';
    }

    /**
     * Returns CSV log fields enclosure character
     * @return string
     */
    public function getLogEnclosure()
    {
        return '"';
    }

/*

*/
}
