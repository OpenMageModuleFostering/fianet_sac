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
class Fianet_Sac_Model_Source_Type {

    public function toOptionArray() {
        return array(
            array('value' => 'carte', 'label' => Mage::helper('fianet')->__('Credit card')),
            array('value' => 'cheque', 'label' => Mage::helper('fianet')->__('Money order')),
            array('value' => 'contre-remboursement', 'label' => Mage::helper('fianet')->__('Against repayment')),
            array('value' => 'virement', 'label' => Mage::helper('fianet')->__('Transfer')),
            array('value' => 'cb en n fois', 'label' => Mage::helper('fianet')->__('Credit card in x times')),
            array('value' => 'paypal', 'label' => Mage::helper('fianet')->__('Paypal'))
        );
    }

}