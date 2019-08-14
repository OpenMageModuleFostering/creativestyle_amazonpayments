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
 * @copyright  Copyright (c) 2014 - 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Ipn_Grid extends Creativestyle_AmazonPayments_Block_Adminhtml_Log_Grid_Abstract {

    protected $_logType = 'ipn';

    public function __construct() {
        parent::__construct();
        $this->setId('amazonpayments_log_ipn_grid');
        $this->setFilterVisibility(false);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareColumns() {

        $this->addColumn('timestamp', array(
            'header'        => Mage::helper('amazonpayments')->__('Date'),
            'index'         => 'timestamp',
            'type'          => 'datetime',
            'width'         => '150px',
            'renderer'      => 'Creativestyle_AmazonPayments_Block_Adminhtml_Renderer_Timestamp',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('notification_type', array(
            'header'        => Mage::helper('amazonpayments')->__('Notification type'),
            'index'         => 'notification_type',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('response_code', array(
            'header'        => Mage::helper('amazonpayments')->__('Response code'),
            'index'         => 'response_code',
            'align'         => 'center',
            'width'         => '80px',
            'filter'        => false,
            'sortable'      => false
        ));

        return parent::_prepareColumns();
    }

}
