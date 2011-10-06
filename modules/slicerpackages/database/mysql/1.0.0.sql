CREATE TABLE IF NOT EXISTS `slicerpackages_package` (
  `package_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) NOT NULL,
  `os` varchar(256) NOT NULL,
  `arch` varchar(256) NOT NULL,
  `revision` varchar(256) NOT NULL,
  `submissiontype` varchar(256) NOT NULL,
  `packagetype` varchar(256) NOT NULL,
  PRIMARY KEY (`package_id`)
) AUTO_INCREMENT=10;
