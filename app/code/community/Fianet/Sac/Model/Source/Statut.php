<?php

/**
 * 2000-2012 FIA-NET
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please contact us
 * via http://www.fia-net-group.com/formulaire.php so we can send you a copy immediately.
 *
 *  @author FIA-NET <support-boutique@fia-net.com>
 *  @copyright 2000-2012 FIA-NET
 *  @version Release: $Revision: 1.0.1 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */
class Fianet_Sac_Model_Source_Statut {

    // set null to enable all possible
    protected $_stateStatuses = array(
            /*  Mage_Sales_Model_Order::STATE_NEW,
              //  Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
              Mage_Sales_Model_Order::STATE_PROCESSING,
              Mage_Sales_Model_Order::STATE_COMPLETE,
              Mage_Sales_Model_Order::STATE_CLOSED,
              Mage_Sales_Model_Order::STATE_CANCELED,
              Mage_Sales_Model_Order::STATE_HOLDED, */
    );

    public function toOptionArray() {
        if ($this->_stateStatuses) {
            $statuses = Mage::getSingleton('sales/order_config')->getStateStatuses($this->_stateStatuses);
        } else {
            $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        }
        $options = array();
        $options[] = array(
            'value' => '',
            'label' => Mage::helper('adminhtml')->__('-- Please Select --')
        );
        foreach ($statuses as $code => $label) {
            $options[] = array(
                'value' => $code,
                'label' => $code
            );
        }
        return $options;
    }

}

?>
