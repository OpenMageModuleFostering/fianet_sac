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
class Fianet_Sac_Helper_Order extends Mage_Core_Helper_Abstract {

    protected $_resource = null;
    protected $_readConnection = null;
    protected $_writeConnection = null;
    protected $_tables = array();
    protected $_allowedCountries = array(
        'AT', //Autriche
        'BE', //Belgique
        'BG', //Bulgarie
        'CH', //Suisse
        'CY', //Chypre
        'CZ', //République tchèque
        'DE', //Allemagne
        'DK', //Danemark
        'EE', //Estonie
        'ES', //Espagne
        'FI', //Finlande
        'FR', //France
        'GB', //Royaume-Uni
        'GF', //Guyane française
        'GP', //Guadeloupe
        'GR', //Grèce
        'HU', //Hongrie
        'IE', //Irlande
        'IT', //Italie
        'LT', //Lituanie
        'LU', //Luxembourg
        'LV', //Lettonie
        'MC', //Monaco
        'MQ', //Martinique
        'MT', //Malte
        'NL', //Pays-Bas
        'PL', //Pologne
        'PT', //Portugal
        'RE', //Réunion
        'RO', //Roumanie
        'SE', //Suède
        'SI', //Slovénie
        'SK', //Slovaquie
        'YT', //Mayotte
    );

    protected function _initConnections() {
        $this->_resource = Mage::getSingleton('core/resource');
        $this->_readConnection = $this->_resource->getConnection('core_read');
        $this->_writeConnection = $this->_resource->getConnection('core_write');
        $this->_tables = array($this->_resource->getTableName('sales/order'));
        $orderGrid = $this->_resource->getTableName('sales/order_grid');
        if ($orderGrid)
            $this->_tables[] = $orderGrid;
    }

     public function canSendOrder($order, $orderId = 0, $isMissingOrder = false) {
		//Récupération des Status définis dans les paramétrages du module
        $myarray = Mage::getStoreConfig('sac/sacconfg/procasso', '0');
        if ($myarray)
            $modeArray = explode(',', $myarray);
        else
            $modeArray = array();
			
        // Aucune commande trouvée
        if (!$order->getId()) {
            Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : invalid order id %s', $orderId));
            return false;
        }

		//Pas d'envoi si le module n'est pas activé
		if(Mage::getStoreConfig('sac/sacconfg/active') == 0){
			Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : module disabled, order id %s not sent', $orderId));
            return false;
		}
		
		//Pas d'envoi si la commande a été passée avant le délai indiqué dans le module
		$time = Mage::getStoreConfig('sac/sacconfg/timecontrol');
		//Si mauvais format, aucune vérification
		if(isset($time) && is_numeric($time)){
			//récupération de la date max à comparer et mise au format Date
			$dateMax = new DateTime(date('Y-m-d H:i:s', time() - ($time * 24 * 60 * 60)));
			$dateOrder = new DateTime($order->getCreatedAt());
			
			if($dateOrder < $dateMax){
				Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order id %s is too old (%s day max)', $orderId, $time));
				return false;
			}
		}
		
        // Pas d'envoi si la commande a un total égal à zéro
        if ($order->getGrandTotal() == 0) {
            Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order id %s has a total of %s', $orderId, $order->getGrandTotal()));
            return false;
        }

        // Pas d'envoi si la commande est déjà envoyée sauf 2ème envoie pour transaction absente
        if (!$isMissingOrder) {
            if ($order->getFianetSacSent()) {
                Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order #%s already sent', $order->getIncrementId()));
                return false;
            }
        }
		
		// Pas d'envoi si la commande n'est pas dans un statut défini dans les paramétrages du module
        if (!in_array($order->getStatus(), $modeArray)) {
			// Si la commande ne vient pas d'être passée
			if($order->getStatus() != '' && $order->getState()!='new') {
				Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order #%s not sent - status: %s not selected', $order->getIncrementId(), $order->getStatus()));
			} else if($order->getStatus() != '') {
				Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order #%s not sent - state: %s', $order->getIncrementId(), $order->getState()));
			}
            return false;
        }

        // Pas d'envoi sur les paiements RNP et Kwixo
        if (preg_match('/receiveandpay/i', $order->getPayment()->getMethod()) || preg_match('/kwx/i', $order->getPayment()->getMethod())) {
            Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order #%s not sent - paymentMethod: %s', $order->getIncrementId(), $order->getPayment()->getMethod()));
            return false;
        }

        $billingAddressCounrty = $order->getBillingAddress()->getCountry();
        $shippingAddressCounrty = $order->getShippingAddress()->getCountry();

		// Pas d'envoi sur les paiements ayant pour adresse un pays non géré par Certissim
        if (!(in_array($billingAddressCounrty, $this->_allowedCountries) && in_array($shippingAddressCounrty, $this->_allowedCountries))) {
            Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order #%s not sent - billing country: %s - shipping country: %s', $order->getIncrementId(), $billingAddressCounrty, $shippingAddressCounrty));
            return false;
        }

        return true;
    }

    public function processResponse($responses, $orderIds = array()) {
        $nb = 0;
        $this->_initConnections();

        // On indique que la commande a été envoyée
        foreach ($orderIds as $orderId) {
            // Plus rapide par requête directe
            $queryR = "SELECT `store_id` FROM `{$this->_resource->getTableName('sales/order')}` WHERE `entity_id` = '{$orderId}' LIMIT 0,1;";
            $results = $this->_readConnection->fetchAll($queryR);

            if (count($results) == 0)
                continue; // Pas de résultat trouvé

            foreach ($results as $result) {
                $sotreId = $result['store_id'];
            }

            $mode = Mage::getStoreConfig('sac/sacconfg/mode', $sotreId);
            if ($mode == null && $sotreId > 0)
                $mode = Mage::getStoreConfig('sac/sacconfg/mode', '0');

            if ($mode == Fianet_Core_Model_Source_Mode::MODE_PRODUCTION) {
                $mode = 'PRODUCTION';
            } else {
                $mode = 'TEST';
            }

            // Mise à jour par SQL sinon boucle infinie
            foreach ($this->_tables as $table) {
                $queryW = "UPDATE `{$table}` ";
                $queryW .= "SET `fianet_sac_sent` = '1', ";
                $queryW .= "`fianet_sac_mode` = '{$mode}' ";
                $queryW .= "WHERE `entity_id` = '{$orderId}';";
                $this->_writeConnection->query($queryW);
            }
        }

        // On traite les réponses
        foreach ($responses as $response) {
            if ($response['etat'] == 'error' || $response['etat'] == 'encours') {
                // Mise à jour par SQL sinon boucle infinie
                foreach ($this->_tables as $table) {
                    $query = "UPDATE `{$table}` ";
                    $query .= "SET `fianet_sac_evaluation` = '{$response['etat']}' ";
                    $query .= "WHERE `increment_id` = '{$response['refid']}';";
                    $this->_writeConnection->query($query);
                }

                $nb++;
                Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : order %s sent and is currently in state : %s', $response['refid'], $response['etat']));
            } else {
                Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__(get_class($this) . '::' . __FUNCTION__ . ' : unknow state %s for order %s', $response['etat'], $response['refid']));
            }
        }
        return ($nb);
    }

}