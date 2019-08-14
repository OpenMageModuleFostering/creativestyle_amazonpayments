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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Exception_Grid extends Creativestyle_AmazonPayments_Block_Adminhtml_Log_Grid_Abstract {

    protected $_logType = 'exception';

    public function __construct() {
        parent::__construct();
        $this->setId('amazonpayments_log_exception_grid');
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

        $this->addColumn('exception_message', array(
            'header'        => Mage::helper('amazonpayments')->__('Exception message'),
            'index'         => 'exception_message',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('exception_code', array(
            'header'        => Mage::helper('amazonpayments')->__('Exception code'),
            'index'         => 'exception_code',
            'align'         => 'center',
            'width'         => '50px',
            'filter'        => false,
            'sortable'      => false
        ));

        return parent::_prepareColumns();
    }

}
