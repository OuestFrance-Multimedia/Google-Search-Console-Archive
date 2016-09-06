#
# Filters table, work in progress, add it once
#

CREATE TABLE `filters` (
  `name` varchar(30) NOT NULL,
  `query` varchar(45) DEFAULT NULL,
  `value` mediumtext NOT NULL,
  `website` varchar(45) NOT NULL,
  UNIQUE KEY `UNIQUE` (`website`,`query`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
