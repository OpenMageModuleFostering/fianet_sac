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
?>
<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('fianet_kwixo_catproduct_association')}` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `catalog_category_entity_id` int(11) unsigned NOT NULL,
    `fianet_product_type` int(5) unsigned NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `catalog_category_entity_id` (`catalog_category_entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$this->getTable('fianet_kwixo_shipping_association')}` (
    `shipping_code` varchar(255) NOT NULL,
    `fianet_shipping_type` enum('1','2','3','4','5') NOT NULL default '4',
    `delivery_times` enum('1','2') NOT NULL default '2',
    `conveyor_name` varchar(255) NOT NULL,
    PRIMARY KEY  (`shipping_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
