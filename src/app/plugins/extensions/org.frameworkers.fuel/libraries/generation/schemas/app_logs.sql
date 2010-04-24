-- 
-- Table structure for table for app_logs
--

CREATE TABLE `app_logs` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`created` DATETIME NOT NULL ,
`ip` VARCHAR( 15 ) NOT NULL ,
`code` INT NOT NULL ,
`extra` VARCHAR( 60 ) NOT NULL
) ENGINE = MYISAM ;
