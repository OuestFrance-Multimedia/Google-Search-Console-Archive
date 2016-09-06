#
# Filters table, work in progress, add it once
#
# Don't forget to replace {%website%} token with your website configuration name (id in the sample)
#

CREATE TABLE `filters` (
  `name` varchar(30) NOT NULL,
  `query` varchar(45) DEFAULT NULL,
  `value` mediumtext NOT NULL,
  `website` varchar(45) NOT NULL,
  UNIQUE KEY `UNIQUE` (`website`,`query`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `filters` (`name`,`query`,`value`,`website`) VALUES ('Home','page','^/$','{%website%}');
INSERT INTO `filters` (`name`,`query`,`value`,`website`) VALUES ('All','query','*','{%website%}');