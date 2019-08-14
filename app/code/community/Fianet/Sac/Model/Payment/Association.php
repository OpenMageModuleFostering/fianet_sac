<?php

class Fianet_Sac_Model_Payment_Association extends Mage_Core_Model_Abstract
{
	protected function _construct()
	{
		parent::_construct();
		$this->_init('sac/payment_association');
	}
}