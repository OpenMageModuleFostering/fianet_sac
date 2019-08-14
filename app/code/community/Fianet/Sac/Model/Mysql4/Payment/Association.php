<?php

class Fianet_Sac_Model_Mysql4_Payment_Association extends Fianet_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('sac/payment_association', 'code');
	}
}