<?php

class Fianet_Sac_OrderController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->_redirect('adminhtml/sales_order');
	}
	
	public function getEvaluationAction()
	{
		try
		{
			Mage::getModel('sac/action')->getEvaluation();
		}
		catch (Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}
		$this->_redirect('adminhtml/sales_order');
	}
	
	public function sentSacAction()
	{
		try
		{
			$orderIds = $this->getRequest()->getPost('order_ids', array());
			$countHoldOrder = 0;
			$sender  = Mage::getModel('fianet/fianet_sender');
			
			foreach ($orderIds as $orderId)
			{
				$order = Mage::getModel('sales/order')->load($orderId);
				$payment = $order->getPayment()->getMethod();
				$sent = $order->getData('fianet_sac_sent');
				if (!eregi('receiveandpay', $payment) && $sent != '1')
				{
					$SacOrder = Fianet_Core_Model_Fianet_Order_Sac::GenerateSacOrder($order);
					//Zend_Debug::dump($SacOrder->get_xml());
					$sender->add_order($SacOrder);
				}
			}
			
			$response = $sender->send();
			
			$countHoldOrder = Mage::getModel('sac/action')->ProcessResponse($response);
			
			if ($countHoldOrder > 0)
			{
				$this->_getSession()->addSuccess(Mage::helper('fianet')->__('%s order(s) successfully sent to FIA-NET', $countHoldOrder));
			}
			
		}
		catch (Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}
		$this->_redirect('adminhtml/sales_order');
	}
	
	
	/*
	protected function GenerateSacOrder(Mage_Sales_Model_Order $order)
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
		$configurationData = Mage::getModel('fianet/configuration_value');
		$configurationData->_scope_field = $scope_field;
		$configurationData->setScope($id);
		
		$SacOrder = Mage::getModel('fianet/fianet_order_sac');
		
		$SacOrder->scope_field	= $scope_field;
		$SacOrder->scope_id		= $id;
		
		$billing_address = $order->getBillingAddress();
		$shipping_address = $order->getShippingAddress();
		
		$SacOrder->billing_user->nom = $billing_address->getLastname();
		$SacOrder->billing_user->prenom = $billing_address->getFirstname();
		$SacOrder->billing_user->telhome = eregi_replace("[^0-9]", "", $billing_address->getTelephone());
		$SacOrder->billing_user->telfax = eregi_replace("[^0-9]", "", $billing_address->getFax());
		$SacOrder->billing_user->email = $billing_address->getEmail();
		$SacOrder->billing_user->societe = $billing_address->getCompany();
		
		if (trim($billing_address->getCompany()) != '')
		{
			$SacOrder->billing_user->set_quality_professional();
		}
		
		$SacOrder->billing_adress->rue1 = $billing_address->getStreet(1);
		$SacOrder->billing_adress->rue2 = $billing_address->getStreet(2);
		$SacOrder->billing_adress->cpostal = $billing_address->getPostcode();
		$SacOrder->billing_adress->ville = $billing_address->getCity();
		$SacOrder->billing_adress->pays = $billing_address->getCountry();
		
		if (!$this->compare_billing_and_shipping($billing_address, $shipping_address))
		{
			$SacOrder->delivery_user = Mage::getModel('fianet/fianet_order_user_delivery');
			$SacOrder->delivery_adress = Mage::getModel('fianet/fianet_order_adress_delivery');
			
			$SacOrder->delivery_user->qualite = $SacOrder->billing_user->qualite;
			
			$SacOrder->delivery_user->nom = $shipping_address->getLastname();
			$SacOrder->delivery_user->prenom = $shipping_address->getFirstname();
			$SacOrder->delivery_user->telhome = eregi_replace("[^0-9]", "", $shipping_address->getTelephone());
			$SacOrder->delivery_user->telfax = eregi_replace("[^0-9]", "", $shipping_address->getFax());
			$SacOrder->delivery_user->email = $shipping_address->getEmail();
			$SacOrder->delivery_user->societe = $shipping_address->getCompany();
			
			$SacOrder->delivery_adress->rue1 = $shipping_address->getStreet(1);
			$SacOrder->delivery_adress->rue2 = $shipping_address->getStreet(2);
			$SacOrder->delivery_adress->cpostal = $shipping_address->getPostcode();
			$SacOrder->delivery_adress->ville = $shipping_address->getCity();
			$SacOrder->delivery_adress->pays = $shipping_address->getCountry();
		}
		
		$SacOrder->info_commande->refid = $order->getRealOrderId();
		$SacOrder->info_commande->devise = $order->getBaseCurrencyCode();
		$SacOrder->info_commande->montant = $order->getBaseGrandTotal();
		$SacOrder->info_commande->ip = $order->getRemoteIp();
		$SacOrder->info_commande->timestamp = $order->getCreatedAt();
		
		$SacOrder->info_commande->siteid = $configurationData->load('SAC_SITEID')->Value;
		if ($SacOrder->info_commande->siteid == null)
		{
			$configurationData->setScope(0);
			$SacOrder->info_commande->siteid = $configurationData->load('SAC_SITEID')->Value;
		}
		
		
		$shipping_code = $order->getShippingCarrier()->getAllowedMethods();
		foreach ($shipping_code as $code => $val)
		{
			$shipping_code = $code;
		}
		$shipping = Mage::getModel('fianet/shipping_association')->load($shipping_code);
		$SacOrder->info_commande->transport->type = $shipping->fianet_shipping_type;
		$SacOrder->info_commande->transport->nom = $shipping->conveyor_name;
		$SacOrder->info_commande->transport->rapidite = $shipping->delivery_times ;
		
		foreach($order->getItemsCollection() as $item)
		{
			$product = Mage::getModel('fianet/fianet_order_info_productList_product');
			
			$product->type = Mage::getModel('fianet/product')->load($item->getProduct_id())->getFianetProductType();
			$product->prixunit = $item->getPrice();
			$product->name = $item->getName();
			$product->nb = (int)$item->getQtyOrdered();
			$product->ref = $item->getProduct_id();
			$SacOrder->info_commande->list->add_product($product);
		}
		$SacOrder->payment->type = Mage::getModel('sac/payment_association')->load($order->getPayment()->getMethod())->getValue();
		
		//Zend_Debug::dump($SacOrder);
		return ($SacOrder);
	}
	
	protected function compare_billing_and_shipping($billing, $shipping)
	{
		$identical = true;
		if ($billing->getLastname() != $shipping->getLastname())
		{
			$identical = false;
		}
		if ($billing->getFirstname() != $shipping->getFirstname())
		{
			$identical = false;
		}
		if ($billing->getTelephone() != $shipping->getTelephone())
		{
			$identical = false;
		}
		if ($billing->getFax() != $shipping->getFax())
		{
			$identical = false;
		}
		if ($billing->getEmail() != $shipping->getEmail())
		{
			$identical = false;
		}
		if ($billing->getStreet(1) != $shipping->getStreet(1))
		{
			$identical = false;
		}
		if ($billing->getStreet(2) != $shipping->getStreet(2))
		{
			$identical = false;
		}
		if ($billing->getPostcode() != $shipping->getPostcode())
		{
			$identical = false;
		}
		if ($billing->getCity() != $shipping->getCity())
		{
			$identical = false;
		}
		if ($billing->getCountry() != $shipping->getCountry())
		{
			$identical = false;
		}
		if ($billing->getCompany() != $shipping->getCompany())
		{
			$identical = false;
		}
		
		return ($identical);
	}
	*/
}