/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Im EDWIN Release 1.9.2 wurde das ModuleDownloadTicker implementiert. Im
 * DB-Install Skript fehlt jedoch seit Beginn der Fremdschlüssel FK_DFID. Dieser
 * wird mit dieser Release-Version (2.3.1) zu allen Installationsskripten
 * hinzugefügt.
 *
 * Am BE wurde bei den Textfeldern der DL Areas der TinyMCE Editor aktiviert.
 * Außerdem wurde die Ausgabe von CB Boxen, QS Statements und TS Blöcken am FE
 * geändert und das nl2br entfernt. Wenn beim gerade bearbeiteten Projekt
 * der Editor für diese Textfelder bereits aktiviert war, kann das PHP-Update
 * Skript update-2_3_0-2_3_1.php ohne Änderungen ausgeführt werden (DL Areas
 * sollten immer geändert werden). Dabei werden Newlines in den Textfeldern durch
 * Breaklines ('<br/>') ersetzt, sofern im Text kein Breakline existiert (=
 * kein Editor aktiviert oder kein Zeilenumbruch).
 * Folgende Konfigurationsvariablen können im $_CONFIG gesetzt werden (Boolean):
 *    - 'update_cb_box'
 *    - 'update_qs_statement'
 *    - 'update_ts_block'
 *    - 'update_dl_area' => sollte nicht deaktiviert werden (Standard = true),
 *                          bzw. nur dann deaktivieren wenn bereits ein Editor am
 *                          Textfeld aktiviert war.
 * [/INFO]
 */

/******************************************************************************/
/*  Fehlende Spalte für mc_module_downloadticker in DB-Installationsskripten  */
/*  (notwendig für alle pre-1.9.2 Versionen)                                  */
/******************************************************************************/

ALTER TABLE mc_module_downloadticker
ADD FK_DFID INT NULL AFTER FK_FID,
ADD INDEX ( FK_DFID );

/******************************************************************************/
/*      ModuleSideBox - Anzeige auf Inhalt bzw. Knoten oder ab Knoten         */
/******************************************************************************/

ALTER TABLE mc_module_sidebox_assignment
ADD BABeneath TINYINT( 1 ) NOT NULL DEFAULT '0';

/******************************************************************************/
/*                         Neues Modul GlobalSideBox                          */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`) VALUES(43, 'globalsidebox', 'ModuleGlobalSideBox', 0, 0, 0, 0);

/******************************************************************************/
/*                         Neues Modul GeoDetection                           */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser) VALUES ('42', 'geodetection', 'ModuleGeoDetection', '0', '0', '0', '0', '0');

/******************************************************************************/
/*           ContentItemCC - Dateiupload mit Mailversand                      */
/******************************************************************************/

CREATE TABLE IF NOT EXISTS mc_client_uploads (
  CUID int(11) NOT NULL AUTO_INCREMENT,
  FK_CID int(11) NOT NULL,
  CUCreateDateTime datetime NOT NULL,
  CUFile varchar(50) NOT NULL,
  PRIMARY KEY (CUID),
  KEY FK_CID (FK_CID)
) ENGINE=MyISAM;

/********************************************************/
/*            Reseller Modul BE/FE                      */
/********************************************************/

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`) VALUES
(28, 'reseller', 'ModuleReseller', 0, 52, 0);

CREATE TABLE `mc_module_reseller_areas` (
  RAID int(11) NOT NULL AUTO_INCREMENT,
  RAName varchar(255) NOT NULL default '',
  FK_RAID int(11) NOT NULL default '0',
  PRIMARY KEY (RAID),
  KEY FK_RAID (FK_RAID)
) ENGINE=MyISAM AUTO_INCREMENT=1;

CREATE TABLE `mc_module_reseller` (
  RID int(11) NOT NULL AUTO_INCREMENT,
  RName varchar(255) NOT NULL default '',
  RAddress varchar(255) NOT NULL default '',
  RPostalCode varchar(50) NOT NULL default '',
  RCity varchar(80) NOT NULL default '',
  RCountry varchar(80) NOT NULL default '',
  RCallNumber varchar(100) NOT NULL default '',
  RFax varchar(100) NOT NULL default '',
  REmail varchar(150) NOT NULL default '',
  RWeb varchar(255) NOT NULL default '',
  RNotes varchar(255) NOT NULL default '',
  RDefault tinyint(1) NOT NULL default '0',
  PRIMARY KEY (RID)
) ENGINE=MyISAM AUTO_INCREMENT=1;

CREATE TABLE `mc_module_reseller_assignation` (
  FK_RAID int(11) NOT NULL,
  FK_RID int(11) NOT NULL,
  KEY FK_RAID (FK_RAID),
  KEY FK_RID (FK_RID)
) ENGINE=MyISAM;

CREATE TABLE `mc_module_reseller_labels` (
  FK_RAID int(11) NOT NULL,
  RLLanguage varchar(255) DEFAULT NULL,
  RLLabel varchar(255) DEFAULT NULL,
  KEY FK_RAID (FK_RAID)
) ENGINE=MyISAM;

INSERT INTO `mc_contenttype` (CTID, CTClass, CTActive, CTPosition)
VALUES (35, 'ContentItemRS', 0, 53);

CREATE TABLE `mc_contentitem_rs` (
  `RSID` int(11) NOT NULL AUTO_INCREMENT,
  `RSTitle1` varchar(150) NOT NULL,
  `RSTitle2` varchar(150) NOT NULL,
  `RSTitle3` varchar(150) NOT NULL,
  `RSImage1` varchar(150) NOT NULL,
  `RSImage2` varchar(150) NOT NULL,
  `RSImage3` varchar(150) NOT NULL,
  `RSImageTitles` text,
  `RSText1` text,
  `RSText2` text,
  `RSText3` text,
  `FK_CIID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`RSID`),
  KEY `FK_CID` (`FK_CIID`)
) ENGINE=MyISAM  AUTO_INCREMENT=1 ;

/******************************************************************************/
/*                Neu ContentItemFQ - FAQ Inhaltstyp                          */
/******************************************************************************/

CREATE TABLE IF NOT EXISTS mc_contentitem_fq (
  FQID int(11) NOT NULL AUTO_INCREMENT,
  FQTitle1 varchar(255) NOT NULL,
  FQTitle2 varchar(255) NOT NULL,
  FQTitle3 varchar(255) NOT NULL,
  FQText1 text,
  FQText2 text,
  FQText3 text,
  FQImage1 varchar(150) NOT NULL,
  FQImage2 varchar(150) NOT NULL,
  FQImage3 varchar(150) NOT NULL,
  FQImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FQID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;


CREATE TABLE IF NOT EXISTS mc_contentitem_fq_question (
  FQQID int(11) NOT NULL AUTO_INCREMENT,
  FQQTitle varchar(255) NOT NULL,
  FQQText text,
  FQQImage varchar(150) NOT NULL,
  FQQImageTitles text,
  FQQPosition int(11) NOT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FQQID),
  UNIQUE KEY FK_CIID_FQQPosition_UN (FK_CIID,FQQPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;


INSERT INTO `mc_contenttype` (CTID, CTClass, CTActive, CTPosition)
VALUES (36, 'ContentItemFQ', 0, 54);
