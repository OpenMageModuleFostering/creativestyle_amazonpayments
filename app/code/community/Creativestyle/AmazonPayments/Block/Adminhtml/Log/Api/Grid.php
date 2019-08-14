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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Api_Grid extends Creativestyle_AmazonPayments_Block_Adminhtml_Log_Grid_Abstract {

    protected $_logType = 'api';

    public function __construct() {
        parent::__construct();
        $this->setId('amazonpayments_log_api_grid');
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

        $this->addColumn('call_action', array(
            'header'        => Mage::helper('amazonpayments')->__('Action'),
            'index'         => 'call_action',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('call_url', array(
            'header'        => Mage::helper('amazonpayments')->__('URL'),
            'index'         => 'call_url',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('response_code', array(
            'header'        => Mage::helper('amazonpayments')->__('Response code'),
            'index'         => 'response_code',
            'align'         => 'center',
            'width'         => '50px',
            'filter'        => false,
            'sortable'      => false
        ));

        return parent::_prepareColumns();
    }

}
