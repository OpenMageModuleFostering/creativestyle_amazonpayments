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
 * @copyright  Copyright (c) 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Adminhtml_Amazonpayments_DebugController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction($actionMenuItem, $actionBreadcrumbs) {
        $this->_setActiveMenu('creativestyle/amazonpayments/' . $actionMenuItem);
        foreach ($actionBreadcrumbs as $breadcrumb) {
            $this->_addBreadcrumb($this->__($breadcrumb), $this->__($breadcrumb))
                ->_title($breadcrumb);
        }
        return $this;
    }

    public function indexAction() {
        $this->loadLayout()
            ->_initAction('debug', array('Amazon Pay and Login with Amazon', 'Debug data'))
            ->renderLayout();
    }

    public function downloadAction() {
        $debugData = Mage::helper('amazonpayments/debug')->getDebugData();
        $filename = str_replace(array('.', '/', '\\'), array('_'), parse_url(Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL), PHP_URL_HOST)) .
            '_apa_debug_' . Mage::getModel('core/date')->gmtTimestamp() . '.dmp';
        $debugData = base64_encode(serialize($debugData));
        Mage::app()->getResponse()->setHeader('Content-type', 'application/base64');
        Mage::app()->getResponse()->setHeader('Content-disposition', 'attachment;filename=' . $filename);
        Mage::app()->getResponse()->setBody($debugData);
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('admin/creativestyle/amazonpayments/debug');
    }

}
