/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Es existiert ein PHP-Update-Script, welches nach diesem SQL-Update-Script 
 * ausgeführt werden muss. Dafür muss man sich am BE als Admin (User mit 
 * Rechten für ModuleUserManagement) anmelden und dann über den Browser 
 * /edwin/admin/update-2_0_1-2_1_0.php aufrufen.
 * ACHTUNG: Nach dem Einloggen wird eine Fehlermeldung angezeigt, da für die neuen
 * Navigationsbäume noch keine Trees angelegt wurden. Man muss einfach die URL
 * zum PHP-Update-Script aufrufen, danach sollte ein fehlerfreies Arbeiten möglich 
 * sein.
 * [/INFO]
 */

/******************************************/
/* Navigationsbäume am BE neu behandeln   */
/******************************************/

ALTER TABLE mc_contentitem 
MODIFY CTree ENUM('main', 'footer', 'hidden', 'login', 'pages') NOT NULL DEFAULT 'main';

ALTER TABLE mc_user_rights
ADD UTree ENUM('main', 'footer', 'login', 'pages') DEFAULT NULL;

UPDATE mc_user_rights SET UTree = 'main' WHERE FK_SID != 0;
UPDATE mc_user_rights SET FK_SID = '0' WHERE UModules IS NOT NULL;

ALTER TABLE mc_moduletype_frontend ADD MActiveLogin TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE mc_moduletype_frontend ADD MActiveLandingPages TINYINT(1) NOT NULL DEFAULT 0;

/******************************************/
/* Loginbereich am FE                     */
/******************************************/

CREATE TABLE mc_frontend_user (
  FUID int(11) NOT NULL auto_increment,
  FUSID varchar(50) NOT NULL,
  FUCompany varchar(150) default NULL,
  FUPosition varchar(150) default NULL,
  FK_FID int(11) NOT NULL default '0',
  FUTitle varchar(50) default NULL,
  FUFirstname varchar(150) NOT NULL,
  FULastname varchar(150) NOT NULL,
  FUNick varchar(50) NOT NULL,
  FUPW varchar(50) NOT NULL,
  FUBirthday date default NULL,
  FUCountry int(11) NOT NULL,
  FUZIP varchar(6) NOT NULL,
  FUCity varchar(150) NOT NULL,
  FUAddress varchar(150) NOT NULL,
  FUPhone varchar(50) NOT NULL,
  FUMobilePhone varchar(50) NOT NULL,
  FUEmail varchar(100) NOT NULL,
  FUNewsletter tinyint(1) NOT NULL default '1',
  FUCreateDateTime datetime default NULL,
  FUChangeDateTime datetime default NULL,
  PRIMARY KEY  (FUID),
  KEY CID (FUID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_login (
  LID int(11) NOT NULL AUTO_INCREMENT,
  LTitle1 varchar(150) NOT NULL,
  LTitle2 varchar(150) NOT NULL,
  LTitle3 varchar(150) NOT NULL,
  LTitle4 varchar(150) NOT NULL,
  LTitle5 varchar(150) NOT NULL,
  LTitle6 varchar(150) NOT NULL,
  LTitle7 varchar(150) NOT NULL,
  LTitle8 varchar(150) NOT NULL,
  LTitle9 varchar(150) NOT NULL,
  LImage1 varchar(150) NOT NULL,
  LImage2 varchar(150) NOT NULL,
  LImage3 varchar(150) NOT NULL,
  LImage4 varchar(150) NOT NULL,
  LImage5 varchar(150) NOT NULL,
  LImage6 varchar(150) NOT NULL,
  LImage7 varchar(150) NOT NULL,
  LImage8 varchar(150) NOT NULL,
  LImage9 varchar(150) NOT NULL,
  LText1 text,
  LText2 text,
  LText3 text,
  LText4 text,
  LText5 text,
  LText6 text,
  LText7 text,
  LText8 text,
  LText9 text,
  FK_CIID int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (LID)
) ENGINE=MyISAM;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (32, 'ContentItemLogin', 0, 51);

CREATE TABLE mc_frontend_user_rights (
  FK_FUID int(11) NOT NULL,
  FK_FUGID int(11) NOT NULL,
  UNIQUE KEY FK_FUID_FUGID_UN (FK_FUID, FK_FUGID)
) ENGINE=MyISAM;

CREATE TABLE mc_frontend_user_group (
  FUGID int(11) NOT NULL AUTO_INCREMENT,
  FUGName varchar(50) NOT NULL,
  FUGDescription varchar(250) NOT NULL,
  PRIMARY KEY (FUGID)
) ENGINE=MyISAM;

CREATE TABLE mc_frontend_user_group_sites (
  FK_FUGID int(11) NOT NULL,
  FK_SID int(11) NOT NULL,
  UNIQUE KEY FK_FUGID_SID_UN (FK_FUGID, FK_SID)
) ENGINE=MyISAM;

CREATE TABLE mc_frontend_user_group_pages (
  FK_FUGID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  UNIQUE KEY FK_FUGID_CIID_UN (FK_FUGID, FK_CIID)
) ENGINE=MyISAM;

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
(20, 'frontendusermgmt', 'ModuleFrontendUserManagement', 0, 51, 0);

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode) VALUES
(23, 'login', 'ModuleLogin', 0, 0);
