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
class Creativestyle_AmazonPayments_Model_Autoload {

    const SCOPE_FILE_PREFIX = '__';

    static protected $_instance;

    protected $_isIncludePathDefined= null;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_isIncludePathDefined = defined('COMPILER_INCLUDE_PATH');
    }

    /**
     * Singleton pattern implementation
     *
     * @return Varien_Autoload
     */
    static public function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new Creativestyle_AmazonPayments_Model_Autoload();
        }
        return self::$_instance;
    }

    /**
     * Register SPL autoload function
     */
    static public function register()
    {
        spl_autoload_register(array(self::instance(), 'autoload'));
    }

    /**
     * Load class source code
     *
     * @param string $class
     */
    public function autoload($class)
    {
        if (in_array($class, array('Certificate', 'Message',
            'SnsMessageParser', 'VerifySignature', 'IpnNotificationParser',
            'OpenSslVerifySignature', 'SnsMessageValidator',
            'XmlNotificationParser'
        ))) {
            $classArray = array('OffAmazonPaymentsNotifications', 'Impl', $class . '.php');
            if ($this->_isIncludePathDefined) {
                $classFile = COMPILER_INCLUDE_PATH . DIRECTORY_SEPARATOR . implode('_', $classArray);
            } else {
                $classFile = implode(DIRECTORY_SEPARATOR, $classArray);
            }
            return include $classFile;
        } else if ($class == 'OffAmazonPaymentsNotifications_Model_IPNNotificationMetadata') {
            if ($this->_isIncludePathDefined) {
                $classFile = COMPILER_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'OffAmazonPaymentsNotifications_Model_IpnNotificationMetadata.php';
            } else {
                $classFile = 'OffAmazonPaymentsNotifications' . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'IpnNotificationMetadata.php';
            }
            return include $classFile;
        } else if ($class == 'OffAmazonPaymentsNotifications_NotificationImpl') {
            if ($this->_isIncludePathDefined) {
                $classFile = COMPILER_INCLUDE_PATH . DIRECTORY_SEPARATOR . 'OffAmazonPaymentsNotifications_Model_NotificationImpl.php';
            } else {
                $classFile = 'OffAmazonPaymentsNotifications' . DIRECTORY_SEPARATOR . 'Model' . DIRECTORY_SEPARATOR . 'NotificationImpl.php';
            }
            return include $classFile;
        }
    }

}
