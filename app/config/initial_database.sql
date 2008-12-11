-- /**
--  * Initial Application Database Schema
--  * Furnace - Frameworkers.org
--  *
--  * Instructions:
--  *   1) Manually create an empty database
--  *   2) Execute the statements in this file
--  *   3) Edit your application configuration to point to the database
--  *   4) Further customize your application's model using Fuel.
--  *
-- **/
--

-- 
-- Table structure for table `app_accounts`
-- 

CREATE TABLE `app_accounts` (
  `objId` int(11) unsigned NOT NULL auto_increment COMMENT 'The unique id of this object in the database',
  `username` varchar(20) NOT NULL COMMENT 'The username associated with this account',
  `password` varchar(160) NOT NULL COMMENT 'The password for the account',
  `emailAddress` varchar(80) NOT NULL COMMENT 'The email address associated with this account',
  `status` varchar(20) NOT NULL COMMENT 'The status of this account',
  `secretQuestion` varchar(160) NOT NULL COMMENT 'The secret question for access to this account',
  `secretAnswer` varchar(160) NOT NULL COMMENT 'The secret answer for the secret question',
  `objectClass` varchar(50) NOT NULL COMMENT 'The class of the primary object associated with this account',
  `objectId` int(11) unsigned NOT NULL COMMENT 'The id of the primary object associated with this account',
  PRIMARY KEY  (`objId`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 COMMENT='table for application accounts' ;


-- 
-- Table structure for table `app_roles`
-- 

CREATE TABLE `app_roles` (
  `accountId` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`accountId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='permissions table for application accounts';

