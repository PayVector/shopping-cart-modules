INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, type, addon) VALUES ('PayVector', 'payvector.php', 'addons/payvector/views/cc_payvector.tpl', 'payvector.tpl', 'Y', 'P', 'payvector');
CREATE TABLE IF NOT EXISTS `?:payvector_cross_reference` (
  `id_cross_reference` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `cross_reference` VARCHAR(24) DEFAULT NULL,
  `card_last_four` VARCHAR(4) DEFAULT NULL,
  `card_type` VARCHAR(45) DEFAULT NULL,
  `last_updated` VARCHAR(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;