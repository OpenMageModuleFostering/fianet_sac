<?php

class Fianet_Sac_Model_Source_Payment_Type
{
	public function toOptionArray()
	{
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