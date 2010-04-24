-- 
-- Table structure for table `app_accounts`
-- 

CREATE TABLE `app_accounts` (
  `faccount_id`     int(11) unsigned NOT NULL auto_increment COMMENT 'The unique id of this object in the database',
  `username`       varchar(20) NOT NULL COMMENT 'The username associated with this account',
  `password`       varchar(160) NOT NULL COMMENT 'The password for the account',
  `emailAddress`   varchar(80) NOT NULL COMMENT 'The email address associated with this account',
  `status`         varchar(20) NOT NULL COMMENT 'The status of this account',
  `secretQuestion` varchar(160) NOT NULL COMMENT 'The secret question for access to this account',
  `secretAnswer`   varchar(160) NOT NULL COMMENT 'The secret answer for the secret question',
  `objectClass`    varchar(50) NOT NULL COMMENT 'The class of the primary object associated with this account',
  `objectId`       int(11) unsigned NOT NULL COMMENT 'The id of the primary object associated with this account',
  `created`        datetime NOT NULL COMMENT 'When this account was created',
  `modified`       datetime NOT NULL COMMENT 'When this account was last modified',
  `lastLogin`      datetime NOT NULL COMMENT 'The last time this account logged in',
  `newPasswordKey` varchar(25) NOT NULL COMMENT 'A key for verifying forgot password attempts',
  PRIMARY KEY  (`faccount_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 COMMENT='table for application accounts' ;
END;
