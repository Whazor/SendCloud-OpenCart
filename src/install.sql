CREATE TABLE IF NOT EXISTS `oc_sendcloud_order` (
  `order_id` int(11) NOT NULL,
  `parcel_id` int(11) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
