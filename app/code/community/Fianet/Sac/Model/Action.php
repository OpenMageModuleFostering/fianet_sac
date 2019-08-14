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
class Fianet_Sac_Model_Action {

    protected static $_resource = null;
    protected static $_readConnection = null;
    protected static $_writeConnection = null;
    protected static $_tables = array();

    protected static function _initConnections() {
        self::$_resource = Mage::getSingleton('core/resource');
        self::$_readConnection = self::$_resource->getConnection('core_read');
        self::$_writeConnection = self::$_resource->getConnection('core_write');
        self::$_tables = array(self::$_resource->getTableName('sales/order'));
        $orderGrid = self::$_resource->getTableName('sales/order_grid');
        if ($orderGrid)
            self::$_tables[] = $orderGrid;
    }

    public static function sendMissingOrders() {
        $nb = 0;
        $date = Zend_Date::now();

        $collection = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('created_at', array('to' => ($date->subHour(3)->toString('Y-MM-dd HH:mm:ss'))))
                ->addAttributeToFilter('fianet_sac_sent', 1)
                ->addAttributeToFilter('fianet_sac_evaluation', 'absente')
                ->addAttributeToFilter('fianet_sac_had_to_send_again', 1)
                ->load();

        self::_initConnections();

        foreach ($collection as $object) {
            $orderId = (int) $object->getEntityId();
            $order = Mage::getModel('sales/order')->load($orderId);

            if (!Mage::helper('sac/order')->canSendOrder($order, $orderId, true)) {
                continue;
            }

            $storeId = $order->getStoreId();
            $statut = Mage::getStoreConfig('sac/sacconfg/active', $storeId);

            if ($statut == null && $storeId > 0) {
                $statut = Mage::getStoreConfig('sac/sacconfg/active', '0');
            }

            Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : New send evaluation request'));

            try {
                $payment_type = Mage::getModel('sac/payment_association')->load($order->getPayment()->getMethod())->getValue();
                $message = "Certissim : sending missing order #%s, payment type is %s sac status is %s";
                Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__($message, $order->getIncrementId(), $payment_type, $statut));
                $SacOrder = Fianet_Sac_Model_Fianet_Order_Sac::GenerateSacOrder($order);
                $sender = Mage::getModel('fianet/fianet_sender');
                $sender->addOrder($SacOrder);
                $response = $sender->send();
                Mage::helper('sac/order')->processResponse($response, array($order->getId()));

                // On indique que cette commande ne dois plus être envoyée
                // Mise à jour par SQL sinon boucle infinie
                $table = self::$_resource->getTableName('sales/order');
                $queryW = "UPDATE `{$table}` ";
                $queryW .= "SET `fianet_sac_had_to_send_again` = '-1' ";
                $queryW .= "WHERE `entity_id` = '{$orderId}';";
                self::$_writeConnection->query($queryW);

                $nb++;
            } catch (Exception $e) {
                Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : '.$e->getMessage()));
            }
        }
    }

    public static function getEvaluation($orderIds = array()) {
        Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : get Evaluation'));
        $nb = 0;

        $collection = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToSelect('store_id')
                ->addAttributeToSelect('fianet_sac_sent')
                ->addAttributeToSelect('fianet_sac_evaluation')
				->addAttributeToSelect('fianet_sac_classement_id')
				->addAttributeToSelect('fianet_sac_mode')
                ->addAttributeToFilter('fianet_sac_sent', 1) // uniquement les commandes déjà envoyée
				->addAttributeToFilter('fianet_sac_classement_id',
					array(
						 'or' => array(
                            0 => array('nin' => array('15','16','17','18','25','26')),
                            1 => array('is' => new Zend_Db_Expr('null'))
                        )
					)
				); // Uniquement les commandes dont le ClassementID n'est pas "final"

        // Uniquement les commandes sélectionnées quand évaluation lancée depuis le back-office
        if (is_array($orderIds) && (count($orderIds) > 0))
            $collection->addAttributeToFilter('entity_id', array('in' => $orderIds));

        $collection->load();

        $order_list = array();
		
        foreach ($collection as $order) {
            $fromstoreid = $order->getStoreId();

            $siteid = Mage::getStoreConfig('sac/sacconfg/siteid', $fromstoreid);
            if ($siteid == null && $fromstoreid > 0)
                $siteid = Mage::getStoreConfig('sac/sacconfg/siteid', '0');

            $login = Mage::getStoreConfig('sac/sacconfg/compte', $fromstoreid);
            if ($login == null && $fromstoreid > 0)
                $login = Mage::getStoreConfig('sac/sacconfg/compte', '0');

            $pwd = Mage::getStoreConfig('sac/sacconfg/password', $fromstoreid);
            if ($pwd == null && $fromstoreid > 0)
                $pwd = Mage::getStoreConfig('sac/sacconfg/password', '0');

            $order_list[$siteid]['login'] = $login;
            $order_list[$siteid]['pwd'] = $pwd;
            $order_list[$siteid]['mode'] = $order->getFianetSacMode();
            $order_list[$siteid]['orders'][] = $order->getIncrementId();
        }
		// Récupération des évaluations des commandes
        $evaluations = Mage::getModel('fianet/fianet_sender')->getEvaluations($order_list);
        self::_initConnections();
		
        foreach ($evaluations as $evaluation) {
			$eval = (isset($evaluation['eval'])) ? $evaluation['eval'] : $evaluation['info'];
            if (isset($eval)) {
				$maj_value = false;
				foreach (self::$_tables as $table) {
					// On vérifie si le ClassementID a évolué, sinon on ne fait rien
					// Plus rapide par requête directe
					$queryR = "SELECT `fianet_sac_evaluation`, `fianet_sac_classement_id` FROM `{$table}` WHERE `increment_id` = '{$evaluation['refid']}' LIMIT 0,1;";
					$infos_order = self::$_readConnection->fetchAll($queryR);
					foreach($infos_order as $info_order) {
						$evaluationOrder = $info_order['fianet_sac_evaluation'];
						$classementid = $info_order['fianet_sac_classement_id'];
					}
					
					//Si ClassementID de l'appel est différent de celui en BDD
					if(($classementid != $evaluation['classementid']) || ($eval != $evaluationOrder)) {
						$queryW = "UPDATE `{$table}`";
						$queryW .= " SET `fianet_sac_evaluation` = '{$eval}', `fianet_sac_classement_id` = '{$evaluation['classementid']}'";
						
						// uniquement pour la table sales_flat_order
						if (self::$_resource->getTableName('sales/order') == $table) {
							if ($eval == 'absente') {
								// Plus rapide par requête directe
								$queryR = "SELECT `fianet_sac_had_to_send_again` FROM `{$table}` WHERE `increment_id` = '{$evaluation['refid']}' LIMIT 0,1;";
								$sendAgain = self::$_readConnection->fetchOne($queryR);
								
								if ($sendAgain == '0') {
									// indique qu'il faut envoyer la commande encore 1 fois
									$queryW .= ", `fianet_sac_had_to_send_again` = '1'";
								}
							}
						}
						$queryW .= " WHERE `increment_id` = '{$evaluation['refid']}';";
						
						// On met à jour les commandes
						try {
							$maj_value = true; //la commande a été mise à jour
							self::$_writeConnection->query($queryW);
						}
						catch (Exception $e) {
							Mage::getModel('fianet/log')->log(Mage::helper('fianet')->__('Certissim : '.$e->getMessage()));
						}
					}
				}
				if($maj_value == true) { // Si la commande a été mise à jour, alors affichage dans les logs
					$textLog = "Certissim : Evaluation of the order ".$evaluation['refid']." has been received ";
					if(($eval == 'absente') || ($eval == 'encours')) {
						$textLog .= " (status : '".$eval."')";
					}
					else {
						$textLog .= "(score : '".$eval."' - ClassementID : '".$evaluation['classementid']."')";
					}
					Mage::getModel('fianet/log')->Log(Mage::helper('fianet')->__($textLog));
					$nb++;
				}
            }
        }
    }

    public static function getReevaluation() {
        $nb = 0;
        Mage::getModel('fianet/log')->Log(Mage::helper('fianet')->__('Certissim : Attempt to retrieve Reevaluation...'));
        $reevaluations = Mage::getModel('fianet/fianet_sender')->getReevaluation();
        Mage::getModel('fianet/log')->Log(Mage::helper('fianet')->__('Certissim : %s reevaluation found.', count($reevaluations)));

        self::_initConnections();

        foreach ($reevaluations as $reevaluation) {
            if (isset($reevaluation['eval'])) {
                // Mise à jour par SQL sinon boucle infinie
                foreach (self::$_tables as $table) {
                    $queryW = "UPDATE `{$table}`";
                    $queryW .= " SET `fianet_sac_reevaluation` = '{$reevaluation['eval']}'";
                    $queryW .= " WHERE `increment_id` = '{$reevaluation['refid']}';";
                    self::$_writeConnection->query($queryW);
                }

                $order = Mage::getModel('sales/order')->loadByIncrementId($reevaluation['refid']);

                Mage::getModel('fianet/log')->Log(Mage::helper('fianet')->__('Certissim : Order %s updated to eval %s', $reevaluation['refid'], $reevaluation['eval']));

                try {
                    $notification = Mage::getModel('adminnotification/inbox');
                    $notification->setseverity(Mage_AdminNotification_Model_Inbox::SEVERITY_MINOR);
                    $notification->setTitle(Mage::helper('fianet')->__('Order %s has been reevaluated', $reevaluation['refid']));
                    $notification->setDate_added(date('Y-m-d H:i:s'));

                    switch ($reevaluation['eval']) {
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

                    $img = '<img src="' . Mage::getDesign()->getSkinUrl('images/kwixo/' . $icon) . '">';
                    $message = Mage::helper('fianet')->__('Certissim : The order %s has now evaluation %s', $reevaluation['refid'], $img);

                    $notification->setDescription($message);

                    $notification->setUrl(self::getUrlBO($order));
                    $notification->save();
                } catch (Exception $e) {
                    Mage::getModel('fianet/log')->Log(Mage::helper('fianet')->__('Certissim : '.$e->getMessage()));
                }
                $nb++;
            }
        }
        Mage::getModel('fianet/log')->Log(Mage::helper('fianet')->__('Certissim : Retrieving reevaluation ended.'));
    }

    protected static function getUrlBO(Mage_Sales_Model_Order $order) {
        $url = '';
        //get BO url
        $url = Mage::getStoreConfig('sac/saclink/testurl', '0');
        if ($order->getData('fianet_sac_mode') == 'PRODUCTION') {
            $url = Mage::getStoreConfig('sac/saclink/produrl', '0');
        }
        $url .= Mage::getStoreConfig('sac/saclink/interface', '0');
        $fromstoreid = $order->getStoreId();

        //get info for connexion
        $siteid = Mage::getStoreConfig('sac/sacconfg/siteid', $fromstoreid);
        if ($siteid == null && $fromstoreid > 0)
            $siteid = Mage::getStoreConfig('sac/sacconfg/siteid', '0');

        $login = Mage::getStoreConfig('sac/sacconfg/compte', $fromstoreid);
        if ($login == null && $fromstoreid > 0)
            $login = Mage::getStoreConfig('sac/sacconfg/compte', '0');

        $password = Mage::getStoreConfig('sac/sacconfg/password', $fromstoreid);
        if ($password == null && $fromstoreid > 0)
            $password = Mage::getStoreConfig('sac/sacconfg/password', '0');

        $url .= '?sid=' . $siteid . '&log=' . $login . '&pwd=' . urlencode($password) . '&rid=' . $order->getIncrementId();

        return $url;
    }
	
}