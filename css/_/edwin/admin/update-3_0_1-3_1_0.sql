/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * Die Standardwerte für Konfigurationsvariablen werden nun in den Files unter
 * includes/config.defaults.php gesetzt. Sollten für das Kundenprojekt
 * Entwicklungen gemacht worden sein, sollten alle Konfigurationsvariablen aus
 * den Klassenfiles entfernt und in das oben genannte PHP File in den
 * entsprechnden Bereich "PROJECTS" verschoben werden.
 *
 * Folgende Konfigurationsvariablen müssen durch entsprechende neue Variablen
 * ersetzt werden:
 * - c_be_datetime_format         => be_datetime_format
 * - c_be_comment_datetime_format => be_comment_datetime_format
 * - c_be_results_per_page        => be_results_per_page
 *
 * Die Navigation der Variationsebene wird nun nicht mehr im Template
 * main_nav_variation.tpl erzeugt, stattdessen muss das Template ContentItemVA.tpl
 * bzw. ContentItemVA<x>.tpl für Custom Templates verwendet werden.
 *
 * q2e_jquery.src.js wurde um einen ajaxSetup Block erweitert. Dieser sollte
 * bei einem Update nachgezogen werden (http://forge.q2e.at/mantis/view.php?id=1227).
 *
 * ContentItemBG: c_bg_image_before_title und c_bg_image_after_title geben nun den
 * speziellen Titel aus (vorher: Untertitel). Fuer den Untertitel muss nun die Variable
 * c_bg_image_before_subtitle bzw. c_bg_image_after_subtitle verwendet werden.
 * Weitere Aenderungen:
 * c_bg_text1       => c_bg_text1_variation (c_bg_text1 gibt nun immer den ersten Inhaltstext aus)
 * c_bg_text1_plain => c_bg_text1_variation_plain (c_bg_text1_plain gibt es nicht mehr)
 * c_bg_title       => c_bg_title_variation (c_bg_title gibt nun immer den Inhaltstitel aus)
 * c_bg_title_plain => c_bg_title_variation_plain (c_bg_title_plain gibt es nicht mehr)
 * Am Frontend im ContentItemBG.tpl ueberpruefen!
 *
 * Nach diesem Update Skript, sollte die Dateigröße der Downloads aktualisiert
 * werden: edwin/manage_stuff.php?site=1&do=files_update_filesize
 * damit diese für bereits hochgeladene Downloads korrekt in der Datenbank
 * hinterlegt ist ( Dateigröße bei Files in DB mitspeichern )
 *
 * Aufgrund von Änderungen bei der Erzeugung bzw. Ausgabe von Kurztexten müssen
 * folgende Konfigurationsvariablen angepasst werden:
 * [BE]
 * - ci_be_shorttext_maxlength // Kurztexte für die Blogebene werden mit der hier
 *                             // definierten Länge in die Datenbank gespeichert
 *                             // und am Frontent 1 zu 1 ausgegeben
 * - ci_shorttext_maxlength // Der allgemeine Kurztext für Inhalte wird mit der hier
 *                          // definierten Länge in die Datenbank gespeichert
 *                          // und am Frontent 1 zu 1 ausgegeben
 * [FE]
 * - ci_shorttext_maxlength wurde entfernt ( existiert nur noch am Backend )
 * // neue Variablen für Ebenen
 * - ar_shorttext_maxlength
 * - ib_shorttext_maxlength
 * - ip_shorttext_maxlength
 * // bei Suche wurde bis jetzt ci_shorttext_maxlength verwendet und entfernt
 * - se_shorttext_maxlength
 * Die Variablen *_shorttext_aftertext haben nun standardmäßig ALLE den Wert von
 * $_CONFIG['m_shorttext_aftertext'] = ' ...'; und können auch über diesen
 * geändert werden.
 *
 * ModuleCustomText custom_config.css Styles hinzugefügt. Wenn das Modul verwendet
 * wird, müssen die Display Klassen ergänzt werden.
 *
 * TinyMCE Update auf Version 3.4.9 mit jQuery
 * - Anpassungen im main.tpl
 *
 * Im Zuge der Modernisierung des Mitarbeitermoduls wurden (werden) aus der Tabelle
 * mc_module_employee die Spalten EShowOnSites und ERank geloescht.
 * Dies sollte jedoch kein Prob sein da dieses Modul, sowie der InhaltstypIM (bei dem
 * die Aenderungen nicht mitgezogen wurden), nicht mehr in Verwendung sind.
 *
 * Ab dieser Version hat das Startseitenmodul pro Bereich und pro Box ein eigenes
 * Formular um Probleme mit max_file_uploads zu verhindern. Das bedeutet dass der
 * Aktionen-Speichern-Button ab jetzt nur mehr den allgemeinen Bereich
 * des Startseitenbereiches speichert/aktualisiert.
 *
 * ModuleForm ( Leadmanagement ) speichert standardmäßig nun für die
 * "Datenherkunft" zum Lead den Pfad des ContentItems ( Seite ) auf der das
 * Formular abgeschickt wurde. Sollte wie bisher der Titel der Elternseite
 * verwendet werden muss die Konfigurationsvariable
 * $_CONFIG['fo_origin_use_path'] = false; gesetzt werden.
 *
 * Die Bildersuche wird nun standardmäßig auf Basis des Inhaltes von ContentItems
 * durchgeführt. Sollte Bilduntertitel und Tags verfügbar sein, kann die Variable
 * $_CONFIG['se_use_text_for_image_search'] = false; gesetzt werden.
 *
 *
 * [/INFO]
 */

/******************************************************************************/
/*                    Mehrfache Templates pro Contenttyp                      */
/******************************************************************************/

ALTER TABLE mc_contenttype ADD FK_CTID INT NOT NULL DEFAULT '0',
ADD CTTemplate TINYINT NOT NULL DEFAULT '0',
ADD INDEX ( FK_CTID );

ALTER TABLE mc_contenttype
CHANGE CTPosition CTPosition INT NOT NULL DEFAULT '0';

ALTER TABLE mc_contenttype ADD CTPageType TINYINT NOT NULL DEFAULT '1';

UPDATE mc_contenttype SET CTPosition = CTPosition + 90 WHERE CTPosition > 9;

UPDATE mc_contenttype SET CTPageType = 90 WHERE CTPosition < 100;

/******************************************************************************/
/*        Lead Management - Vererbung von Kampagnenkonfigurationen            */
/******************************************************************************/

ALTER TABLE mc_campaign ADD FK_CGID INT NOT NULL DEFAULT '0',
ADD INDEX ( FK_CGID );

/******************************************************************************/
/*                    ContentItemCM - Kontaktformular                         */
/******************************************************************************/

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition, FK_CTID, CTTemplate)
VALUES ('55', 'ContentItemCM', '0', '154', '0', '0');

CREATE TABLE IF NOT EXISTS mc_contentitem_cm (
  CMID int(11) NOT NULL AUTO_INCREMENT,
  CMTitle1 varchar(150) NOT NULL,
  CMTitle2 varchar(150) NOT NULL,
  CMTitle3 varchar(150) NOT NULL,
  CMImage1 varchar(150) NOT NULL,
  CMImage2 varchar(150) NOT NULL,
  CMImage3 varchar(150) NOT NULL,
  CMImageTitles text,
  CMText1 text,
  CMText2 text,
  CMText3 text,
  FK_CGID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CMID),
  KEY FK_CID (FK_CIID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                        CONFIG File für Portale                             */
/******************************************************************************/

ALTER TABLE mc_site
ADD SUrlInternal VARCHAR( 128 ) NOT NULL,
ADD SUrlExternal VARCHAR( 128 ) NOT NULL,
ADD SPathExternal VARCHAR( 128 ) NOT NULL;

/******************************************************************************/
/*                 Dateigröße bei Files in DB mitspeichern                    */
/******************************************************************************/

ALTER TABLE mc_centralfile ADD CFSize INT NOT NULL DEFAULT '0' AFTER CFProtected ;
ALTER TABLE mc_contentitem_dl_area_file ADD DFSize INT NOT NULL DEFAULT '0' AFTER DFPosition;
ALTER TABLE mc_file ADD FSize INT NOT NULL DEFAULT '0' AFTER FPosition;

/******************************************************************************/
/*   Sperrung von Edwin BE Accounts = eigener grafischer Hinweis bei Login    */
/******************************************************************************/

ALTER TABLE mc_user ADD UBlocked TINYINT NOT NULL DEFAULT '0',
ADD UBlockedMessage TEXT NOT NULL DEFAULT '';

/******************************************************************************/
/*          Verwaltung für Footer / Kontakt Texte auf Webseite                */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('56', 'customtext', 'ModuleCustomText', '0', '0', '0', '0', '0');
ALTER TABLE mc_module_customtext ADD CTTemplateVariables VARCHAR( 255 ) NOT NULL AFTER CTDescription;

/******************************************************************************/
/*                 Kampagnen Formulare an Sideboxen anhaengen                 */
/******************************************************************************/

ALTER TABLE mc_module_sidebox ADD FK_CGAID INT NOT NULL AFTER BUrl, ADD INDEX ( FK_CGAID );

CREATE TABLE IF NOT EXISTS mc_campaign_attached (
  CGAID int(11) NOT NULL AUTO_INCREMENT,
  CGAAdditionalDataOrigin varchar(100) NOT NULL,
  CGARecipients varchar(255) NOT NULL,
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGAID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                             ContentItemRL                                  */
/******************************************************************************/

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (
  '46', 'ContentItemRL', '0', '155'
);

CREATE TABLE IF NOT EXISTS `mc_contentitem_rl` (
  `RLID` int(11) NOT NULL AUTO_INCREMENT,
  `RLTitle1` varchar(150) NOT NULL,
  `RLTitle2` varchar(150) NOT NULL,
  `RLTitle3` varchar(150) NOT NULL,
  `RLImage1` varchar(150) NOT NULL,
  `RLImage2` varchar(150) NOT NULL,
  `RLImage3` varchar(150) NOT NULL,
  `RLImageTitles` text,
  `RLText1` text,
  `RLText2` text,
  `RLText3` text,
  `RLTplType` tinyint NOT NULL,
  `FK_RCID` int(11) NOT NULL,
  `FK_CIID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`RLID`),
  KEY `FK_CID` (`FK_CIID`),
  KEY `FK_RCID` (`FK_RCID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mc_module_reseller_category` (
  `RCID` int(11) NOT NULL AUTO_INCREMENT,
  `RCName` varchar(150) NOT NULL,
   PRIMARY KEY (`RCID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `mc_module_reseller_category_assignation` (
  `FK_RID` int(11) NOT NULL,
  `FK_RCID` int(11) NOT NULL,
   KEY `FK_RID` (`FK_RID`),
   KEY `FK_RCID` (`FK_RCID`)
) ENGINE=MyISAM ;

/******************************************************************************/
/*                       Modernisierung ModuleEmployee                        */
/******************************************************************************/

ALTER TABLE `mc_module_employee` ADD `ETitle1` varchar(150) DEFAULT NULL AFTER `EID`;
ALTER TABLE `mc_module_employee` ADD `ETitle2` varchar(150) DEFAULT NULL AFTER `ETitle1`;
ALTER TABLE `mc_module_employee` ADD `ETitle3` varchar(150) DEFAULT NULL AFTER `ETitle2`;
ALTER TABLE `mc_module_employee` ADD `EText1` text AFTER `ETitle3`;
ALTER TABLE `mc_module_employee` ADD `EText2` text AFTER `EText1`;
ALTER TABLE `mc_module_employee` ADD `EText3` text AFTER `EText2`;
ALTER TABLE `mc_module_employee` CHANGE `EImage` `EImage1` VARCHAR( 150 ) NULL;
ALTER TABLE `mc_module_employee` MODIFY EImage1 VARCHAR( 150 ) NULL AFTER `EText3`;
ALTER TABLE `mc_module_employee` ADD `EImage2` varchar(150) DEFAULT NULL AFTER `EImage1`;
ALTER TABLE `mc_module_employee` ADD `EImage3` varchar(150) DEFAULT NULL AFTER `EImage2`;
ALTER TABLE `mc_module_employee` MODIFY EPosition tinyint(11) NOT NULL DEFAULT '0' AFTER `EImage3`;
ALTER TABLE `mc_module_employee` ADD `ENoRandom` tinyint(4) NOT NULL DEFAULT '0' AFTER `EPosition`;
ALTER TABLE `mc_module_employee` ADD `EUrl` varchar(255) NOT NULL AFTER `ENoRandom`;
ALTER TABLE `mc_module_employee` CHANGE `EName` `EFirstname` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ELastname` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EStaffNumber` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `FK_FID` INT( 11 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ETitle` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ECompany` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EInitials` VARCHAR( 10 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ECountry` INT( 11 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EZIP` VARCHAR( 6 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ECity` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EAddress` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EPhoneDirectDial` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EFax` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EFaxDirectDial` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EMobilePhone` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EMobilePhoneDirectDial` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ERoom` VARCHAR( 50 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EJobTitle` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EFunction` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `ESpecialism` VARCHAR( 150 ) NOT NULL;
ALTER TABLE `mc_module_employee` ADD `EHourlyRate` DOUBLE NOT NULL;
ALTER TABLE `mc_module_employee` MODIFY EPhone varchar(50) NOT NULL AFTER `EAddress`;
ALTER TABLE `mc_module_employee` MODIFY EEmail varchar(150) NOT NULL AFTER `EMobilePhoneDirectDial`;
ALTER TABLE `mc_module_employee` ADD `FK_ETID` INT NOT NULL;
ALTER TABLE `mc_module_employee` ADD `FK_CGAID` INT NOT NULL;
ALTER TABLE `mc_module_employee` ADD `FK_CIID` int(11) DEFAULT NULL;
ALTER TABLE `mc_module_employee` ADD `FK_SID` int(11) NOT NULL;
ALTER TABLE `mc_module_employee` ADD INDEX ( `FK_ETID` );
ALTER TABLE `mc_module_employee` ADD INDEX ( `FK_CGAID` );
ALTER TABLE `mc_module_employee` ADD INDEX ( `FK_CIID` );
ALTER TABLE `mc_module_employee` ADD INDEX ( `FK_SID` );
ALTER TABLE `mc_module_employee` DROP `EShowOnSites`;
ALTER TABLE `mc_module_employee` DROP `ERank`;

CREATE TABLE IF NOT EXISTS `mc_module_employee_assignment` (
  `FK_EID` int(11) NOT NULL,
  `FK_CIID` int(11) NOT NULL,
  `EABeneath` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`FK_EID`,`FK_CIID`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `mc_module_employee_type` (
  `ETID` int(11) NOT NULL AUTO_INCREMENT,
  `ETTitle` varchar(100) NOT NULL,
  `ETPosition` int(11) NOT NULL,
  `FK_SID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ETID`),
  KEY `FK_SID` (`FK_SID`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES (57, 'employeebox', 'ModuleEmployeeBox', 0, 0, 0, 0, 0);


/******************************************************************************/
/*               Datei-Uploads bei Leadmanagement Formularen                  */
/******************************************************************************/

ALTER TABLE mc_campaign_data
ADD CGDFiletypes VARCHAR( 128 ) NOT NULL AFTER CGDMaxValue,
ADD CGDFilesize INT NOT NULL DEFAULT '0' AFTER CGDFiletypes;

ALTER TABLE mc_campaign_data
MODIFY CGDType tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 Text; 2 Textarea; 3 Combobox; 4 Checkbox; 5 Checkboxgroup; 6 Radiobutton; 7 Upload;';

/******************************************************************************/
/*                                    ModuleNews                              */
/******************************************************************************/

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`)
VALUES (41, 'news', 'ModuleNews', 0, 57, 0);

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES (58, 'news', 'ModuleNews', 0, 0, 0, 0, 0);

CREATE TABLE mc_module_news_category (
  NWCID int(11) NOT NULL AUTO_INCREMENT,
  NWCTitle varchar(255) NOT NULL,
  NWCIdentifier varchar(255) NOT NULL,
  NWCPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (NWCID),
  KEY FK_SID (FK_SID),
  UNIQUE KEY NWCID_NWCPosition_UN (NWCID, NWCPosition)
) ENGINE=MyISAM;

CREATE TABLE mc_module_news (
  NWID int(11) NOT NULL AUTO_INCREMENT,
  NWTitle varchar(255) NOT NULL,
  NWText text,
  NWCreateDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  NWChangeDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FK_NWCID int(11) DEFAULT NULL,
  FK_UID int(11) DEFAULT NULL,
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (NWID),
  KEY FK_NWCID (FK_NWCID),
  KEY FK_UID (FK_UID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;
