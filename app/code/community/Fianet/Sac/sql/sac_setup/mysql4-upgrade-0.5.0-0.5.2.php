<?php
$installer = $this;
$installer->startSetup();
/*
$config = array(
		'SAC_BASEURL_PRODUCTION'=>'https://secure.fia-net.com/fscreener/',
		'SAC_BASEURL_TEST'=>'https://secure.fia-net.com/pprod/',
		'SAC_URL_STACKING'=>'engine/stacking.cgi',
		'SAC_URL_VALIDSTACK'=>'engine/get_validstack.cgi',
		'SAC_URL_GETALERT'=>'engine/get_alert.cgi',
		'SAC_URL_BOMERCHANT'=>'commun/visucheck_detail.php',
		'SAC_URL_CHECKXML'=>'marchand/checkxml.php',
		'SAC_STATUS'=>'0',
		'SAC_SITEID'=>'',
		'SAC_LOGIN'=>'',
		'SAC_PASSWORD'=>''
		);
Fianet_Core_Model_Configuration::SetDefaultConfig($config);
*/
$Payments	= Mage::getModel('fianet/MageConfiguration')
				->getPaymentMethods();

foreach ($Payments as $code => $name)
{
	if (!eregi('receiveandpay', $code))
	{
		$val = 'carte';
		if (eregi('checkmo', $code))
		{
			$val = 'cheque';
		}
		if (eregi('paypal', $code))
		{
			$val = 'paypal';
		}
		if (eregi('purchaseorder', $code))
		{
			$val = 'virement';
		}
		if (eregi('free', $code))
		{
			$val = 'virement';
		}
		
		Mage::getModel('sac/payment_association')
			->load($code)
			->setId($code)
			->setValue($val)
			->save();
	}
}

$installer->endSetup();

?>