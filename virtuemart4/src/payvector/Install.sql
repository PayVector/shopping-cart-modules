CREATE TABLE IF NOT EXISTS `#__virtuemart_payment_plg_payvector_gateway_entry_points` (
  `id` int(11) NOT NULL auto_increment,
  `GatewayEntryPointObject` longtext NOT NULL,
  `DateTimeProcessed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

INSERT INTO `#__virtuemart_payment_plg_payvector_gateway_entry_points`(`GatewayEntryPointObject`,`DateTimeProcessed`) values('PlaceHolder',NOW() - INTERVAL 30 MINUTE);

CREATE TABLE IF NOT EXISTS `#__virtuemart_payment_plg_payvector_cross_reference` (
  `user_id` INT(11) NOT NULL,
  `cross_reference` VARCHAR(24) NULL DEFAULT NULL,
  `card_last_four` VARCHAR(4) NULL DEFAULT NULL,
  `card_type` VARCHAR(45) NULL DEFAULT NULL,
  `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE = MyISAM DEFAULT CHARACTER SET = utf8;