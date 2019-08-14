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
class Fianet_Sac_Model_Fianet_Order_Sac {

    public $billing_user = null;
    public $billing_address = null;
    public $info_commande = null;
    public $payment = null;
    public $delivery_user = null;
    public $delivery_address = null;
    public $scope_field = null;
    public $scope_id = null;
    protected $version = null;
    protected $encoding = null;

    public function __construct() {
        $this->billing_user = Mage::getModel('fianet/fianet_order_user_billing');
        $this->billing_address = Mage::getModel('fianet/fianet_order_address_billing');
        $this->info_commande = Mage::getModel('sac/fianet_order_info_sac');
        $this->payment = Mage::getModel('fianet/fianet_order_payment');
        $this->version = (string) Mage::getConfig()->getModuleConfig('Fianet_Sac')->version;
        $this->encoding = Mage::getStoreConfig('sac/sacconfg/charset', '0'); // iso/utf8
    }

    public function reset() {
        $this->billing_user = Mage::getModel('fianet/fianet_order_user_billing');
        $this->billing_address = Mage::getModel('fianet/fianet_order_address_billing');
        $this->info_commande = Mage::getModel('sac/fianet_order_info_sac');
        $this->payment = Mage::getModel('fianet/fianet_order_payment');
        $this->delivery_user = null;
        $this->delivery_address = null;
    }

    public function getXml($display_signature = false) {
        $xml = '';
        if ($display_signature) {
            $xml .= '<?xml version="1.0" encoding="' . $this->encoding . '" ?>' . "\n";
        }
        $xml .= '<control fianetmodule="Magento_SAC" version="' . $this->version . '">' . "\n";
        $xml .= $this->billing_user->getXml();
        $xml .= $this->billing_address->getXml();
        if ($this->delivery_user != null) {
            if ($this->delivery_user instanceof Fianet_Core_Model_Fianet_Order_User_Delivery) {
                $xml .= $this->delivery_user->getXml();
            } else {
                Mage::getModel('fianet/log')->Log("Mage_Sac_Model_Fianet_Order_Sac - getXml() <br />\nDelivery user is not an object of type Mage_Sac_Order_User_Delivery");
            }
        }
        if ($this->delivery_address != null) {
            if ($this->delivery_address instanceof Fianet_Core_Model_Fianet_Order_Address_Delivery) {
                $xml .= $this->delivery_address->getXml();
            } else {
                Mage::getModel('fianet/log')->Log("Mage_Sac_Model_Fianet_Order_Sac - getXml() <br />\nDelivery address is not an object of type Mage_Sac_Order_Address_Delivery");
            }
        }
        $xml .= $this->info_commande->getXml();
        $xml .= $this->payment->getXml();
        $xml .= '</control>';

        return ($xml);
    }

    public static function generateSacOrder(Mage_Sales_Model_Order $order) {
        $scope_field = "store_id";
        $id = $order->getStore()->getId();

        $sacOrder = Mage::getModel('sac/fianet_order_sac');

        $sacOrder->scope_field = $scope_field;
        $sacOrder->scope_id = $id;

        $billing_address = $order->getBillingAddress();
        $shipping_address = $order->getShippingAddress();

        $sacOrder->billing_user->titre = $billing_address->getPrefix();
        $sacOrder->billing_user->nom = $billing_address->getLastname();
        $sacOrder->billing_user->prenom = $billing_address->getFirstname();
        $sacOrder->billing_user->telhome = preg_replace("/[^0-9]/", "", $billing_address->getTelephone());
        $sacOrder->billing_user->telfax = preg_replace("/[^0-9]/", "", $billing_address->getFax());
        $sacOrder->billing_user->email = $billing_address->getEmail() == '' ? $order->getCustomer_email() : $billing_address->getEmail();
        $sacOrder->billing_user->societe = $billing_address->getCompany();

        if (trim($billing_address->getCompany()) != '') {
            $sacOrder->billing_user->setQualityProfessional();
        }

        $sacOrder->billing_address->rue1 = $billing_address->getStreet(1);
        $sacOrder->billing_address->rue2 = $billing_address->getStreet(2);
        $sacOrder->billing_address->cpostal = $billing_address->getPostcode();
        $sacOrder->billing_address->ville = $billing_address->getCity();
        $sacOrder->billing_address->pays = (preg_match('/^97/', $billing_address->getPostcode()))? 'FR' : $billing_address->getCountry();

        $shipping_code = $order->getShippingCarrier()->getCarrierCode();
        $shipping = Mage::getModel('fianet/shipping_association')->load($shipping_code);

        if ($shipping->fianet_shipping_type != '1' && $shipping->fianet_shipping_type != '2') {
            if (!Fianet_Core_Model_Functions::compareBillingAndShipping($billing_address, $shipping_address)) {
                $sacOrder->delivery_user = Mage::getModel('fianet/fianet_order_user_delivery');
                $sacOrder->delivery_address = Mage::getModel('fianet/fianet_order_address_delivery');

                $sacOrder->delivery_user->qualite = $sacOrder->billing_user->qualite;

                $sacOrder->delivery_user->titre = $shipping_address->getPrefix();
                $sacOrder->delivery_user->nom = $shipping_address->getLastname();
                $sacOrder->delivery_user->prenom = $shipping_address->getFirstname();
                $sacOrder->delivery_user->telhome = preg_replace("/[^0-9]/", "", $shipping_address->getTelephone());
                $sacOrder->delivery_user->telfax = preg_replace("/[^0-9]/", "", $shipping_address->getFax());
                $sacOrder->delivery_user->email = $shipping_address->getEmail();
                $sacOrder->delivery_user->societe = $shipping_address->getCompany();

                $sacOrder->delivery_address->rue1 = $shipping_address->getStreet(1);
                $sacOrder->delivery_address->rue2 = $shipping_address->getStreet(2);
                $sacOrder->delivery_address->cpostal = $shipping_address->getPostcode();
                $sacOrder->delivery_address->ville = $shipping_address->getCity();
                $sacOrder->delivery_address->pays = (preg_match('/^97/', $shipping_address->getPostcode()))? 'FR' : $shipping_address->getCountry();
            }
        }

        $sacOrder->info_commande->refid = $order->getRealOrderId();
        $sacOrder->info_commande->devise = $order->getBaseCurrencyCode();
        $sacOrder->info_commande->montant = $order->getBaseGrandTotal();
		//Si l'IP de l'internaute n'est pas présente dans Magento (en cas de création de commande depuis le BO) alors on récupère l'IP de la boutique
		$sacOrder->info_commande->ip = (!$order->getRemoteIp()) ? $_SERVER['REMOTE_ADDR'] : $order->getRemoteIp(); 
        $sacOrder->info_commande->timestamp = $order->getCreatedAt();


        $sacOrder->info_commande->siteid = Mage::getStoreConfig('sac/sacconfg/siteid', $id);
        if ($sacOrder->info_commande->siteid == null && $id != 0)
            $sacOrder->info_commande->siteid = Mage::getStoreConfig('sac/sacconfg/siteid', '0');


        $sacOrder->info_commande->transport->type = $shipping->fianet_shipping_type;
        $sacOrder->info_commande->transport->nom = $shipping->conveyor_name;
        $sacOrder->info_commande->transport->rapidite = $shipping->delivery_times;

        foreach ($order->getAllVisibleItems() as $item) {
            $pAmount = $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseTaxAmount() + $item->getBaseWeeeTaxAppliedRowAmount();

            $pName = $item->getName();
            $pSku = $item->getSku();
            if ($item->getProduct_type() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $children_product = $item->getChildrenItems();
                if (count($children_product) == 1) {
                    $pName = $children_product[0]->getName();
                    $pSku = $children_product[0]->getSku();
                }
            }

            $product = Mage::getModel('fianet/fianet_order_info_productlist_product');
            $product->type = Mage::getModel('sac/product')->load($item->getProductId())->getFianetProductType();
            $product->prixunit = $pAmount;
            $product->name = $pName;
            $product->nb = (int) $item->getQtyOrdered();
            $product->ref = $pSku;
            $sacOrder->info_commande->list->addProduct($product);
        }
        $sacOrder->payment->type = Mage::getModel('sac/payment_association')->load($order->getPayment()->getMethod())->getValue();

        return ($sacOrder);
    }

}
