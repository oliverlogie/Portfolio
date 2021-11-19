/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * Attributgruppen können nun über das Backend verwaltet werden. Sie müssen
 * nun Inhaltstypen, bei denen sie verwendet werden sollen zugeordnet werden.
 * Es wird im Feld FK_CTID der Inhaltstyp gespeichert, die zur Auswahl stehenden
 * Inhaltstypen müssen über $_LANG['ag_content_type_options'] definiert werden.
 * Dieses SQL Update Skript kann nur die Zuweisung zur Variationsebene
 * automatisch durchführen ( ehemals AVariation - wird mit diesem Skript
 * entfernt ), werden Attribute mit anderen Inhaltstypen verwendet, muss die
 * Zuweisung nach dem Update manuell gemacht werden. Inhaltstypen die Attribute
 * verwenden:
 *            - 18 = IM
 *            - 17 = PA
 *            - 42 = PP
 *            - 79 = VA
 * Beim ModuleAttribute sollte für Benutzer, die Attributgruppen nicht
 * bearbeiten dürfen, die entsprechende Submodul-Berechtigung gesetzt werden.
 *
 * In der logischen Ebene am Backend wurden einige Template IFs im Code entfernt
 * und sattdessen unterschiedliche Strings direkt von PHP ausgegeben. Sollte
 * das lang.core.php File im custom Verzeichnis angelegt sein, müssen folgende
 * Variablen angepasst werden ( da auch HTML Formatierungen in den Variablen )
 * existieren:
 *            - lo_parent_datetime_warning
 *            - lo_children_datetime_warning
 *            - lo_conflict_parent_datetime_warning
 *
 * Am Backend können über den TinyMCE Editor Ankerlinks erstellt werden, dazu
 * sollte unbedingt das Plugin "anchor" aktiviert werden, damit auch Ankerpunkte
 * definiert werden können.
 *
 * In der Mediendatenbank wird die Checkbox "Download immer anzeigen" über das
 * styles.css nun standardmäßig ausgeblendet. Die Checkbox sollte wieder 
 * eingeblendet werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/*               ES mit IFrame - Höhe am Backend konfigurierbar               */
/******************************************************************************/

ALTER TABLE mc_contentitem_es ADD EFrameHeight SMALLINT NULL AFTER EExt;

/******************************************************************************/
/*                              ContentItemFD                                 */
/******************************************************************************/

CREATE TABLE mc_contentitem_fd (
  FDID int(11) NOT NULL AUTO_INCREMENT,
  FDTitle1 varchar(150) NOT NULL,
  FDTitle2 varchar(150) NOT NULL,
  FDTitle3 varchar(150) NOT NULL,
  FDImage1 varchar(150) NOT NULL,
  FDImage2 varchar(150) NOT NULL,
  FDImage3 varchar(150) NOT NULL,
  FDImageTitles text,
  FDText1 text,
  FDText2 text,
  FDText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FDID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (41, 'ContentItemFD', 0, 59);

CREATE TABLE mc_contentitem_fd_maindata (
  FDMDID bigint(20) unsigned NOT NULL,
  FDMDType VARCHAR( 255 ) NOT NULL,
  FDMDTitle1 varchar(50) NOT NULL,
  FDMDTitle2 varchar(50) NOT NULL,
  FDMDFirstname varchar(50) NOT NULL,
  FDMDLastname varchar(50) NOT NULL,
  FDMDLastnameBirth varchar(50) NOT NULL,
  FDMDGender enum('unbekannt', 'maennlich', 'weiblich') NOT NULL DEFAULT 'unbekannt',
  FDMDBirthday date DEFAULT NULL,
  FDMDBirthPlace varchar(100) NOT NULL,
  FDMDBirthCountry varchar(100) NOT NULL,
  FDMDFirstnameMother varchar(100) NOT NULL,
  FDMDFirstnameFather varchar(100) NOT NULL,
  FDMDAddress varchar(100) NOT NULL,
  FDMDZIP varchar(10) NOT NULL,
  FDMDCity varchar(100) NOT NULL,
  FDMDCountry varchar(100) NOT NULL,
  FDMDFamilyStatus enum('unbekannt', 'ledig', 'verheiratet', 'geschieden', 'verwitwet') NOT NULL DEFAULT 'unbekannt',
  FDMDJobType enum('selbstaendig', 'unselbstaendig') NOT NULL DEFAULT 'unselbstaendig',
  FDMDEmployer text NOT NULL,
  FDMDForeignAddress varchar(100) NOT NULL,
  FDMDCitizenship varchar(100) NOT NULL,
  FDMDBloodType varchar(50) NOT NULL,
  FDMDComment text NOT NULL,
  FDMDComment2 text NOT NULL,
  FDMDExams varchar(255) NOT NULL,
  FDMDElearning varchar(255) NOT NULL,
  FDMDFastLink varchar(255) NOT NULL,
  FDMDLoginChanged TINYINT( 1 ) NOT NULL ,
  FDMDLoginCount INT( 11 ) NOT NULL ,
  FDMDLoginId VARCHAR( 255 ) NOT NULL,
  FDMDIdentifier varchar(255) NOT NULL,
  PRIMARY KEY (FDMDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_contactdata (
  FDCDInfo text NOT NULL,
  FDCDType enum('unbekannt', 'Handy', 'Handy (SMS)', 'Festnetz', 'Fax', 'eMail' , 'Kennzeichen' , 'Festnetz (Ansprechperson)' , 'Handy (Ansprechperson)' , 'Handy (SMS) (Ansprechperson)' , 'eMail (Ansprechperson)' ) NOT NULL DEFAULT 'unbekannt',
  FDCDComment text NOT NULL,
  FDCDDefault enum('ja', 'nein') NOT NULL DEFAULT 'nein',
  FK_FDMDID bigint(20) unsigned NOT NULL,
  KEY FK_FDMDID (FK_FDMDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_bookingdata (
  FDBDID int(11) NOT NULL AUTO_INCREMENT,
  FDBDClass text NOT NULL,
  FDBDMpaClass text NOT NULL,
  FDBDStartdate date DEFAULT NULL,
  FDBDEnddate date DEFAULT NULL,
  FDBDComment text NOT NULL,
  FK_FDMDID bigint(20) unsigned NOT NULL,
  PRIMARY KEY (FDBDID),
  KEY FK_FDMDID (FK_FDMDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_course (
  FDCID int(11) NOT NULL AUTO_INCREMENT,
  FDCStatus varchar(50) NOT NULL,
  FDCStatusCode tinyint NOT NULL DEFAULT '0',
  FDCName varchar(100) NOT NULL,
  FDCStartdate date DEFAULT NULL,
  FDCComment text NOT NULL,
  FK_FDBDID int(11) NOT NULL,
  PRIMARY KEY (FDCID),
  KEY FK_FDBDID (FK_FDBDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_module (
  FDMName varchar(50) NOT NULL,
  FDMPosition INT( 11 ) NOT NULL,
  FDMDate date DEFAULT NULL,
  FDMStarttime TIME DEFAULT NULL,
  FDMEndtime TIME DEFAULT NULL,
  FDMStatus varchar(50) NOT NULL,
  FDMStatusCode tinyint NOT NULL DEFAULT '0',
  FK_FDCID int(11) NOT NULL,
  KEY FK_FDCID (FK_FDCID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_drivinglesson (
  FDDLNumber smallint unsigned NOT NULL,
  FDDLClass varchar(10) NOT NULL,
  FDDLDate date DEFAULT NULL,
  FDDLStarttime time DEFAULT NULL,
  FDDLEndtime time DEFAULT NULL,
  FDDLTeacher varchar(100) NOT NULL,
  FDDLTeacherFirstname VARCHAR(50) NOT NULL,
  FDDLTeacherLastname VARCHAR(50) NOT NULL,
  FDDLStatus varchar(50) NOT NULL,
  FDDLStatusCode tinyint NOT NULL DEFAULT '0',
  FDDLComment text NOT NULL,
  FDDLStates VARCHAR(100) NOT NULL,
  FK_FDBDID int(11) NOT NULL,
  KEY FK_FDBDID (FK_FDBDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_examdate (
  FDEDClass varchar(10) NOT NULL,
  FDEDType varchar(50) NOT NULL,
  FDEDTypeShort varchar(10) NOT NULL,
  FDEDDateTime DATETIME DEFAULT NULL,
  FDEDStatus enum('unbekannt', 'bestanden', 'nicht bestanden', 'nicht angetreten', 'verschoben', 'abgelaufen' , 'storniert') NOT NULL DEFAULT 'unbekannt',
  FDEDStatusCode tinyint NOT NULL DEFAULT '0',
  FK_FDBDID int(11) NOT NULL,
  KEY FK_FDBDID (FK_FDBDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_administration (
  FDAStartdate date DEFAULT NULL,
  FDAEnddate date DEFAULT NULL,
  FDAText text NOT NULL,
  FDAStatus varchar(50) NOT NULL,
  FDAClass varchar(10) NOT NULL,
  FK_FDBDID int(11) NOT NULL,
  KEY FK_FDBDID (FK_FDBDID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_fd_tutor (
  FDTFirstname varchar(50) NOT NULL,
  FDTLastname varchar(50) NOT NULL,
  FDTBirthday date DEFAULT NULL,
  FDTAddress varchar(100) NOT NULL,
  FDTZIP varchar(10) NOT NULL,
  FDTCity varchar(100) NOT NULL,
  FDTCountry varchar(100) NOT NULL,
  FDTRelationship varchar(100) NOT NULL,
  FK_FDBDID int(11) NOT NULL,
  KEY FK_FDBDID (FK_FDBDID)
) ENGINE=MyISAM;

CREATE TABLE `mc_contentitem_fd_trainer_appointment` (
  FDTAType VARCHAR(100) NOT NULL,
  FDTAPosition int(11) NOT NULL,
  FDTADate date DEFAULT NULL,
  FDTAStarttime time DEFAULT NULL,
  FDTAEndtime time DEFAULT NULL, 
  FDTAComment text NOT NULL,
  FDTAName VARCHAR(255) NOT NULL,
  FDTAShortname VARCHAR(100) NOT NULL,
  FDTANumber int(11) NOT NULL,
  FDTAClass varchar(255) NOT NULL,
  FDTAShortclass varchar(10) NOT NULL,
  FDTAStudent varchar(255) NOT NULL,
  FDTACar varchar(255) NOT NULL,
  FDTAShortcar varchar(255) NOT NULL,
  FDTAStatus varchar(255) NOT NULL,
  FDTAGroup tinyint(1) NOT NULL DEFAULT '0',  
  FK_FDMDID bigint(20) unsigned NOT NULL,
  KEY FK_FDMDID (FK_FDMDID)
) ENGINE=MyISAM;

CREATE TABLE `mc_contentitem_fd_trainer_pdf` (
  FDTPName VARCHAR(255) NOT NULL,
  FDTPLink VARCHAR(255) NOT NULL,
  FK_FDMDID bigint(20) unsigned NOT NULL,
  KEY FK_FDMDID (FK_FDMDID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                          ModuleFastAppointments                            */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('53', 'fastappointments', 'ModuleFastAppointments', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                              Shop mit Attributen                           */
/******************************************************************************/

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES 
('42', 'ContentItemPP', '0', '60'), 
('43', 'ContentItemCP', '0', '61');

INSERT INTO mc_moduletype_frontend 
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES
('49', 'cpcart', 'ModuleCPCart', '0', '0', '0', '0', '0');

ALTER TABLE mc_module_attribute_global ADD AImages TINYINT( 1 ) NOT NULL DEFAULT 0;
ALTER TABLE mc_module_attribute ADD AVCode varchar(10) NOT NULL;
ALTER TABLE mc_module_attribute_global ADD FK_CTID INT NOT NULL;
ALTER TABLE mc_module_attribute_global ADD INDEX ( FK_CTID );
UPDATE mc_module_attribute_global SET FK_CTID = 79 WHERE AVariation > 0;
ALTER TABLE mc_module_attribute_global DROP AVariation;

CREATE TABLE mc_contentitem_pp (
  PPID int(11) NOT NULL AUTO_INCREMENT,
  PPTitle1 varchar(255) NOT NULL,
  PPTitle2 varchar(255) NOT NULL,
  PPTitle3 varchar(255) NOT NULL,
  PPText1 text NOT NULL,
  PPText2 text NOT NULL,
  PPText3 text NOT NULL,
  PPImage1 varchar(150) NOT NULL,
  PPImage2 varchar(150) NOT NULL,
  PPImage3 varchar(150) NOT NULL,
  PPImageTitles text NOT NULL,
  PPPrice float NOT NULL DEFAULT '0',
  PPCasePacks TINYINT(4) NOT NULL DEFAULT '1',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PPID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;


CREATE TABLE mc_contentitem_pp_product (
  PPPID int(11) NOT NULL AUTO_INCREMENT,
  PPPTitle varchar(255) NOT NULL,
  PPPText text NOT NULL,
  PPPImage varchar(150) NOT NULL,
  PPPImageTitles text NOT NULL,
  PPPPosition int(11) NOT NULL,
  PPPPrice float NOT NULL DEFAULT '0',
  PPPNumber varchar(50) NOT NULL,
  PPPCasePacks TINYINT(4) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PPPID),
  UNIQUE KEY FK_CIID_PPPPosition_UN (FK_CIID,PPPPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_pp_attribute_global (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_AID int(11) NOT NULL DEFAULT '0',
  PPAPosition tinyint(1) NOT NULL DEFAULT 0,
  KEY FK_CIID (FK_CIID),
  KEY FK_AID (FK_AID),
  UNIQUE KEY FK_CIID_PPAPosition_UN (FK_CIID, PPAPosition)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_pp_product_attribute (
  FK_PPPID int(11) NOT NULL DEFAULT '0',
  FK_AVID int(11) NOT NULL DEFAULT '0',
  KEY FK_PPPID (FK_PPPID),
  KEY FK_AVID (FK_AVID),
  UNIQUE KEY FK_PPPID_AVID_UN (FK_PPPID, FK_AVID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_pp_option (
  PPOID int(11) NOT NULL AUTO_INCREMENT,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_OPID int(11) NOT NULL DEFAULT '0',
  PPOPrice float NOT NULL DEFAULT 0,
  PPOPosition tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (PPOID),
  KEY FK_CIID (FK_CIID),
  KEY FK_OPID (FK_OPID),
  UNIQUE KEY FK_CIID_OPID_UN (FK_CIID, FK_OPID),
  UNIQUE KEY FK_CIID_PPOPosition_UN (FK_CIID, PPOPosition)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_pp_option_global (
  OPID int(11) NOT NULL AUTO_INCREMENT,
  OPCode varchar(10) NOT NULL,
  OPName varchar(150) NOT NULL,
  OPText text NOT NULL,
  OPImage varchar(150) NOT NULL,
  OPPrice float NOT NULL DEFAULT 0,
  OPGlobal tinyint(1) NOT NULL DEFAULT 0,
  OPProduct tinyint(1) NOT NULL DEFAULT 1,
  OPPosition int (11) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (OPID),
  KEY FK_SID (FK_SID),
  UNIQUE KEY FK_SID_OPPosition_UN (OPPosition, FK_SID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp (
  CPID int(11) NOT NULL AUTO_INCREMENT,
  CPTitle1 varchar(150) NOT NULL,
  CPTitle2 varchar(150) NOT NULL,
  CPTitle3 varchar(150) NOT NULL,
  CPText1 text NOT NULL,
  CPText2 text NOT NULL,
  CPText3 text NOT NULL,
  CPImage1 varchar(150) NOT NULL,
  CPImage2 varchar(150) NOT NULL,
  CPImage3 varchar(150) NOT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order (
  CPOID int(11) NOT NULL AUTO_INCREMENT,
  CPOCreateDateTime datetime NOT NULL,
  CPOChangeDateTime datetime NOT NULL,
  CPOTotalPrice double NOT NULL,
  CPOTotalTax double NOT NULL,
  CPOTotalPriceWithoutTax double NOT NULL,
  CPOTransactionID varchar(255) NOT NULL,
  CPOTransactionNumber varchar(50) NOT NULL,
  CPOTransactionNumberDay int(11) NOT NULL,
  CPOTransactionStatus tinyint(4) NOT NULL DEFAULT '0',
  CPOStatus tinyint(4) NOT NULL DEFAULT '0',
  CPOShippingCost double NOT NULL,
  FK_CPSID int(11) NOT NULL,
  CPOPaymentCost DOUBLE NOT NULL,
  FK_CYID int(11) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (CPOID),
  KEY FK_CPSID (FK_CPSID),
  KEY FK_CYID (FK_CYID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order_customer (
  CPOCID int(11) NOT NULL AUTO_INCREMENT,
  CPOCCompany varchar(150) DEFAULT NULL,
  CPOCPosition varchar(150) DEFAULT NULL,
  FK_FID int(11) NOT NULL DEFAULT '0',
  CPOCTitle varchar(50) DEFAULT NULL,
  CPOCFirstname varchar(150) NOT NULL,
  CPOCLastName varchar(150) NOT NULL,
  CPOCBirthday date DEFAULT NULL,
  CPOCCountry int(11) NOT NULL,
  CPOCZIP varchar(6) NOT NULL,
  CPOCCity varchar(150) NOT NULL,
  CPOCAddress varchar(150) NOT NULL,
  CPOCPhone varchar(50) NOT NULL,
  CPOCEmail varchar(100) NOT NULL,
  CPOCText1 varchar(255) NOT NULL,
  CPOCText2 varchar(255) NOT NULL,
  CPOCText3 varchar(255) NOT NULL,
  CPOCText4 varchar(255) NOT NULL,
  CPOCText5 varchar(255) NOT NULL,
  CPOCCreateDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  CPOCChangeDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPOCID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CPOID (FK_CPOID),
  KEY FK_FID (FK_FID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order_info (
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  FK_CPIID int(11) NOT NULL DEFAULT '0',
  CPOIValue varchar(150) NOT NULL, 
  KEY FK_CPOID (FK_CPOID),
  KEY FK_CPIID (FK_CPIID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order_shipping_address (
  CPOSID int(11) NOT NULL AUTO_INCREMENT,
  CPOSCompany varchar(150) DEFAULT NULL,
  CPOSPosition varchar(150) DEFAULT NULL,
  CPOSFoa varchar(25) NOT NULL,
  CPOSTitle varchar(50) DEFAULT NULL,
  CPOSFirstname varchar(150) NOT NULL,
  CPOSLastName varchar(150) NOT NULL,
  CPOSBirthday date DEFAULT NULL,
  CPOSCountry int(11) NOT NULL,
  CPOSZIP varchar(6) NOT NULL,
  CPOSCity varchar(150) NOT NULL,
  CPOSAddress varchar(150) NOT NULL,
  CPOSPhone varchar(50) NOT NULL,
  CPOSEmail varchar(100) NOT NULL,
  CPOSText1 varchar(255) NOT NULL,
  CPOSText2 varchar(255) NOT NULL,
  CPOSText3 varchar(255) NOT NULL,
  CPOSText4 varchar(255) NOT NULL,
  CPOSText5 varchar(255) NOT NULL,
  CPOSCreateDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  CPOSChangeDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPOSID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CPOID (FK_CPOID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order_item (
  CPOIID int(11) NOT NULL AUTO_INCREMENT,
  CPOITitle varchar(150) NOT NULL,
  CPOINumber varchar(50) NOT NULL,
  CPOIPosition int(11) NOT NULL DEFAULT '0',
  CPOIQuantity int(11) NOT NULL DEFAULT '0',
  CPOISum double NOT NULL,
  CPOIUnitPrice double NOT NULL,
  CPOIProductPrice double NOT NULL,
  FK_PPPID int(11) NOT NULL,
  FK_CPOID int(11) NOT NULL,
  PRIMARY KEY (CPOIID),
  KEY FK_PPPID (FK_PPPID),
  KEY FK_CPOID (FK_CPOID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order_item_option (
  FK_CPOIID int(11) NOT NULL DEFAULT '0',
  CPOIOName varchar(150) NOT NULL,
  CPOIOPrice double NOT NULL,
  CPOIOPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_OPID int(11) NOT NULL DEFAULT '0',
  KEY FK_CPOIID (FK_CPOIID),
  KEY FK_OPID (FK_OPID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_shipment_mode (
  CPSID int(11) NOT NULL AUTO_INCREMENT,
  CPSName varchar(150) NOT NULL,
  CPSPrice float NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (CPSID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_shipment_mode_country (
  FK_CPSID int(11) NOT NULL,
  FK_COID int(11) NOT NULL,
  CPSCPrice float NOT NULL DEFAULT '0',
  KEY FK_CPSID (FK_CPSID),
  KEY FK_COID (FK_COID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_info (
  CPIID int(11) NOT NULL AUTO_INCREMENT,
  CPIName varchar(150) NOT NULL,
  CPIType enum('checkbox', 'text') NOT NULL DEFAULT 'checkbox',
  CPIPosition TINYINT(4) NOT NULL DEFAULT 0,
  CPIDeleted tinyint(1) NOT NULL DEFAULT 0,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (CPIID),
  KEY FK_SID (FK_SID),
  UNIQUE KEY FK_SID_CPIPosition_UN (FK_SID, CPIPosition)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_payment_type (
  CYID int(11) NOT NULL AUTO_INCREMENT,
  CYName varchar(150) NOT NULL,
  CYClass varchar(50) NOT NULL,
  CYText text NOT NULL,
  CYCosts float NOT NULL DEFAULT 0,
  CYPosition INT NOT NULL DEFAULT 0,
  CYNoPayment tinyint(1) NOT NULL DEFAULT 0,
  FK_SID int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (CYID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) 
VALUES ('33', 'shopplusmgmt', 'ModuleShopPlusManagement', '0', '11', '0');

CREATE TABLE mc_module_shopplusmgmt_log (
  LDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  LAction varchar(500) NOT NULL DEFAULT '',
  FK_UID int(11) NOT NULL DEFAULT '0',
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_cartsetting (
  CPCID int(11) NOT NULL AUTO_INCREMENT,
  CPCTitle VARCHAR (50) NOT NULL,
  CPCText TEXT NOT NULL,
  CPCPrice float NOT NULL DEFAULT 0,
  CPCPosition tinyint(2) NOT NULL DEFAULT 0,
  FK_SID int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (CPCID),
  KEY FK_SID (FK_SID),
  UNIQUE KEY FK_SID_CPCPosition_UN (FK_SID, CPCPosition)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_pp_cartsetting (
  FK_CIID int(11) NOT NULL DEFAULT 0,
  FK_CPCID int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (FK_CIID, FK_CPCID),
  KEY FK_CIID (FK_CIID),
  KEY FK_CPCID (FK_CPCID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_cp_order_cartsetting (
  CPOCTitle varchar(150) NOT NULL,
  CPOCPosition int(11) NOT NULL DEFAULT '0',
  CPOCQuantity int(11) NOT NULL DEFAULT '0',
  CPOCSum double NOT NULL,
  CPOCUnitPrice double NOT NULL,
  FK_CPOID int(11) NOT NULL,
  FK_CPCID int(11) NOT NULL,
  KEY FK_CPCID (FK_CPCID),
  KEY FK_CPOID (FK_CPOID)
) ENGINE=MyISAM;

/******************************************************************************/
/* DB-Spalte für Position von ContentItem reicht nur für maximal 127 Elemente */
/******************************************************************************/

ALTER TABLE mc_contentitem CHANGE CPosition CPosition INT NOT NULL DEFAULT '0';

/******************************************************************************/
/*      ModulCustomText - Modul zum Editieren kundenspezifischer Texte        */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('34', 'customtext', 'ModuleCustomText', '0', '12', '0');

CREATE TABLE mc_module_customtext (
  CTID int(11) NOT NULL AUTO_INCREMENT,
  CTTitle varchar(150) NOT NULL,
  CTText text NOT NULL,
  CTName varchar(150) NOT NULL,
  CTDescription text NOT NULL,
  CTPosition tinyint NOT NULL DEFAULT 0,
  FK_SID int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (CTID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                       Cronjob Termin / Log - Liste                         */
/******************************************************************************/

CREATE TABLE mc_cron_log (
  FK_CID int(11) NOT NULL DEFAULT 0,
  CDateTime datetime NOT NULL,
  CText text NOT NULL,
  CStatus tinyint(1) NOT NULL DEFAULT 0,
  KEY (FK_CID)
) ENGINE=MyISAM;

/******************************************************************************/
/*     on / offline stellen von Boxen bei Inhaltstypen mit Subelementen /     */
/*     Startseite                                                             */
/******************************************************************************/

ALTER TABLE mc_contentitem_cb_box ADD CBBDisabled TINYINT( 1 ) NOT NULL DEFAULT 0 AFTER CBBPosition;
ALTER TABLE mc_contentitem_dl_area ADD DADisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER DAPosition;
ALTER TABLE mc_contentitem_fq_question ADD FQQDisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER FQQPosition;
ALTER TABLE mc_contentitem_pp_product ADD PPPDisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER PPPNumber;
ALTER TABLE mc_contentitem_qs_statement ADD QSDisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER QSPosition;
ALTER TABLE mc_contentitem_ts_block ADD TBDisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER TBPosition;
ALTER TABLE mc_contentitem_ca_area ADD CAADisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER CAALink;
ALTER TABLE mc_contentitem_ca_area_box ADD CAABDisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER CAABLink;
ALTER TABLE mc_module_siteindex_compendium_area ADD SADisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER SAPosition;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD SBDisabled TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER SBPositionLocked;

/******************************************************************************/
/*              Verknüpfung von Attributen / Attributgruppen                  */
/******************************************************************************/

CREATE TABLE mc_module_attribute_global_link_group (
  AGID int(11) NOT NULL AUTO_INCREMENT,
  AGPosition tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (AGID),
  UNIQUE KEY AGID_AGPosition_UN (AGID, AGPosition)
) ENGINE=MyISAM;

CREATE TABLE mc_module_attribute_link_group (
  ALID int(11) NOT NULL AUTO_INCREMENT,
  ALPosition tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (ALID),
  UNIQUE KEY ALID_AGPosition_UN (ALID, ALPosition)
) ENGINE=MyISAM;

ALTER TABLE mc_module_attribute_global ADD FK_AGID INT NOT NULL DEFAULT '0' AFTER FK_CTID ,
ADD INDEX ( FK_AGID );
ALTER TABLE mc_module_attribute ADD FK_ALID INT NOT NULL DEFAULT '0' AFTER AVCode,
ADD INDEX ( FK_ALID );

ALTER TABLE mc_module_attribute ADD INDEX ( FK_AID ); 

/******************************************************************************/
/*                     Allgemeine Datenbankkorrekturen                        */
/******************************************************************************/

ALTER TABLE mc_comments ADD INDEX ( FK_FUID );
ALTER TABLE mc_contentitem_cb_box CHANGE FK_CIID FK_CIID INT( 11 ) NOT NULL DEFAULT '0';

/******************************************************************************/
/*            FrontendUser - "Anrede" + "UID" Feld ergänzen                   */
/******************************************************************************/

ALTER TABLE mc_frontend_user ADD FUUID VARCHAR( 25 ) NOT NULL AFTER FUNewsletter;

/******************************************************************************/
/*                            ImageMap Modul                                  */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES (50, 'imagemap', 'ModuleImageMap', '0', '0', '0', '0', '0');

/***********************************************************************************************/
/* Entfernen / Hinzufügen zu weiteren Benutzergruppen von registrierten FE-Benutzern über Link */
/***********************************************************************************************/

ALTER TABLE mc_frontend_user ADD FUActivationCode VARCHAR( 50 ) NOT NULL AFTER FUShowProfile;

/******************************************************************************/
/*                  ModuleSidebox - Externe Links                             */
/******************************************************************************/

ALTER TABLE mc_module_sidebox ADD BUrl VARCHAR( 255 ) NOT NULL AFTER BNoRandom;

/******************************************************************************/
/*                       Geschützte Downloads                                 */
/******************************************************************************/

ALTER TABLE mc_centralfile ADD CFProtected TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER CFShowAlways;

/******************************************************************************/
/*            Lieferkosten pro Produkt (Shop mit Attributen)                  */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp ADD PPShippingCosts FLOAT NOT NULL DEFAULT '0' AFTER PPCasePacks;
ALTER TABLE mc_contentitem_pp_product ADD PPPShippingCosts FLOAT NOT NULL DEFAULT '0' AFTER PPPCasePacks;

CREATE TABLE mc_contentitem_cp_preferences (
  CPPID int(11) NOT NULL AUTO_INCREMENT,
  CPPName varchar(25) NOT NULL DEFAULT '',
  CPPValue varchar(25) NOT NULL DEFAULT '',
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (CPPID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

INSERT INTO mc_contentitem_cp_preferences (CPPID, CPPName, CPPValue, FK_SID) VALUES
(1, 'cp_shipping_costs_maximum', '190', 1),
(2, 'cp_shipping_costs_free', '750', 1);

ALTER TABLE mc_contentitem_cp_preferences CHANGE CPPName CPPName VARCHAR( 50 ) NOT NULL;

INSERT INTO mc_contentitem_cp_preferences (CPPID, CPPName, CPPValue, FK_SID) VALUES
(3, 'cp_shipping_costs_percentage', '10', 1);

/******************************************************************************/
/*                       Lead Mgmt                                            */
/******************************************************************************/

CREATE TABLE mc_campaign (
  `CGID` int(11) NOT NULL AUTO_INCREMENT,
  `CGName` varchar(150) NOT NULL DEFAULT '',
  `CGPosition` tinyint(4) NOT NULL DEFAULT '0',
  `CGStatus` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 Active; 2 Archived; 3 Deleted;',
  `FK_CGTID` int(11) DEFAULT NULL,
  `FK_SID` int(11) DEFAULT NULL,
  PRIMARY KEY (`CGID`),
  KEY FK_CGTID (FK_CGTID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM  AUTO_INCREMENT=1;

CREATE TABLE mc_campaign_contentitem (
  `CGCID` int(11) NOT NULL AUTO_INCREMENT,
  `FK_CIID` int(11) NOT NULL,
  `FK_CGID` int(11) NOT NULL,
  `CGCCampaignRecipient` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`CGCID`),
  KEY FK_CIID (FK_CIID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM  AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mc_campaign_type` (
  `CGTID` int(11) NOT NULL AUTO_INCREMENT,
  `CGTName` varchar(150) NOT NULL DEFAULT '',
  `CGTPosition` tinyint(4) NOT NULL DEFAULT '0',
  `FK_SID` int(11) DEFAULT NULL,
  PRIMARY KEY (`CGTID`)
) ENGINE=MyISAM  AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mc_campaign_data` (
  `CGDID` int(11) NOT NULL AUTO_INCREMENT,
  `CGDType` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 Text; 2 Textarea; 3 Combobox; 4 Checkbox; 5 Checkboxgroup; 6 Radiobutton;',
  `CGDName` varchar(255) NOT NULL DEFAULT '',
  `CGDValue` text NOT NULL,
  `CGDRequired` tinyint(1) NOT NULL DEFAULT '0',
  `CGDDependency` int(11) DEFAULT NULL,
  `CGDPosition` tinyint(2) NOT NULL DEFAULT '0',
  `CGDValidate` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1 Number; 2 Mail; 3 Date; 4 Birthday; ',
  `CGDPredefined` tinyint(2) NOT NULL DEFAULT '0' COMMENt '1 Country; 2 Date;',
  `CGDPrechecked` varchar(50) NOT NULL DEFAULT '',
  `CGDMinLength` smallint(4) NOT NULL DEFAULT '0',
  `CGDMaxLength` smallint(4) NOT NULL DEFAULT '0',
  `CGDMinValue` smallint(4) NOT NULL DEFAULT '0',
  `CGDMaxValue` mediumint(4) NOT NULL DEFAULT '0',
  `CGDDisabled` tinyint(1) NOT NULL DEFAULT '0',
  `CGDClientData` tinyint(1) NOT NULL DEFAULT '0',
  `FK_CGID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`CGDID`),
  KEY `FK_CGID` (`FK_CGID`)
) ENGINE=MyISAM  AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mc_campaign_lead_data` (
  `FK_CGLID` int(11) NOT NULL DEFAULT '0',
  `FK_CGDID` int(11) NOT NULL DEFAULT '0',
  `CGLDValue` text NOT NULL,
  KEY `FK_CGLID` (`FK_CGLID`),
  KEY `FK_CGDID` (`FK_CGDID`)
) ENGINE=MyISAM ;

CREATE TABLE IF NOT EXISTS `mc_campaign_lead` (
  `CGLID` int(11) NOT NULL AUTO_INCREMENT,
  `CGLDocumentsEMail` tinyint(1) NOT NULL DEFAULT '0',
  `CGLDocumentsPost` tinyint(1) NOT NULL DEFAULT '0',
  `CGLAppointment` tinyint(1) NOT NULL DEFAULT '0',
  `CGLDataOrigin` varchar(255) NOT NULL DEFAULT '',
  `CGLDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `FK_CID` int(11) NOT NULL DEFAULT '0',
  `FK_FUID` int(11) NOT NULL DEFAULT '0',
  `FK_CGSID` int(11) NOT NULL DEFAULT '0',
  `FK_CGID` int(11) DEFAULT NULL,
  `FK_CGCCID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`CGLID`),
  KEY `FK_CID` (`FK_CID`),
  KEY `FK_FUID` (`FK_FUID`),
  KEY `FK_CGSID` (`FK_CGSID`),
  KEY `FK_CGID` (`FK_CGID`),
  KEY `FK_CGCCID` (`FK_CGCCID`)
) ENGINE=MyISAM  AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mc_campaign_lead_appointment` (
  `CGLAID` int(11) NOT NULL AUTO_INCREMENT,
  `CGLACreateDateTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `CGLAChangeDateTime` datetime DEFAULT NULL,
  `CGLADateTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `CGLATitle` varchar(255) NOT NULL DEFAULT '',
  `CGLAText` text,
  `CGLAStatus` enum('Open','Finished') NOT NULL DEFAULT 'Open',
  `FK_UID` int(11) NOT NULL DEFAULT '0',
  `FK_CGLID` int(11) NOT NULL DEFAULT '0',
  `FK_CGID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`CGLAID`),
  KEY `FK_UID` (`FK_UID`),
  KEY `FK_CGLID` (`FK_CGLID`),
  KEY `FK_CGID` (`FK_CGID`)
) ENGINE=MyISAM   AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mc_campaign_lead_manipulated_log` (
  `CGLMLDateTime` datetime DEFAULT NULL,
  `FK_CGLID` int(11) DEFAULT NULL,
  `FK_UID` int(11) DEFAULT NULL,
  KEY FK_CGLID (FK_CGLID),
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM  ;

CREATE TABLE IF NOT EXISTS `mc_campaign_lead_status` (
  `FK_CGLID` int(11) NOT NULL DEFAULT '0',
  `FK_CGID` int(11) NOT NULL DEFAULT '0',
  `CGLSDateTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `CGLSText` text NOT NULL,
  `FK_CGSID` int(11) NOT NULL DEFAULT '1',
  `FK_UID` int(11) NOT NULL DEFAULT '0',
  KEY `FK_CGLID` (`FK_CGLID`),
  KEY `FK_CGID` (`FK_CGID`),
  KEY `FK_CGSID` (`FK_CGSID`),
  KEY `FK_UID` (`FK_UID`)
) ENGINE=MyISAM ;

CREATE TABLE IF NOT EXISTS `mc_campaign_status` (
  `CGSID` int(11) NOT NULL AUTO_INCREMENT,
  `CGSName` varchar(150) NOT NULL DEFAULT '',
  `CGSPosition` int(11) NOT NULL DEFAULT '0',
  `FK_CGID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`CGSID`),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM   AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mc_campaign_competing_company` (
  `CGCCID` int(11) NOT NULL AUTO_INCREMENT,
  `CGCCValue` varchar(150) NOT NULL DEFAULT '',
  `CGCCPosition` tinyint(2) NOT NULL DEFAULT '0',
  `FK_CGID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`CGCCID`),
  KEY `FK_CGID` (`FK_CGID`)
) ENGINE=MyISAM   AUTO_INCREMENT=1;

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`)
VALUES ('35', 'leadmgmt', 'ModuleLeadManagement', '0', '54', '0'),
       ('36', 'leadmgmtall', 'ModuleLeadManagementAll', '0', '0', '0'),
       ('37', 'form', 'ModuleForm', '0', '0', '0');

INSERT INTO `mc_moduletype_frontend` (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('51', 'form', 'ModuleForm', '0', '0', '0', '0', '0');

ALTER TABLE `mc_client` ADD `FK_UID` INT( 11 ) NOT NULL DEFAULT '0' AFTER `CChangeDateTime`; 

/******************************************************************************/
/*                       Lead Mgmt Kontaktformular                            */
/******************************************************************************/

INSERT INTO `mc_campaign_type` (`CGTID`, `CGTName`, `CGTPosition`, `FK_SID`) VALUES
(1, 'Diverse', 1, 1);

INSERT INTO `mc_campaign` (`CGID`, `CGName`, `CGPosition`, `CGStatus`, `FK_CGTID`, `FK_SID`) VALUES
(1, 'Kontaktformular', 1, 1, 1, 1);

INSERT INTO `mc_campaign_data` (`CGDID`, `CGDType`, `CGDName`, `CGDValue`, `CGDRequired`, `CGDDependency`, `CGDPosition`, `CGDValidate`, `CGDPredefined`, `CGDPrechecked`, `CGDMinLength`, `CGDMaxLength`, `CGDMinValue`, `CGDMaxValue`, `CGDDisabled`, `CGDClientData`, `FK_CGID`) VALUES
(1, 3, 'Anrede', 'Frau$Herr$Firma', 1, NULL, 3, 0, 0, '', 0, 0, 0, 0, 0, 1, 1),
(2, 1, 'Titel', '', 0, NULL, 4, 0, 0, '', 0, 50, 0, 0, 0, 1, 1),
(3, 1, 'Vorname', '', 1, NULL, 5, 0, 0, '', 0, 150, 0, 0, 0, 1, 1),
(4, 1, 'Nachname', '', 1, NULL, 6, 0, 0, '', 2, 150, 0, 0, 0, 1, 1),
(5, 1, 'Straße', '', 1, NULL, 11, 0, 0, '', 0, 150, 0, 0, 0, 1, 1),
(6, 3, 'Land', '', 1, NULL, 8, 0, 1, '', 0, 0, 0, 0, 0, 1, 1),
(7, 1, 'PLZ', '', 1, NULL, 9, 1, 0, '', 0, 0, 0, 0, 0, 1, 1),
(8, 1, 'Ort', '', 1, NULL, 10, 0, 0, '', 0, 150, 0, 0, 0, 1, 1),
(9, 1, 'E-Mail Adresse', '', 1, NULL, 13, 2, 0, '', 0, 0, 0, 0, 0, 1, 1),
(10, 1, 'Telefonnummer', '', 1, NULL, 12, 0, 0, '', 0, 0, 0, 0, 0, 1, 1),
(11, 5, 'Wofür interessieren Sie sich?', 'Produkt 1$Produkt 2$Produkt 3$Produkt 4$Produkt 5$Produkt 6', 0, NULL, 15, 0, 0, '1$2', 0, 0, 0, 0, 0, 0, 1),
(12, 2, 'Nachricht', '', 0, NULL, 16, 0, 0, '', 0, 0, 0, 0, 0, 0, 1),
(13, 4, 'Ich möchte weitere Informationen erhalten', '', 0, NULL, 14, 0, 0, '', 0, 0, 0, 0, 0, 1, 1);

INSERT INTO `mc_campaign_status` (`CGSID`, `CGSName`, `CGSPosition`, `FK_CGID`) VALUES
(1, 'Bearbeitung offen', 1, 0),
(2, 'Kontakt aufgenommen', 2, 0),
(3, 'Kontakt abgeschlossen', 3, 0);

/******************************************************************************/
/*                  BE: Modul Multimedia Bibliothek                           */
/******************************************************************************/

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`)
VALUES ('38', 'medialibrary', 'ModuleMultimediaLibrary', '0', '55', '0');

CREATE TABLE mc_module_medialibrary (
  MID int(11) NOT NULL AUTO_INCREMENT,
  MTitle1 varchar(150) DEFAULT NULL,
  MTitle2 varchar(150) DEFAULT NULL,
  MTitle3 varchar(150) DEFAULT NULL,
  MText1 text,
  MText2 text,
  MText3 text,
  MImage1 varchar(150) DEFAULT NULL,
  MImage2 varchar(150) DEFAULT NULL,
  MImage3 varchar(150) DEFAULT NULL,
  MVideoType1 varchar(50) NOT NULL,
  MVideo1 varchar(200) NOT NULL,
  MVideoType2 varchar(50) NOT NULL,
  MVideo2 varchar(200) NOT NULL,
  MVideoType3 varchar(50) NOT NULL,
  MVideo3 varchar(200) NOT NULL,
  MPosition tinyint(11) NOT NULL DEFAULT '0',
  MUrl varchar(255) NOT NULL,
  MImageTitles text NULL,
  FK_MCID  int(11) NOT NULL,
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (MID),
  KEY FK_MCID (FK_MCID),
  KEY FK_CIID (FK_CIID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM  AUTO_INCREMENT=1 ;

CREATE TABLE mc_module_medialibrary_category (
  MCID int(11) NOT NULL AUTO_INCREMENT,
  MCTitle varchar(100) NOT NULL,
  MCPosition INT( 11 ) NOT NULL,
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (MCID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM  AUTO_INCREMENT=1 ;

CREATE TABLE mc_module_medialibrary_assignment (
  FK_MID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  MABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_MID,FK_CIID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM ;

/******************************************************************************/
/*                  FE: Modul Multimedia Bibliothek                           */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser) VALUES
(52, 'mediasidebox', 'ModuleMultimediaSidebox', 0, 0, 0, 0, 0);

/******************************************************************************/
/*                  ContentItem Multimedia Bibliothek                         */
/******************************************************************************/

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES
(44, 'ContentItemMB', 0, 62);

CREATE TABLE mc_contentitem_mb (
  MBID int(11) NOT NULL AUTO_INCREMENT,
  MBTitle1 varchar(150) NOT NULL,
  MBTitle2 varchar(150) NOT NULL,
  MBTitle3 varchar(150) NOT NULL,
  MBImage1 varchar(150) NOT NULL,
  MBImage2 varchar(150) NOT NULL,
  MBImage3 varchar(150) NOT NULL,
  MBImageTitles text,
  MBText1 text,
  MBText2 text,
  MBText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (MBID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM  AUTO_INCREMENT=1 ;
