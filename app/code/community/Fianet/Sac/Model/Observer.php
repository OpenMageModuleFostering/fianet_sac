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
class Fianet_Sac_Model_Observer {

    public function autoSendOrder($observer) {
        $object = $observer->getEvent()->getDataObject();
        if (!$object) {
            return $this;
        }

        $order = Mage::getModel('sales/order')->load((int) $object->getEntityId());

        if (!Mage::helper('sac/order')->canSendOrder($order, (int) $object->getEntityId())) {
            return $this;
        }

        $storeId = $order->getStoreId();
        $statut = Mage::getStoreConfig('sac/sacconfg/active', $storeId);

        if ($statut == null && $storeId > 0) {
            $statut = Mage::getStoreConfig('sac/sacconfg/active', '0');
        }

        Mage::getModel('fianet/log')->log('Certissim : send evaluation request');

        try {
            $payment_type = Mage::getModel('sac/payment_association')->load($order->getPayment()->getMethod())->getValue();
            $message = "Certissim : automatic sending order #%s, payment type is %s sac status is %s";
            Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__($message, $order->getIncrementId(), $payment_type, $statut));
            $SacOrder = Fianet_Sac_Model_Fianet_Order_Sac::GenerateSacOrder($order);
            $sender = Mage::getModel('fianet/fianet_sender');
            $sender->addOrder($SacOrder);
            $response = $sender->send();
            Mage::helper('sac/order')->processResponse($response, array($order->getId()));
        } catch (Exception $e) {
            Mage::getModel('fianet/log')->log($e->getMessage());
        }
        return $this;
    }

}