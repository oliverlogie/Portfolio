/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO] 
 * Bei 1.8 kommt die SBPosition in module_siteindex_compendium dazu, 
 * hier müssen die Werte händisch vergeben werden, da keine automatische Zuordnung passieren kann
 * [/INFO] 
 */


/******************************************/
/* Updates V1.7.0                         */
/******************************************/

INSERT INTO `mc_contenttype` (`CTID` ,`CTTitle` ,`CTClass` ,`CTSelectable` ,`CTPosition`) VALUES ('57', 'Inhalt-Bauernhöfe', 'ContentItemLevel', 0, 52);
INSERT INTO `mc_contenttype` (`CTID` ,`CTTitle` ,`CTClass` ,`CTSelectable` ,`CTPosition`) VALUES ('19', 'Inhalt-Warenkorb', 'ContentItemSC', 0, 46);
INSERT INTO `mc_contenttype` (`CTID` ,`CTTitle` ,`CTClass` ,`CTSelectable` ,`CTPosition`) VALUES ('20', 'Inhalt-LigamanagerSaison', 'ContentItemLS', 0, 28);
INSERT INTO `mc_contenttype` (`CTID` ,`CTTitle` ,`CTClass` ,`CTSelectable` ,`CTPosition`) VALUES ('21', 'Inhalt-LigamanagerLiveTicker', 'ContentItemLL', 0, 49);
INSERT INTO `mc_contenttype` (`CTID` ,`CTTitle` ,`CTClass` ,`CTSelectable` ,`CTPosition`) VALUES ('22', 'Inhalt-Spieler', 'ContentItemSP', 0, 20);

CREATE TABLE IF NOT EXISTS mc_contentitem_sc (
  SID int(11) NOT NULL auto_increment,
  STitle1 varchar(150) NOT NULL default '',
  STitle2 varchar(150) NOT NULL default '',
  STitle3 varchar(150) NOT NULL default '',
  SText1 text,
  SText2 text,
  SText3 text,
  SImage1 varchar(150) NOT NULL default '',
  SImage2 varchar(150) NOT NULL default '',
  SImage3 varchar(150) NOT NULL default '',
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_contentitem_sp (
  PID int(11) NOT NULL auto_increment,
  PName varchar(150) default NULL,
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) default NULL,
  PImage2 varchar(150) default NULL,
  PImage3 varchar(150) default NULL,
  PShortDescription varchar(150) default NULL,
  PNick varchar(100) default NULL,
  PBirthday date default NULL,
  PHeight varchar(50) default NULL,
  PCountry varchar(50) default NULL,
  PNumber tinyint(4) default NULL,
  PPosition varchar(100) default NULL,
  PHobbies text,
  PFamilyStatus varchar(100) default NULL,
  PHistory text,
  FK_CIID int(11) NOT NULL default '0',
  FK_TID int(11) NOT NULL default '1',
  PRIMARY KEY  (PID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_contentitem_ls (
  SID int(11) NOT NULL auto_increment,
  STitle1 varchar(150) NOT NULL,
  STitle2 varchar(150) NOT NULL,
  SText1 text,
  SText2 text,
  SImage1 varchar(150) NOT NULL,
  SImage2 varchar(150) NOT NULL,
  FK_LID int(11) NOT NULL default '0',
  FK_YID int(11) NOT NULL default '0',
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

ALTER TABLE `mc_contentitem_bg` ADD `GImageTitles` TEXT NULL AFTER `GImage` ;
ALTER TABLE `mc_contentitem_bg` CHANGE `GText` `GText1` TEXT NULL ;
ALTER TABLE `mc_contentitem_bg` ADD `GText2` TEXT NULL AFTER `GText1` ,
ADD `GText3` TEXT NULL AFTER `GText2` ;

ALTER TABLE `mc_contentitem_bi` CHANGE `BText` `BText` TEXT NULL;

ALTER TABLE `mc_contentitem_cc` CHANGE `CCText` `CCText1` TEXT NULL ;
ALTER TABLE `mc_contentitem_cc` ADD `CCText2` TEXT NULL AFTER `CCText1` ,
ADD `CCText3` TEXT NULL AFTER `CCText2` ,
ADD `CCImage` VARCHAR( 150 ) NOT NULL AFTER `CCText3` ;

ALTER TABLE `mc_contentitem_dl` CHANGE `DLText` `DLText1` TEXT NULL  ;
ALTER TABLE `mc_contentitem_dl` ADD `DLText2` TEXT NULL AFTER `DLText1` ;
ALTER TABLE `mc_contentitem_dl` ADD `DLImageTitles` TEXT NULL AFTER `DLImage` ;

ALTER TABLE `mc_contentitem_dl_area` CHANGE `DAText` `DAText` TEXT NULL;

ALTER TABLE `mc_contentitem_ec` CHANGE `ECText` `ECText1` TEXT NULL ;
ALTER TABLE `mc_contentitem_ec` ADD `ECText2` TEXT NULL AFTER `ECText1` ,
ADD `ECText3` TEXT NULL AFTER `ECText2` ,
ADD `ECImage` VARCHAR( 150 ) NOT NULL AFTER `ECText3` ;

ALTER TABLE `mc_contentitem_ib` CHANGE `IText` `IText` TEXT NULL;

ALTER TABLE `mc_contentitem_ig` ADD `IImageTitles` TEXT NULL AFTER `IImage` ;
ALTER TABLE `mc_contentitem_ig` CHANGE `IText` `IText` TEXT NULL;

 ALTER TABLE `mc_contentitem_im` CHANGE `IText1` `IText1` TEXT NULL ,
CHANGE `IText2` `IText2` TEXT NULL ,
CHANGE `IText3` `IText3` TEXT NULL ;
ALTER TABLE `mc_contentitem_im` ADD `IImageTitles` TEXT NULL AFTER `IImage6` ;

ALTER TABLE `mc_contentitem_nl` CHANGE `NLTitle` `NLTitle1` VARCHAR( 150 )  NOT NULL;
ALTER TABLE `mc_contentitem_nl` ADD `NLTitle2` VARCHAR( 150 ) NOT NULL AFTER `NLTitle1` ,
ADD `NLTitle3` VARCHAR( 150 ) NOT NULL AFTER `NLTitle2` ;
ALTER TABLE `mc_contentitem_nl` CHANGE `NLText` `NLText1` TEXT NULL  ;
ALTER TABLE `mc_contentitem_nl` ADD `NLText2` TEXT NULL AFTER `NLText1` ,
ADD `NLText3` TEXT NULL AFTER `NLText2` ;

ALTER TABLE `mc_contentitem_pa` CHANGE `PText1` `PText1` TEXT NULL ,
CHANGE `PText2` `PText2` TEXT NULL ,
CHANGE `PText3` `PText3` TEXT NULL ;
ALTER TABLE `mc_contentitem_pa` CHANGE `PTitle` `PTitle1` VARCHAR( 150 ) NOT NULL ;
ALTER TABLE `mc_contentitem_pa` ADD `PTitle2` VARCHAR( 150 ) NOT NULL AFTER `PTitle1` ,
ADD `PTitle3` VARCHAR( 150 ) NOT NULL AFTER `PTitle2` ;
ALTER TABLE `mc_contentitem_pa` ADD `PImageTitles` TEXT NULL AFTER `PImage3` ;

ALTER TABLE `mc_contentitem_pi` ADD `PTitle3` VARCHAR( 150 ) NOT NULL AFTER `PTitle2` ;
ALTER TABLE `mc_contentitem_pi` CHANGE `PText1` `PText1` TEXT NULL ,
CHANGE `PText2` `PText2` TEXT NULL ;
ALTER TABLE `mc_contentitem_pi` ADD `PText3` TEXT NULL AFTER `PText2` ;
ALTER TABLE `mc_contentitem_pi` ADD `PImage3` VARCHAR( 150 ) NOT NULL AFTER `PImage2` ;
ALTER TABLE `mc_contentitem_pi` ADD `PImageTitles` TEXT NULL AFTER `PImage3` ;

ALTER TABLE `mc_contentitem_po` CHANGE `PText1` `PText1` TEXT NULL ,
CHANGE `PText2` `PText2` TEXT NULL ;
ALTER TABLE `mc_contentitem_po` ADD `PText3` TEXT NULL AFTER `PText2` ;
ALTER TABLE `mc_contentitem_po` ADD `PImage3` VARCHAR( 150 ) NOT NULL AFTER `PImage2` ;
ALTER TABLE `mc_contentitem_po` ADD `PImageTitles` TEXT NULL AFTER `PImage3` ;
ALTER TABLE `mc_contentitem_po` ADD `PNumber` VARCHAR( 50 ) NOT NULL AFTER `PPrice`  ;

ALTER TABLE `mc_contentitem_pt` CHANGE `PTitle` `PTitle1` VARCHAR( 150 ) NOT NULL  ;
ALTER TABLE `mc_contentitem_pt` CHANGE `PText1` `PText1` TEXT NULL ,
CHANGE `PText2` `PText2` TEXT NULL ,
CHANGE `PText3` `PText3` TEXT NULL ;
ALTER TABLE `mc_contentitem_pt` ADD `PTitle2` VARCHAR( 150 ) NOT NULL AFTER `PTitle1` ,
ADD `PTitle3` VARCHAR( 150 ) NOT NULL AFTER `PTitle2` ;
ALTER TABLE `mc_contentitem_pt` ADD `PImageTitles` TEXT NULL AFTER `PImage3` ;

ALTER TABLE `mc_contentitem_qs` CHANGE `QText1` `QText1` TEXT NULL ,
CHANGE `QText2` `QText2` TEXT NULL ;
ALTER TABLE `mc_contentitem_qs` ADD `QText3` TEXT NULL AFTER `QText2` ;
ALTER TABLE `mc_contentitem_qs` ADD `QImageTitles` TEXT NULL AFTER `QImage` ;

ALTER TABLE `mc_contentitem_qs_statement` CHANGE `QSText` `QSText` TEXT NULL ;
ALTER TABLE `mc_contentitem_qs_statement` ADD `QSImageTitles` TEXT NULL AFTER `QSImage` ;

ALTER TABLE `mc_contentitem_sc` CHANGE `SText1` `SText1` TEXT NULL ,
CHANGE `SText2` `SText2` TEXT NULL ,
CHANGE `SText3` `SText3` TEXT NULL ;

ALTER TABLE `mc_contentitem_se` CHANGE `SText` `SText` TEXT NULL  ;

ALTER TABLE `mc_contentitem_sp` ADD `PImageTitles` TEXT NULL AFTER `PImage3` ;

ALTER TABLE `mc_contentitem_ls` CHANGE `SText1` `SText1` TEXT NULL ,
CHANGE `SText2` `SText2` TEXT NULL ;
ALTER TABLE `mc_contentitem_ls` ADD `SImageTitles` TEXT NULL AFTER `SImage2` ;

ALTER TABLE `mc_contentitem_ti` CHANGE `TText1` `TText1` TEXT NULL ,
CHANGE `TText2` `TText2` TEXT NULL,
CHANGE `TText3` `TText3` TEXT NULL ;
ALTER TABLE `mc_contentitem_ti` CHANGE `TTitle` `TTitle1` VARCHAR( 150 ) NOT NULL ;
ALTER TABLE `mc_contentitem_ti` ADD `TTitle2` VARCHAR( 150 ) NOT NULL AFTER `TTitle1` ,
ADD `TTitle3` VARCHAR( 150 ) NOT NULL AFTER `TTitle2` ;
ALTER TABLE `mc_contentitem_ti` ADD `TImageTitles` TEXT NULL AFTER `TImage3` ;

ALTER TABLE `mc_contentitem_to` CHANGE `TTitle` `TTitle1` VARCHAR( 150 ) NOT NULL  ;
ALTER TABLE `mc_contentitem_to` ADD `TTitle2` VARCHAR( 150 ) NOT NULL AFTER `TTitle1` ,
ADD `TTitle3` VARCHAR( 150 ) NOT NULL AFTER `TTitle2` ;
 ALTER TABLE `mc_contentitem_to` CHANGE `TText1` `TText1` TEXT NULL ,
CHANGE `TText2` `TText2` TEXT NULL ;
ALTER TABLE `mc_contentitem_to` ADD `TText3` TEXT NULL AFTER `TText2` ;

ALTER TABLE `mc_contentitem_ts` CHANGE `TText1` `TText1` TEXT NULL ,
CHANGE `TText2` `TText2` TEXT NULL ;
ALTER TABLE `mc_contentitem_ts` ADD `TImageTitles` TEXT NULL AFTER `TImage2` ;

ALTER TABLE `mc_module_siteindex_compendium_global` ADD `FK_CIID` INT NULL AFTER `SBText3` ;


/******************************************/
/* Updates V1.7.3                         */
/******************************************/

INSERT INTO mc_contenttype (CTID, CTTitle, CTClass, CTSelectable, CTPosition) VALUES(23, 'Inhalt-Buchung', 'ContentItemBO', 0, 38);
INSERT INTO mc_contenttype (CTID, CTTitle, CTClass, CTSelectable, CTPosition) VALUES(24, 'Inhalt-ExterneDaten', 'ContentItemES', 0, 29);
INSERT INTO mc_contenttype (CTID, CTTitle, CTClass, CTSelectable, CTPosition) VALUES(25, 'Inhalt-BuchungStorno', 'ContentItemBC', 0, 39);

CREATE TABLE IF NOT EXISTS mc_contentitem_es (
  EID int(11) NOT NULL auto_increment,
  ETitle1 varchar(150) NOT NULL,
  ETitle2 varchar(150) NOT NULL,
  ETitle3 varchar(150) NOT NULL,
  EText1 text,
  EText2 text,
  EText3 text,
  EImage1 varchar(150) NOT NULL,
  EImage2 varchar(150) NOT NULL,
  EImage3 varchar(150) NOT NULL,
  EImageTitles text,
  EExt tinyint(4) NOT NULL,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (EID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_contentitem_bc (
  BID int(11) NOT NULL auto_increment,
  BTitle1 varchar(150) NOT NULL,
  BTitle2 varchar(150) NOT NULL,
  BTitle3 varchar(150) NOT NULL,
  BText1 text,
  BText2 text,
  BText3 text,
  BImage1 varchar(150) NOT NULL,
  BImage2 varchar(150) NOT NULL,
  BImage3 varchar(150) NOT NULL,
  BImageTitles text,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (BID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_contentitem_bo (
  BID int(11) NOT NULL auto_increment,
  BTitle1 varchar(150) default NULL,
  BTitle2 varchar(150) default NULL,
  BTitle3 varchar(150) default NULL,
  BTitle4 varchar(150) default NULL,
  BTitle5 varchar(150) default NULL,
  BTitle6 varchar(150) default NULL,
  BText1 text,
  BText2 text,
  BText3 text,
  BText4 text,
  BText5 text,
  BText6 mediumtext,
  BImage1 varchar(150) NOT NULL,
  BImage2 varchar(150) NOT NULL,
  BImage3 varchar(150) NOT NULL,
  BImage4 varchar(150) NOT NULL,
  BImage5 varchar(150) NOT NULL,
  BImage6 varchar(150) NOT NULL,
  BImageTitles text,
  FK_LID int(11) NOT NULL default '1',
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (BID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

 ALTER TABLE `mc_client` CHANGE `CCountry` `CCountry` INT NOT NULL  ;
 ALTER TABLE `mc_client` ADD `CBirthday` DATE NULL AFTER `CLastName` ;
 UPDATE `mc_client` SET CCountry=1 WHERE CCountry=0;

/******************************************/
/* Updates V1.8.0                         */
/******************************************/

INSERT INTO mc_contenttype (CTID, CTTitle, CTClass, CTSelectable, CTPosition) VALUES(26, 'Inhalt-Umfrage', 'ContentItemSU', 0, 40);

CREATE TABLE IF NOT EXISTS mc_contentitem_su (
  SID int(11) NOT NULL auto_increment,
  STitle1 varchar(150) NOT NULL,
  STitle2 varchar(150) NOT NULL,
  STitle3 varchar(150) NOT NULL,
  SText1 text,
  SText2 text,
  SText3 text,
  SImage1 varchar(150) NOT NULL,
  SImage2 varchar(150) NOT NULL,
  SImage3 varchar(150) NOT NULL,
  SImageTitles text,
  FK_SID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_client_actions (
  CAAID INT NOT NULL AUTO_INCREMENT ,
  FK_CID INT NOT NULL ,
  CADateTime DATETIME NOT NULL ,
  CAAction VARCHAR( 50 ) NOT NULL ,
  CAActionID INT NOT NULL ,
  CAActionText TEXT NOT NULL ,
  PRIMARY KEY ( CAAID )
) ENGINE = MYISAM;

DROP TABLE `mc_download`;
RENAME TABLE `mc_employee` TO `mc_module_employee`;
ALTER TABLE `mc_module_siteindex_compendium` ADD `SBPosition` TINYINT NOT NULL DEFAULT '0' AFTER `SBNoImage` ;

/* ADD MISSING TABLES */

CREATE TABLE IF NOT EXISTS mc_module_3drack (
  RAID int(11) NOT NULL auto_increment,
  RATitle varchar(100) NOT NULL,
  RACategoryID int(11) default NULL,
  RAImage varchar(100) NOT NULL,
  RAArea tinyint(4) NOT NULL default '1',
  FK_SID int(11) NOT NULL default '0',
  PRIMARY KEY  (RAID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_attribute (
  AVID int(11) NOT NULL auto_increment,
  AVTitle varchar(150) NOT NULL,
  AVText text NULL,
  AVImage varchar(150) NULL,
  AVPosition int(11) NOT NULL,
  FK_AID int(11) NOT NULL,
  PRIMARY KEY  (AVID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_attribute_global (
  AID int(11) NOT NULL auto_increment,
  ATitle varchar(150) NOT NULL,
  AText text NOT NULL,
  APosition int(11) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (AID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_booked_room (
  BRID int(11) NOT NULL auto_increment,
  BRDate date NOT NULL,
  BRTime tinyint(4) NOT NULL,
  FK_LID int(11) NOT NULL,
  FK_RID int(11) NOT NULL,
  FK_BID int(11) NOT NULL,
  PRIMARY KEY  (BRID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_booking (
  BID int(11) NOT NULL auto_increment,
  BTID varchar(100) NOT NULL,
  BDateTime datetime NOT NULL,
  BNumber varchar(50) NOT NULL,
  BCancellationCode varchar(50) NOT NULL,
  BCancellationDateTime datetime NOT NULL,
  BInvoiceNumber varchar(50) NOT NULL,
  BDateStart date NOT NULL,
  BDateEnd date NOT NULL,
  BComment text,
  BPrice double NOT NULL,
  BPriceTax double NOT NULL,
  BCancellationFee double NOT NULL default '0',
  BInvoiceFile VARCHAR( 150 ),
  BStatus tinyint(4) NOT NULL default '0',
  FK_CID int(11) NOT NULL,
  FK_LID int(11) NOT NULL,
  PRIMARY KEY  (BID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_booking_roominfo (
  BIID int(11) NOT NULL auto_increment,
  BIPrice double NOT NULL,
  BIPriceTax double NOT NULL,
  BIAdults tinyint(4) NOT NULL default '0',
  BITeenagers tinyint(11) NOT NULL default '0',
  BIKids tinyint(4) NOT NULL default '0',
  BIBabies tinyint(4) NOT NULL default '0',
  BIAdditionalMeal tinyint(4) NOT NULL default '0',
  BICode varchar(50) NOT NULL,
  FK_RID int(11) NOT NULL,
  FK_BID int(11) NOT NULL,
  PRIMARY KEY  (BIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_location (
  LID int(11) NOT NULL auto_increment,
  LName varchar(255) NOT NULL,
  LShortName varchar(150) NOT NULL,
  LText1 text,
  LText2 text,
  LText3 text,
  LMainImage varchar(150) default NULL,
  LImages text,
  LPosition tinyint(4) NOT NULL,
  LDeleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (LID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_room (
  RID int(11) NOT NULL auto_increment,
  RName varchar(255) NOT NULL,
  RBeeds tinyint(4) NOT NULL,
  RAdditionalBeeds tinyint(4) NOT NULL,
  RRank tinyint(4) NOT NULL,
  RMainImage varchar(150) default NULL,
  RImages text,
  RDependent tinyint(4) NOT NULL default '0',
  FK_LID int(11) NOT NULL,
  FK_TID int(11) NOT NULL,
  PRIMARY KEY  (RID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_roomtype (
  TID int(11) NOT NULL auto_increment,
  TName varchar(255) NOT NULL,
  TQuantity tinyint(4) NOT NULL default '0',
  TText1 text,
  TText2 text,
  TText3 text,
  TMainImage varchar(150) default NULL,
  TImages text,
  TMinAdults tinyint(4) NOT NULL default '1',
  TMaxAdults tinyint(4) NOT NULL,
  TMaxTeenagers tinyint(4) NOT NULL,
  TMaxKids tinyint(4) NOT NULL,
  TMaxBabies tinyint(4) NOT NULL,
  TMaxTotal tinyint(4) NOT NULL,
  TPriceComplete double NOT NULL,
  TPriceOnePerson double NOT NULL,
  TPriceAdult double NOT NULL,
  TPriceAdditionalAdult double NOT NULL,
  TPriceTeenager double NOT NULL,
  TPriceAdditionalTeenager double NOT NULL,
  TPriceKid double NOT NULL,
  TPriceAdditionalKid double NOT NULL,
  TPriceBaby double NOT NULL,
  TPriceAdditionalBaby double NOT NULL,
  TPriceAdditionalMeal double NOT NULL,
  TRank tinyint(4) NOT NULL,
  TDeleted tinyint(4) NOT NULL default '0',
  FK_LID int(11) NOT NULL,
  PRIMARY KEY  (TID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_booking_special_price (
  PID int(11) NOT NULL auto_increment,
  PDateStart date NOT NULL,
  PDateEnd date NOT NULL,
  PMinDuration tinyint(4) NOT NULL default '1',
  PTitle varchar(255) NOT NULL,
  PShortTitle varchar(150) NOT NULL,
  PPriceComplete double NOT NULL,
  PPriceOnePerson double NOT NULL,
  PPriceAdult double NOT NULL,
  PPriceAdditionalAdult double NOT NULL,
  PPriceTeenager double NOT NULL,
  PPriceAdditionalTeenager double NOT NULL,
  PPriceKid double NOT NULL,
  PPriceAdditionalKid double NOT NULL,
  PPriceBaby double NOT NULL,
  PPriceAdditionalBaby double NOT NULL,
  PPriceAdditionalMeal double NOT NULL,
  PUsed tinyint(4) NOT NULL default '0',
  PDeleted tinyint(4) NOT NULL default '0',
  FK_TID int(11) NOT NULL,
  PRIMARY KEY  (PID),
  KEY SDateStart (PDateStart,PDateEnd,FK_TID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_infoticker (
  IID int(11) NOT NULL auto_increment,
  IText text NOT NULL,
  IRotationTime int(11) NOT NULL default '0',
  IRandom tinyint(4) NOT NULL default '0',
  FK_SID int(11) NOT NULL default '0',
  PRIMARY KEY  (IID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_leaguemanager_game (
  GID int(11) NOT NULL auto_increment,
  GDateTime datetime NOT NULL,
  GTeamHome int(11) default NULL,
  GTeamHomeScore int(11) NOT NULL default '0',
  GTeamHomeScorePart1 tinyint(4) default NULL,
  GTeamHomeScorePart2 tinyint(4) default NULL,
  GTeamHomeScorePart3 tinyint(4) default NULL,
  GTeamHomeScorePart4 tinyint(4) default NULL,
  GTeamHomeScorePart5 tinyint(4) default NULL,
  GTeamHomeLineup text,
  GTeamGuest int(11) default NULL,
  GTeamGuestScore int(11) NOT NULL default '0',
  GTeamGuestScorePart1 tinyint(4) default NULL,
  GTeamGuestScorePart2 tinyint(4) default NULL,
  GTeamGuestScorePart3 tinyint(4) default NULL,
  GTeamGuestScorePart4 tinyint(4) default NULL,
  GTeamGuestScorePart5 tinyint(4) default NULL,
  GTeamGuestLineup text,
  GReport text,
  GScorer varchar(100) default NULL,
  GText1 text,
  GText2 text,
  GText3 text,
  GImage1 varchar(150) default NULL,
  GImage2 varchar(150) default NULL,
  GImage3 varchar(150) default NULL,
  GStatus tinyint(4) NOT NULL default '1',
  GDeleted tinyint(4) NOT NULL default '0',
  FK_YID int(11) NOT NULL,
  FK_LID int(11) NOT NULL default '1',
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (GID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_leaguemanager_game_ticker (
  TID int(11) NOT NULL auto_increment,
  TMinute varchar(10) default NULL,
  TImage varchar(100) default NULL,
  TText text,
  TDeleted tinyint(4) NOT NULL default '0',
  FK_GID int(11) NOT NULL,
  PRIMARY KEY  (TID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_leaguemanager_league (
  LID int(11) NOT NULL auto_increment,
  LName varchar(150) NOT NULL,
  LShortname varchar(10) NOT NULL,
  PRIMARY KEY  (LID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_leaguemanager_team (
  TID int(11) NOT NULL auto_increment,
  TName varchar(255) default NULL,
  TShortName varchar(20) default NULL,
  TImage1 varchar(150) default NULL,
  TLocation varchar(150) default NULL,
  TDeleted tinyint(4) NOT NULL default '0',
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (TID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_leaguemanager_year (
  YID int(11) NOT NULL auto_increment,
  YStartDate date NOT NULL,
  YEndDate date NOT NULL,
  YName varchar(150) NOT NULL,
  PRIMARY KEY  (YID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_newsticker (
  TID int(11) NOT NULL auto_increment,
  TTitle varchar(150) NOT NULL,
  TText text NOT NULL,
  TImage varchar(150) NOT NULL,
  TSelectedItems varchar(255) default NULL,
  TShowOnPages text,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (TID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_sidebox (
  BID int(11) NOT NULL auto_increment,
  BTitle1 varchar(150) default NULL,
  BTitle2 varchar(150) default NULL,
  BTitle3 varchar(150) default NULL,
  BText1 text,
  BText2 text,
  BText3 text,
  BImage1 varchar(150) default NULL,
  BImage2 varchar(150) default NULL,
  BImage3 varchar(150) default NULL,
  BPosition tinyint(11) NOT NULL default '0',
  BShowOnPages varchar(255) default NULL,
  BNoRandom tinyint(4) NOT NULL default '0',
  FK_CIID int(11) default NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (BID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_siteindex_textonly (
  SBID int(11) NOT NULL auto_increment,
  SBTitle1 varchar(100) NOT NULL default '',
  SBText1 text NOT NULL,
  SBTitle2 varchar(100) NOT NULL default '',
  SBText2 text NOT NULL,
  SBTitle3 varchar(100) NOT NULL default '',
  SBText3 text NOT NULL,
  FK_SID int(11) NOT NULL default '0',
  PRIMARY KEY  (SBID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey (
  SID int(11) NOT NULL auto_increment,
  STitle varchar(150) default NULL,
  SShortTitle varchar(150) default NULL,
  SText text,
  SShortText text,
  SDateStart date default NULL,
  SDateEnd date default NULL,
  SDeleted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (SID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey_answer (
  AID int(11) NOT NULL auto_increment,
  ATitle varchar(255) default NULL,
  AText1 text,
  AImage1 varchar(150) default NULL,
  ALink varchar(150) default NULL,
  APosition int(11) NOT NULL,
  ADeleted tinyint(4) NOT NULL default '0',
  FK_QID int(11) NOT NULL,
  PRIMARY KEY  (AID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey_answer_total (
  FK_SID int(11) NOT NULL,
  FK_QID int(11) NOT NULL,
  FK_AID int(11) NOT NULL,
  SATCount int(11) NOT NULL default '0',
  SATCountNewUsers int(11) NOT NULL default '0',
  SATCountUsers int(11) NOT NULL default '0',
  SATCountAnonym int(11) NOT NULL default '0',
  SATCountManual int(11) NOT NULL default '0',
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey_question (
  QID int(11) NOT NULL auto_increment,
  QTitle varchar(150) default NULL,
  QText1 text,
  QText2 text,
  QText3 text,
  QImage1 varchar(150) default NULL,
  QLink varchar(150) default NULL,
  QType tinyint(4) NOT NULL default '1',
  QPosition int(11) NOT NULL,
  QMinSelected tinyint(4) default NULL,
  QMaxSelected tinyint(4) default NULL,
  QDeleted tinyint(4) NOT NULL default '0',
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (QID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey_total (
  FK_SID int(11) NOT NULL,
  STCount int(11) NOT NULL default '0',
  STCountNewUsers int(11) NOT NULL default '0',
  STCountUsers int(11) NOT NULL default '0',
  STCountAnonym int(11) NOT NULL default '0',
  STCountManual int(11) NOT NULL default '0',
  PRIMARY KEY  (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey_users (
  SVUID int(11) NOT NULL auto_increment,
  SVIP varchar(50) NOT NULL,
  SVHostname varchar(150) NOT NULL,
  SVDateTime datetime NOT NULL,
  SVSessionID varchar(150) default NULL,
  FK_SID int(11) NOT NULL,
  FK_CID int(11) NOT NULL,
  PRIMARY KEY  (SVUID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_module_survey_votes (
  FK_SID int(11) NOT NULL,
  FK_AID int(11) NOT NULL,
  FK_SVUID int(11) NOT NULL,
  KEY FK_SID (FK_SID,FK_AID)
) ENGINE=MyISAM;
