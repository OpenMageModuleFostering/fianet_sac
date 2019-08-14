<?php

class Fianet_Sac_Model_Action
{
	public function auto_send_order($observer)
	{
		try
		{
			$event = $observer->getEvent();
			//Mage::getModel('fianet/log')->log('success : ' .Mage::getSingleton('checkout/session')->getLastRealOrderId());
			$order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
			if (eregi('receiveandpay', $order->getPayment()->getMethod()))
			{
				return $this;
			}
			$config = $this->GetConfigurationData($order);
			
			$statut = $config->load('SAC_STATUS')->getValue();
			if ($statut == null || $statut == '')
			{
				$config->setScope(0);
				$statut = $config->load('SAC_STATUS')->getValue();
			}
			if ($statut == '1' || $statut == '2')
			{
				$payment_type = Mage::getModel('sac/payment_association')->load($order->getPayment()->getMethod())->getValue();
				if ($payment_type == 'carte' || $payment_type == 'cb en n fois')
				{
					$message = "SAC : automatic sending order #%s, payment type is %s sac status is %s";
					Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__($message, $order->IncrementId, $payment_type, $statut));
					$SacOrder = Fianet_Core_Model_Fianet_Order_Sac::GenerateSacOrder($order);
					$sender  = Mage::getModel('fianet/fianet_sender');
					$sender->add_order($SacOrder);
					$response = $sender->send();
					//Mage::getModel('fianet/log')->log($response);
					$this->ProcessResponse($response, $order);
				}
			}
		}
		catch(Exception $e)
		{
			Mage::getModel('fianet/log')->log($e->getMessage());
		}
		return $this;
	}
	
	public function ProcessResponse($responses, Mage_Sales_Model_Order $order = null)
	{
		//Zend_Debug::dump($responses);
		$nb = 0;
		foreach ($responses as $response)
		{
			if ($response['etat'] == 'error' || $response['etat'] == 'encours')
			{
				if ($order == null)
				{
					$order = Mage::getModel('sales/order')->loadByIncrementId($response['refid']);
				}
				$config = $this->GetConfigurationData($order);
			
				$statut = $config->load('SAC_STATUS')->getValue();
				if ($statut == null || $statut == '')
				{
					$config->setScope(0);
					$statut = $config->load('SAC_STATUS')->getValue();
				}
				//Mage::getModel('fianet/log')->log('Commande #'.$order->IncrementId.' envoyée statut = '.$statut);
				if ($statut == '2')
				{
					$statut = 'PRODUCTION';
				}
				else
				{
					$statut = 'TEST';
				}
				$order->setData('fianet_sac_sent', 1);
				$order->setData('fianet_sac_mode', $statut);
				if ($response['etat'] == 'error')
				{
					$order->setData('fianet_sac_evaluation', 'error');
				}
				$order->save();
				$nb++;
			}
		}
		return ($nb);
	}
	
	protected function GetConfigurationData(Mage_Sales_Model_Order $order)
	{
		$scope_field = Mage::getModel('fianet/configuration')->getGlobalValue('CONFIGURATION_SCOPE');
		switch ($scope_field)
		{
			case ('store_id'):
				$id = $order->getStore()->getId();
				break;
			case ('group_id'):
				$id = $order->getStore()->getGroup()->getId();
				break;
			case ('website_id'):
				$id = $order->getStore()->getWebsite()->getId();
				break;
			default:
				$id = $order->getStore()->getGroup()->getId();
				break;
		}
		//Mage::getModel('fianet/log')->log('Config scope = ' . $scope_field. ', id = '. $id);
		$configurationData = Mage::getModel('fianet/configuration_value');
		$configurationData->_scope_field = $scope_field;
		$configurationData->setScope($id);
		
		return ($configurationData);
	}
	
	public function getEvaluation()
	{
		$nb = 0;
		$collection = Mage::getResourceModel('sales/order_collection')
			->addAttributeToSelect('fianet_sac_sent')
			->addAttributeToSelect('fianet_sac_evaluation')
			->addAttributeToSelect('fianet_sac_mode')
			->addAttributeToFilter('fianet_sac_sent', 1)
			->load();
		$order_list = array();
		foreach ($collection as $order)
		{
			//Zend_Debug::dump($order->IncrementId);
			//Zend_Debug::dump($order->Fianet_sac_evaluation);	
			if ($order->Fianet_sac_evaluation == null || $order->Fianet_sac_evaluation == 'error'|| $order->Fianet_sac_evaluation == 'encours')
			{
				$config = $this->GetConfigurationData($order);
				
				$siteid = $config->load('SAC_SITEID')->Value;
				$login = $config->load('SAC_LOGIN')->Value;
				$pwd = $config->load('SAC_PASSWORD')->Value;
				
				if ($siteid == null)
				{
					$config->setScope(0);
					$siteid = $config->load('SAC_SITEID')->Value;
					$login = $config->load('SAC_LOGIN')->Value;
					$pwd = $config->load('SAC_PASSWORD')->Value;
				}
				
				$order_list[$siteid]['login']	= $login;
				$order_list[$siteid]['pwd']		= $pwd;
				$order_list[$siteid]['mode']	= $order->Fianet_sac_mode;
				$order_list[$siteid]['orders'][]= $order->IncrementId;
			}
		}
		//Zend_Debug::dump($order_list);
		$evaluations = Mage::getModel('fianet/fianet_sender')->get_evaluations($order_list);
		//Zend_Debug::dump($evaluations);
		foreach ($evaluations as $evaluation)
		{
			if (isset($evaluation['eval']))
			{
				$order = Mage::getModel('sales/order')->loadByIncrementId($evaluation['refid']);
				$order->setData('fianet_sac_evaluation', $evaluation['eval']);
				$order->save();
				$nb++;
			}
		}
		return ($nb);
	}
	
	public function GetReevaluation()
	{
		$nb = 0;
		$reevaluations = Mage::getModel('fianet/fianet_sender')->get_reevaluation();
		//Zend_Debug::dump($evaluations);
		foreach ($reevaluations as $reevaluation)
		{
			if (isset($reevaluation['eval']))
			{
				$notification = Mage::getModel('adminnotification/inbox');
				$notification->setseverity(Mage_AdminNotification_Model_Inbox::SEVERITY_MINOR);
				$notification->setTitle($this->__('Order %s has been reevaluated', $reevaluation['refid']));
				$notification->setDate_added(date('Y-m-d H:i:s'));
				switch ($reevaluation['eval'])
				{
					case('error'):
						$icon = 'attention.gif';
						break;
					case('100'):
						$icon = 'rond_vert.gif';
						break;
					case('-1'):
						$icon = 'rond_vertclair.gif';
						break;
					case('0'):
						$icon = 'rond_rouge.gif';
						break;
					default:
						$icon = 'fianet_SAC_icon.gif';
						break;
				}
				$img = '<img src="'.$this->getSkinUrl('images/fianet/'.$icon).'">';
				$message = Mage::helper('fianet')->__('The order %s has now evaluation %s', $reevaluation['refid'], $img);
				
				$notification->setDescription($message);
				
				
				//Zend_Debug::dump($notification);
				
				$order = Mage::getModel('sales/order')->loadByIncrementId($reevaluation['refid']);
				$order->setData('fianet_sac_reevaluation', $reevaluation['eval']);
				$order->save();
				
				$notification->setUrl($this->getUrlBO($order));
				$notification->save();	
				$nb++;
			}
		}
		return ($nb);
	}
	
	protected function getUrlBO(Mage_Sales_Model_Order $order)
	{
		$url = '';
		$url = $url = Mage::getModel('fianet/configuration')->getGlobalValue('SAC_BASEURL_TEST');
		if ($order->getData('fianet_sac_mode') == 'PRODUCTION')
		{
			$url = $url = Mage::getModel('fianet/configuration')->getGlobalValue('SAC_BASEURL_PRODUCTION');
		}
		$url .= Mage::getModel('fianet/configuration')->getGlobalValue('SAC_URL_BOMERCHANT');
		
		
		$config = $this->GetConfigurationData($order);
		$siteid = $config->load('SAC_SITEID')->Value;
		$login = $config->load('SAC_LOGIN')->Value;
		$password = $config->load('SAC_PASSWORD')->Value;
		
		if ($siteid == null)
		{
			$config->setScope(0);
			$siteid = $config->load('SAC_SITEID')->Value;
			$login = $config->load('SAC_LOGIN')->Value;
			$password = $config->load('SAC_PASSWORD')->Value;
		}
		
		$url .= '?sid='.$siteid.'&log='.$login.'&pwd='.urlencode($password).'&rid='.$order->IncrementId;
		
		return ($url);
	}
	
	protected function getSkinUrl($file)
	{
		return Mage::getDesign()->getSkinUrl($file);
	}
	
	protected function getUrl($path, $params = array())
	{
		return Mage::getModel('core/url')->getUrl($path, $params);
	}
	
	protected function __($message, $param = array())
	{
		return Mage::helper('fianet')->__($message, $param);
	}
}