/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * Es existiert ein PHP-Update-Script, welches nach diesem SQL-Update-Script
 * ausgeführt werden muss. Dafür muss man über den Browser
 * /edwin/admin/update-3_2_0-3_3_0.php aufrufen. Das Skript führt folgende
 * Aktionen durch:
 * 1. Erzeugt ein SQL Statement zur veröffentlichung aller Kommentare mit bereits
 *    veröffentlichten Antworten
 * 2. Aktualisiert die Länderliste der Datenbank mit Telefon-Vorwahl-Codes
 * Zuätzlich: Soll das Land Kosovo in der Länderliste auch zur Verwendung kommen,
 *            dann kann das manage_stuff dazu verwendet werden um die Position
 *            bzw. Namen anzupassen:
 *            edwin/manage_stuff.php?do=countries_export&site=1
 *            edwin/manage_stuff.php?do=countries_import&site=1
 *
 * Shop Plus: Zahlungsarten können nun für unterschiedliche Länder definiert
 * werden ( Preis / Land ). Nach diesem Update Skript müssen alle Zahlungsarten
 * im System für alle Länder aktiviert werden:
 * > edwin/index.php?action=mod_shopplusmgmt&action2=pay
 * Damit wird gewährleistet, dass die Zahlungsarten wie bisher landunabhängig
 * zur Verfügung stehen.
 *
 * ContentItemBG erhaelt zusaetzlich 2 Titel. Aus diesem Grund werden Templatevariablen
 * die beim Titel noch keinen numerischen Suffix besitzen als deprecated gekennzeichnet.
 * Backend:
 * bg_title -> bg_title1
 * c_bg_title -> c_bg_title1
 * CSS: display_bg_title_label -> display_bg_title1_label
 * Frontend:
 * c_bg_title -> c_bg_title1
 *
 * Das "global option" wurde von Shop Plus komplett entfernt, da es bei
 * Kundenprojekten noch nie verwendet wurde. Es muss dennoch überprüft werden,
 * ob in Templates, wo der Warenkorb ausgegeben wird, auch alle Template
 * Variablen, IFs und LOOPs aus dem Template entfernt wurden:
 * - ContentItemCP
 * - ModuleCPCart
 * - Mails
 *
 * ContentItemPB + ModuleProductBoxLevelTagFilter:
 * Änderungen bei Tagfilter Navigation für getaggte Inhalte erfordern, dass ein
 * folgende Languagevariablen von $_LANG2 nach $_LANG verschoben und umbenannt
 * werden:
 * $_LANG2            => $_LANG
 * c_no_content_label => c_tagfilter_no_content_title
 * c_no_content_text  => c_tagfilter_no_content_text
 * keine Htmlentities, da diese Labels in ContentNotFound::getContent() geparst
 * werden
 *
 * Tableplugin statt Insertfixed bei TinyMCE als Standard ab dieser Version:
 * Neue TinyMCE Konfiguration wieder zuruecksetzen, wenn insertfixeddata in Verwendung ist:
 * - insertfixeddata wieder zu den Plugins hinzufuegen
 * - cmsEditor.buttons1: Statt table wieder insertfixed1,insertfixed2 einfuegen.
 *
 * Ab dieser Version wird die Konfiguration $_CONFIG['si_html_text'] nicht mehr benoetigt
 * und kann entfernt werden. Wurde si_html_text auf true gesetzt, dann muss
 * ueber si_area_shorttext_allowed_html bzw. si_box_shorttext_allowed_html
 * (je nachdem wo eine TinyMCE Box initialisiert wird) das erlaubte HTML am Frontend
 * angegeben werden. Ueblicherweise: '<a><b><i><br><span><sub><sup>'
 *
 * Frontend User können nun auch mit Firmendatensätzen verknüpft werden ( früher nur
 * Freitextfeld bei User ). Am Backend wurde dazu das Submodul
 * ModuleFrontendUserMgmtCompany entwickelt. Dabei wurden die folgenden CSS
 * 'display' Klassen im custom_config.css erstellt:
 * > display_fu_company Anzeige des Textfeldes
 * > display_fu_company_select Anzeige des Dropdowns für Firmenauswahl
 *   Steht beim Kundnprojekt dir Benutzerverwaltung zur Verfügung sollte, sofern es
 * nicht anders gewünscht ist, das Submodul zur Verwaltung der Firmen von den
 * Benutzerberechtigungen entfernt werden:
 * INSERT INTO mc_user_rights_submodules (FK_UID, URMModuleShortname, URMSubmodules)
 * VALUES ('<user_id>', 'frontendusermgmt', 'main');
 *   Außerdem muss das Textfeld für die Firma wieder korrekt im custom_config.css
 * eingeblendet werden.
 *
 * Zu den TinyMCE Deselektoren muss '#em_pp_remarks' hinzugefügt werden.
 *
 * Anstatt der globalen Sideboxen koennen nun globalen Bereiche ueber das
 * gleichnamige Modul verwendet werden. Dazu koennen die globalen Sideboxen
 * ueber das manage_stuff Skript migriert werden.
 * Die Bildergroessen-/Textlaengenkonfiguration muessen manuell uebernommen werden.
 * Danach sollte die Startseitenkonfiguration angepasst und der nun
 * ueberfluessige Bereich geloescht werden.
 *
 * ModuleMultimediaLibrary: Am Backend gibt es neue Display Klassen fuer den
 * Video Tab (jetzt an zweiter Position) und Seitenverknuepfung:
 * - display_ms_video1
 * - display_ms_show_on_page_container
 *
 * Das Land mit der ID 231 wurde auf UNITED STATES MINOR OUTLYING ISLANDS
 * umbenannt. Der Label sollte in der Datenbank überprüft und bei Bedarf an die
 * Schreibweise der anderen Ländernamen angepasst werden.
 *
 * Das Frontend-Modul ModuleEmployeeBox hat aufgrund eines Namenskonfliktes mit
 * ContentItemEB nun den Präfix ex_ erhalten: es müssen alle Präfixe ersetzt
 * werden:
 * - Language Dateien
 * - Template
 * - Konfigurationsvariablen:
 *   'eb_max_employeeboxes' > 'ex_max_employeeboxes'
 *   'eb_no_random' > 'ex_no_random'
 *
 *
 * [/INFO]
 */

/******************************************************************************/
/*   Zusatztexte + Navigationsbild pro ContentItem für Sitemapnavigation      */
/******************************************************************************/

ALTER TABLE mc_contentitem
ADD CAdditionalImageLevel TINYINT NOT NULL DEFAULT '0' AFTER CTaggable;
ALTER TABLE mc_contentitem
ADD CAdditionalTextLevel TINYINT NOT NULL DEFAULT '0' AFTER CAdditionalImageLevel;

ALTER TABLE mc_contentabstract
ADD CAdditionalImage VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER CImageBlog ,
ADD CAdditionalText TEXT NOT NULL DEFAULT '' AFTER CAdditionalImage;

INSERT INTO mc_moduletype_backend
(MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
('44', 'additionaltextlevel', 'ModuleAdditionalTextLevel', '0', '0', '0'),
('45', 'additionaltext', 'ModuleAdditionalText', '0', '0', '0'),
('46', 'additionalimagelevel', 'ModuleAdditionalImageLevel', '0', '0', '0'),
('47', 'additionalimage', 'ModuleAdditionalImage', '0', '0', '0');

/******************************************************************************/
/*               Employee Position Datentyp auf INT aendern                   */
/******************************************************************************/

ALTER TABLE mc_module_employee CHANGE EPosition EPosition INT( 11 ) NOT NULL DEFAULT '0';

/******************************************************************************/
/* ContentItemPB - Verwaltung der Anzeige von Produkten aus ContentItemPP     */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp_product
ADD PPPShowOnLevel TINYINT NOT NULL DEFAULT '0' AFTER PPPDisabled;

/******************************************************************************/
/*               Shop Plus - Zahlungsarten mit Preis je Land                  */
/******************************************************************************/

CREATE TABLE mc_contentitem_cp_payment_type_country (
  FK_CYID int(11) NOT NULL,
  FK_COID int(11) NOT NULL,
  CPYCPrice float NOT NULL DEFAULT '0',
  KEY FK_CYID (FK_CYID),
  KEY FK_COID (FK_COID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                 Shop Plus - Verschiedene USt für Produkte                  */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp
ADD PPTaxRate TINYINT NOT NULL DEFAULT '0' AFTER FK_PPPID_Cheapest;

ALTER TABLE mc_contentitem_cp_order_item
ADD CPOITax DOUBLE NOT NULL AFTER CPOISum,
ADD CPOITaxRate TINYINT NOT NULL DEFAULT '0' AFTER CPOIProductPrice,
ADD CPOITaxRatePercentage INT NOT NULL DEFAULT '0' AFTER CPOITaxRate;

/******************************************************************************/
/*                          ContentItemBG: 3 x Titel                          */
/******************************************************************************/

ALTER TABLE mc_contentitem_bg
CHANGE GTitle GTitle1 VARCHAR( 150 ) NOT NULL,
ADD GTitle2 VARCHAR( 150 ) NOT NULL AFTER GTitle1,
ADD GTitle3 VARCHAR( 150 ) NOT NULL AFTER GTitle2;

/******************************************************************************/
/*             Shop Plus - global options - aus System entfernen              */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp_option_global DROP OPGlobal;

/******************************************************************************/
/*             Tagfilter Modul + PB Filter + Sitemapnav Filter                */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('63', 'productboxleveltagfilter', 'ModuleProductBoxLevelTagFilter', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                   Shop Plus - Zusatzdaten für Produkte                     */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp_product
ADD PPPAdditionalData TEXT NOT NULL AFTER PPPShowOnLevel;

/******************************************************************************/
/*                     Veranstaltungsbuchungssystem                           */
/******************************************************************************/

CREATE TABLE mc_frontend_user_company (
  FUCID int(11) NOT NULL AUTO_INCREMENT,
  FUCName varchar(255) NOT NULL,
  FUCStreet varchar(255) NOT NULL,
  FUCPostalCode varchar(255) NOT NULL,
  FUCCity varchar(255) NOT NULL,
  FK_CID_Country int(11) NOT NULL,
  FUCPhone varchar(255) NOT NULL,
  FUCFax varchar(255) NOT NULL,
  FUCEmail varchar(255) NOT NULL,
  FUCWeb varchar(255) NOT NULL,
  FUCNotes varchar(255) NOT NULL,
  FUCType varchar(255) NOT NULL,
  FUCImage varchar(255) NOT NULL,
  FUCVatNumber varchar(255) NOT NULL,
  FUCCreateDatetime datetime NOT NULL,
  FUCChangeDatetime datetime NOT NULL,
  FK_FUCAID_Area int(11) NOT NULL,
  FUCDeleted tinyint(1) NOT NULL,
  PRIMARY KEY (FUCID),
  KEY FK_CID_Country (FK_CID_Country),
  KEY FK_FUCAID_Area (FK_FUCAID_Area)
) ENGINE=MyISAM;

CREATE TABLE mc_frontend_user_company_area (
  FUCAID int(11) NOT NULL AUTO_INCREMENT,
  FUCAName varchar(255) NOT NULL,
  FK_FUCAID_Parent int(11) NOT NULL,
  FUCADeleted tinyint(1) NOT NULL,
  PRIMARY KEY (FUCAID),
  KEY FK_FUCAID_Parent (FK_FUCAID_Parent)
) ENGINE=MyISAM;

ALTER TABLE mc_frontend_user
ADD FK_FUCID_Company INT NOT NULL DEFAULT '0' AFTER FUCompany,
ADD INDEX ( FK_FUCID_Company );

/* ContentItemEB */
INSERT INTO mc_contenttype
(CTID, CTClass, CTActive, CTPosition, FK_CTID, CTTemplate, CTPageType)
VALUES ('56', 'ContentItemEB', '0', '156', '0', '0', '1');

CREATE TABLE mc_contentitem_eb (
  EBID int(11) NOT NULL AUTO_INCREMENT,
  EBTitle1 varchar(255) NOT NULL,
  EBTitle2 varchar(255) NOT NULL,
  EBTitle3 varchar(255) NOT NULL,
  EBImage1 varchar(255) NOT NULL,
  EBImage2 varchar(255) NOT NULL,
  EBImage3 varchar(255) NOT NULL,
  EBImageTitles text,
  EBText1 text,
  EBText2 text,
  EBText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EBID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

/* ModulEventManagement */
INSERT INTO mc_moduletype_backend
(MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('49', 'eventmgmt', 'ModuleEventManagement', '0', '59', '0');

CREATE TABLE mc_module_em_booking (
  EMBID int(11) NOT NULL AUTO_INCREMENT,
  FK_FUID_Participant int(11) NOT NULL DEFAULT '0',
  FK_EMEID_Event int(11) NOT NULL DEFAULT '0',
  EMBCreateDatetime datetime NOT NULL,
  EMBChangeDatetime datetime NOT NULL,
  EMBDeleted tinyint(4) NOT NULL DEFAULT '0',
  EMBName varchar(255) NOT NULL,
  EMBStreet varchar(255) NOT NULL,
  EMBCity varchar(255) NOT NULL,
  EMBPostalCode varchar(255) NOT NULL,
  FK_CID_Country int(11) NOT NULL,
  EMBPhone varchar(255) NOT NULL,
  EMBFax varchar(255) NOT NULL,
  EMBEmail varchar(255) NOT NULL,
  EMBVatNumber varchar(255) NOT NULL,
  FK_FUCID_Company int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMBID),
  KEY FK_FUID_Participant (FK_FUID_Participant),
  KEY FK_EMEID_Event (FK_EMEID_Event),
  KEY FK_CID_Country (FK_CID_Country),
  KEY FK_FUCID_Company (FK_FUCID_Company)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_booking_data (
  FK_EMBID_Booking int(11) NOT NULL DEFAULT '0',
  EMBDDataKey varchar(255) NOT NULL,
  EMBDDataValue varchar(255) NOT NULL,
  KEY FK_EMBID_Booking (FK_EMBID_Booking)
) ENGINE=MyISAM;

CREATE TABLE mc_module_em_display_group (
  EMDGID int(11) NOT NULL AUTO_INCREMENT,
  EMDGName varchar(255) NOT NULL,
  EMDGTitle1 varchar(255) NOT NULL,
  EMDGTitle2 varchar(255) NOT NULL,
  EMDGTitle3 varchar(255) NOT NULL,
  EMDGText1 text NOT NULL,
  EMDGText2 text NOT NULL,
  EMDGText3 text NOT NULL,
  EMDGImage1 varchar(255) NOT NULL,
  EMDGImage2 varchar(255) NOT NULL,
  EMDGImage3 varchar(255) NOT NULL,
  EMDGPosition int(11) NOT NULL DEFAULT '0',
  EMDGCreateDatetime datetime NOT NULL,
  EMDGChangeDatetime datetime NOT NULL,
  EMDGDeleted tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMDGID)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_event (
  EMEID int(11) NOT NULL AUTO_INCREMENT,
  EMEName varchar(255) NOT NULL,
  EMEPoints int(11) NOT NULL DEFAULT '0',
  EMEMaxParticipants int(11) NOT NULL DEFAULT '0',
  EMEMaxParticipantsPerBooking int(11) NOT NULL DEFAULT '0',
  FK_FUCAID_CompanyArea int(11) NOT NULL DEFAULT '0',
  FK_EMDGID_DisplayGroup int(11) NOT NULL DEFAULT '0',
  FK_EMETID_EventType int(11) NOT NULL DEFAULT '0',
  FK_EMLID_Level int(11) NOT NULL DEFAULT '0',
  EMECode varchar(255) NOT NULL,
  EMETitle1 varchar(255) NOT NULL,
  EMETitle2 varchar(255) NOT NULL,
  EMETitle3 varchar(255) NOT NULL,
  EMEText1 varchar(255) NOT NULL,
  EMEText2 varchar(255) NOT NULL,
  EMEText3 varchar(255) NOT NULL,
  EMEImage1 varchar(255) NOT NULL,
  EMEImage2 varchar(255) NOT NULL,
  EMEImage3 varchar(255) NOT NULL,
  EMEStartDate date NOT NULL,
  EMEEndDate date NOT NULL,
  EMECreateDatetime datetime NOT NULL,
  EMEChangeDatetime datetime NOT NULL,
  EMEDeleted tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMEID),
  KEY FK_FUCAID_CompanyArea (FK_FUCAID_CompanyArea),
  KEY FK_FUCAID_DisplayGroup (FK_EMDGID_DisplayGroup),
  KEY FK_FUCAID_EventType (FK_EMETID_EventType),
  KEY FK_FUCAID_Level (FK_EMLID_Level)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_event_download (
  EMEDID int(11) NOT NULL AUTO_INCREMENT,
  EMEDFile varchar(255) NOT NULL,
  EMEDAvailableForParticipanceStatus varchar(255) NOT NULL,
  EMEDPosition int(11) NOT NULL DEFAULT '0',
  FK_EMEID_Event int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMEDID),
  KEY FK_EMEID_Event (FK_EMEID_Event)
) ENGINE=MyISAM;

CREATE TABLE mc_module_em_event_participance (
  EMEPID int(11) NOT NULL AUTO_INCREMENT,
  FK_EMBID_Booking int(11) NOT NULL DEFAULT '0',
  FK_EMEID_Event int(11) NOT NULL DEFAULT '0',
  FK_FUID_Participant int(11) NOT NULL DEFAULT '0',
  EMEPStatus enum('booked','confirmed','participated') NOT NULL,
  EMEPDocumentRequired tinyint(4) NOT NULL DEFAULT '0',
  EMEPDocument varchar(255) NOT NULL,
  EMEPDiet int(11) NOT NULL DEFAULT '0',
  EMEPRemarks varchar(255) NOT NULL,
  FK_CPID_Company int(11) NOT NULL DEFAULT '0',
  FK_FID_Foa int(11) NOT NULL DEFAULT '0',
  EMEPPosition varchar(255) NOT NULL,
  EMEPTitle varchar(255) NOT NULL,
  EMEPFirstname varchar(255) NOT NULL,
  EMEPLastname varchar(255) NOT NULL,
  EMEPBirthday date NOT NULL,
  EMEPCountry varchar(255) NOT NULL,
  EMEPZip varchar(255) NOT NULL,
  EMEPCity varchar(255) NOT NULL,
  EMEPAddress varchar(255) NOT NULL,
  EMEPPhone varchar(255) NOT NULL,
  EMEPMobilePhone varchar(255) NOT NULL,
  EMEPFax varchar(255) NOT NULL,
  EMEPEmail varchar(255) NOT NULL,
  EMEPDeleted tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMEPID),
  KEY FK_EMBID_Booking (FK_EMBID_Booking),
  KEY FK_EMEID_Event (FK_EMEID_Event),
  KEY FK_FUID_Participant (FK_FUID_Participant),
  KEY FK_CPID_Company (FK_CPID_Company),
  KEY FK_FID_Foa (FK_FID_Foa)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_event_type (
  EMETID int(11) NOT NULL AUTO_INCREMENT,
  EMETIdentifier varchar(255) NOT NULL,
  EMETPosition int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMETID),
  UNIQUE KEY EMETPosition (EMETPosition)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_level (
  EMLID int(11) NOT NULL AUTO_INCREMENT,
  EMLIdentifier varchar(255) NOT NULL,
  EMLText text NOT NULL,
  EMLPosition int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EMLID),
  UNIQUE KEY EMLPosition (EMLPosition)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_newslettergroup (
  EMNID int(11) NOT NULL AUTO_INCREMENT,
  EMNName varchar(255) NOT NULL,
  EMText text NOT NULL,
  PRIMARY KEY (EMNID)
) ENGINE=MyISAM;

CREATE TABLE mc_module_em_participant (
  FK_FUID int(11) NOT NULL DEFAULT '0',
  EMPIsEditor tinyint(4) NOT NULL DEFAULT '0',
  EMPBookingAllowed tinyint(4) NOT NULL DEFAULT '0',
  EMPPoints int(11) NOT NULL DEFAULT '0',
  FK_EMLID_Level int(11) NOT NULL DEFAULT '0',
  EMPDocumentRequired tinyint(4) NOT NULL DEFAULT '0',
  EMPDiet int(11) NOT NULL DEFAULT '0',
  EMPRemarks varchar(255) NOT NULL,
  PRIMARY KEY (FK_FUID),
  KEY FK_EMLID_Level (FK_EMLID_Level)
) ENGINE=MyISAM;

CREATE TABLE mc_module_em_participant_event_type (
  FK_FUID_Participant int(11) NOT NULL DEFAULT '0',
  FK_EMETID_EventType int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_FUID_Participant,FK_EMETID_EventType)
) ENGINE=MyISAM;

CREATE TABLE mc_module_em_participant_newslettergroup (
  FK_FUID_Participant int(11) NOT NULL DEFAULT '0',
  FK_EMNID_Newslettergroup int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_FUID_Participant,FK_EMNID_Newslettergroup)
) ENGINE=MyISAM;

ALTER TABLE mc_module_em_event
CHANGE EMEText1 EMEText1 TEXT NOT NULL,
CHANGE EMEText2 EMEText2 TEXT NOT NULL,
CHANGE EMEText3 EMEText3 TEXT NOT NULL;

ALTER TABLE mc_module_em_booking
CHANGE FK_FUID_Participant FK_FUID INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE mc_module_em_booking DROP INDEX FK_FUID_Participant ,
ADD INDEX FK_FUID ( FK_FUID );

ALTER TABLE mc_module_em_event_download
ADD EMEDTitle VARCHAR( 255 ) NOT NULL AFTER EMEDFile;

ALTER TABLE mc_module_em_event_download
ADD EMEDChangeDatetime DATETIME NOT NULL AFTER FK_EMEID_Event,
ADD EMEDCreateDatetime DATETIME NOT NULL AFTER EMEDChangeDatetime;

ALTER TABLE mc_module_em_event_download
ADD EMEDSize INT NOT NULL AFTER EMEDFile;

ALTER TABLE mc_module_em_event_participance
ADD EMEPCreateDatetime DATETIME NOT NULL AFTER EMEPEmail,
ADD EMEPChangeDatetime DATETIME NOT NULL AFTER EMEPCreateDatetime;

ALTER TABLE mc_module_em_participant
CHANGE EMPRemarks EMPRemarks TEXT NOT NULL;

/******************************************************************************/
/*                            HTML Creator Modul                              */
/******************************************************************************/

CREATE TABLE mc_module_html_creator (
  HCID int(11) NOT NULL AUTO_INCREMENT,
  HCTitle1 varchar(255) NOT NULL,
  HCTitle2 varchar(255) NOT NULL,
  HCTitle3 varchar(255) NOT NULL,
  HCText1 text,
  HCText2 text,
  HCText3 text,
  HCImage1 varchar(150) NOT NULL,
  HCImage2 varchar(150) NOT NULL,
  HCImage3 varchar(150) NOT NULL,
  HCImageTitles text,
  HCCreateDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  HCChangeDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  HCDeleted tinyint(1) NOT NULL DEFAULT '0',
  HCTemplate varchar(100) NOT NULL DEFAULT '',
  HCCopiedFromID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (HCID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE mc_module_html_creator_box (
  HCBID int(11) NOT NULL AUTO_INCREMENT,
  HCBTitle varchar(255) NOT NULL,
  HCBText text,
  HCBImage varchar(150) NOT NULL,
  HCBImageTitles text,
  HCBPosition int(11) NOT NULL,
  HCBDeleted tinyint(1) NOT NULL DEFAULT '0',
  FK_HCID int(11) NOT NULL,
  PRIMARY KEY (HCBID),
  KEY FK_HCID (FK_HCID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE mc_module_html_creator_export_log (
  HCELID int(11) NOT NULL AUTO_INCREMENT,
  HCELDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FK_UID int(11) NOT NULL,
  FK_HCID int(11) NOT NULL,
  PRIMARY KEY (HCELID),
  KEY FK_HCID (FK_HCID),
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES (48, 'htmlcreator', 'ModuleHtmlCreator', 0, 58, 0);

/******************************************************************************/
/*                            ContentItemSD                                   */
/******************************************************************************/

CREATE TABLE mc_contentitem_sd (
  SDID int(11) NOT NULL AUTO_INCREMENT,
  SDTitle1 varchar(150) NOT NULL,
  SDTitle2 varchar(150) NOT NULL,
  SDTitle3 varchar(150) NOT NULL,
  SDImage1 varchar(150) NOT NULL,
  SDImage2 varchar(150) NOT NULL,
  SDImage3 varchar(150) NOT NULL,
  SDImageTitles text,
  SDText1 text,
  SDText2 text,
  SDText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SDID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

INSERT INTO mc_contenttype(CTID, CTClass, CTActive, CTPosition)
VALUES ( 47, 'ContentItemSD', 0, 157 );

/******************************************************************************/
/*                   ModuleMultimediaLibrary Erweiterung                      */
/******************************************************************************/

ALTER TABLE mc_module_medialibrary
ADD MCreateDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER MImageTitles,
ADD MChangeDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER MCreateDateTime;

/******************************************************************************/
/*                        FrontendUser Deleted Flag                           */
/******************************************************************************/

ALTER TABLE mc_frontend_user ADD FUDeleted tinyint(1) NOT NULL DEFAULT '0';

/******************************************************************************/
/*                  EventManagement, FrontendUser Logging                     */
/******************************************************************************/

CREATE TABLE mc_module_em_event_participance_log (
  FK_EMEPID int(11) NOT NULL,
  FK_EMBID_Booking int(11) NOT NULL DEFAULT '0',
  FK_EMEID_Event int(11) NOT NULL DEFAULT '0',
  FK_FUID_Participant int(11) NOT NULL DEFAULT '0',
  EMEPStatus enum('booked','confirmed','participated') NOT NULL,
  EMEPDocumentRequired tinyint(4) NOT NULL DEFAULT '0',
  EMEPDocument varchar(255) NOT NULL,
  EMEPDiet int(11) NOT NULL DEFAULT '0',
  EMEPRemarks text NOT NULL,
  FK_CPID_Company int(11) NOT NULL DEFAULT '0',
  FK_FID_Foa int(11) NOT NULL DEFAULT '0',
  EMEPPosition varchar(255) NOT NULL,
  EMEPTitle varchar(255) NOT NULL,
  EMEPFirstname varchar(255) NOT NULL,
  EMEPLastname varchar(255) NOT NULL,
  EMEPBirthday date NOT NULL,
  EMEPCountry varchar(255) NOT NULL,
  EMEPZip varchar(255) NOT NULL,
  EMEPCity varchar(255) NOT NULL,
  EMEPAddress varchar(255) NOT NULL,
  EMEPPhone varchar(255) NOT NULL,
  EMEPMobilePhone varchar(255) NOT NULL,
  EMEPFax varchar(255) NOT NULL,
  EMEPEmail varchar(255) NOT NULL,
  EMEPCreateDatetime DATETIME NOT NULL,
  EMEPChangeDatetime DATETIME NOT NULL,
  EMEPDeleted tinyint(4) NOT NULL DEFAULT '0',
  EMEPLogDateTime datetime NOT NULL,
  FK_UID_User int(11) NOT NULL,
  FK_UID_FrontendUser int(11) NOT NULL,
  PRIMARY KEY (FK_EMEPID, FK_UID_User, FK_UID_FrontendUser, EMEPLogDateTime),
  KEY FK_EMBID_Booking (FK_EMBID_Booking),
  KEY FK_EMEID_Event (FK_EMEID_Event),
  KEY FK_FUID_Participant (FK_FUID_Participant),
  KEY FK_CPID_Company (FK_CPID_Company),
  KEY FK_FID_Foa (FK_FID_Foa),
  KEY FK_UID_User (FK_UID_User),
  KEY FK_UID_FrontendUser (FK_UID_FrontendUser)
) ENGINE=MyISAM ;

CREATE TABLE mc_frontend_user_log (
  FK_FUID int(11) NOT NULL,
  FUSID varchar(50) NOT NULL,
  FUCompany varchar(150) DEFAULT NULL,
  FK_FUCID_Company int(11) NOT NULL DEFAULT '0',
  FUPosition varchar(150) DEFAULT NULL,
  FK_FID int(11) NOT NULL DEFAULT '0',
  FUTitle varchar(50) DEFAULT NULL,
  FUFirstname varchar(150) NOT NULL,
  FULastname varchar(150) NOT NULL,
  FUNick varchar(50) NOT NULL,
  FUPW varchar(50) NOT NULL,
  FUBirthday date DEFAULT NULL,
  FUCountry int(11) NOT NULL,
  FUZIP varchar(6) NOT NULL,
  FUCity varchar(150) NOT NULL,
  FUAddress varchar(150) NOT NULL,
  FUPhone varchar(50) NOT NULL,
  FUMobilePhone varchar(50) NOT NULL,
  FUEmail varchar(100) NOT NULL,
  FUNewsletter tinyint(1) NOT NULL DEFAULT '1',
  FUUID varchar(25) NOT NULL,
  FUFax varchar(50) NOT NULL,
  FUDepartment varchar(255) NOT NULL,
  FUCreateDateTime datetime DEFAULT NULL,
  FUChangeDateTime datetime DEFAULT NULL,
  FUAllowMultipleSessions tinyint(1) NOT NULL DEFAULT '0',
  FULastLogin datetime NOT NULL,
  FUCountLogins int(11) NOT NULL DEFAULT '0',
  FUShowProfile tinyint(1) NOT NULL DEFAULT '0',
  FUActivationCode varchar(50) NOT NULL,
  FUDeleted tinyint(1) NOT NULL DEFAULT '0',
  FULogDateTime datetime NOT NULL,
  FK_UID_User int(11) NOT NULL,
  FK_UID_FrontendUser int(11) NOT NULL,
  PRIMARY KEY (FK_FUID, FK_UID_User, FK_UID_FrontendUser, FULogDateTime),
  KEY FK_FUCID_Company (FK_FUCID_Company)
) ENGINE=MyISAM ;

CREATE TABLE mc_module_em_participant_log (
  FK_FUID int(11) NOT NULL DEFAULT '0',
  EMPIsEditor tinyint(4) NOT NULL DEFAULT '0',
  EMPBookingAllowed tinyint(4) NOT NULL DEFAULT '0',
  EMPPoints int(11) NOT NULL DEFAULT '0',
  FK_EMLID_Level int(11) NOT NULL DEFAULT '0',
  EMPDocumentRequired tinyint(4) NOT NULL DEFAULT '0',
  EMPDiet int(11) NOT NULL DEFAULT '0',
  EMPRemarks varchar(255) NOT NULL,
  EMPLogDateTime datetime NOT NULL,
  FK_UID_User int(11) NOT NULL,
  FK_UID_FrontendUser int(11) NOT NULL,
  PRIMARY KEY (FK_FUID, FK_UID_User, FK_UID_FrontendUser, EMPLogDateTime),
  KEY FK_EMLID_Level (FK_EMLID_Level)
) ENGINE=MyISAM;

CREATE TABLE mc_module_em_booking_log (
  FK_EMBID int(11) NOT NULL,
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_EMEID_Event int(11) NOT NULL DEFAULT '0',
  EMBCreateDatetime datetime NOT NULL,
  EMBChangeDatetime datetime NOT NULL,
  EMBDeleted tinyint(4) NOT NULL DEFAULT '0',
  EMBName varchar(255) NOT NULL,
  EMBStreet varchar(255) NOT NULL,
  EMBCity varchar(255) NOT NULL,
  EMBPostalCode varchar(255) NOT NULL,
  FK_CID_Country int(11) NOT NULL,
  EMBPhone varchar(255) NOT NULL,
  EMBFax varchar(255) NOT NULL,
  EMBEmail varchar(255) NOT NULL,
  EMBVatNumber varchar(255) NOT NULL,
  FK_FUCID_Company int(11) NOT NULL DEFAULT '0',
  EMBLogDateTime datetime NOT NULL,
  FK_UID_User int(11) NOT NULL,
  FK_UID_FrontendUser int(11) NOT NULL,
  PRIMARY KEY (FK_EMBID, EMBLogDateTime, FK_UID_User, FK_UID_FrontendUser),
  KEY FK_FUID (FK_FUID),
  KEY FK_EMEID_Event (FK_EMEID_Event),
  KEY FK_CID_Country (FK_CID_Country),
  KEY FK_FUCID_Company (FK_FUCID_Company)
) ENGINE=MyISAM ;

/* Middle name / zweiter Vorname für Frontend User */
ALTER TABLE mc_frontend_user ADD FUMiddlename VARCHAR( 255 ) NOT NULL AFTER FUFirstname;
ALTER TABLE mc_frontend_user_log ADD FUMiddlename VARCHAR( 255 ) NOT NULL AFTER FUFirstname;

/* Add country code field */
ALTER TABLE mc_country ADD COCode INT NOT NULL DEFAULT '0' AFTER COSymbol;

/* Update country name ( default was United States - which actually is 230 ) */
UPDATE mc_country
SET COName = 'UNITED STATES MINOR OUTLYING ISLANDS'
WHERE COID = 231;

/* Additional participance data */
ALTER TABLE mc_module_em_event_participance
ADD EMEPDocumentPassportNumber VARCHAR( 255 ) NOT NULL AFTER EMEPDocument ,
ADD EMEPDocumentPassportDateOfIssue VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportNumber ,
ADD EMEPDocumentPassportPlaceOfIssue VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportDateOfIssue ,
ADD EMEPDocumentPassportExpiredDate VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportPlaceOfIssue ,
ADD EMEPDocumentPassportNationality VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportExpiredDate;

ALTER TABLE mc_module_em_event_participance_log
ADD EMEPDocumentPassportNumber VARCHAR( 255 ) NOT NULL AFTER EMEPDocument ,
ADD EMEPDocumentPassportDateOfIssue VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportNumber ,
ADD EMEPDocumentPassportPlaceOfIssue VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportDateOfIssue ,
ADD EMEPDocumentPassportExpiredDate VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportPlaceOfIssue ,
ADD EMEPDocumentPassportNationality VARCHAR( 255 ) NOT NULL AFTER EMEPDocumentPassportExpiredDate;

/* Middlename auch für Buchung speichern */
ALTER TABLE mc_module_em_event_participance ADD EMEPMiddlename VARCHAR( 255 ) NOT NULL AFTER EMEPFirstname;
ALTER TABLE mc_module_em_event_participance_log ADD EMEPMiddlename VARCHAR( 255 ) NOT NULL AFTER EMEPFirstname;

/******************************************************************************/
/*                             ModuleGlobalAreas                              */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES (50, 'globalareamgmt', 'ModuleGlobalAreaManagement', 0, 60, 0);

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES (64, 'globalarea', 'ModuleGlobalArea', 0, 0, 0, 0, 0);

CREATE TABLE mc_module_global_area (
  GAID int(11) NOT NULL AUTO_INCREMENT,
  GATitle varchar(100) NOT NULL,
  GAText text NOT NULL,
  GAImage varchar(100) NOT NULL,
  GABoxType enum('large','medium','small') NOT NULL DEFAULT 'large',
  GAPosition tinyint(4) NOT NULL,
  GADisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (GAID),
  UNIQUE KEY FK_SID_GAPosition_UN (FK_SID, GAPosition)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE mc_module_global_area_box (
  GABID int(11) NOT NULL AUTO_INCREMENT,
  GABTitle varchar(100) NOT NULL,
  GABText text NOT NULL,
  GABImage varchar(100) NOT NULL,
  GABNoImage tinyint(4) NOT NULL DEFAULT '0',
  GABNoText tinyint(4) NOT NULL DEFAULT '0',
  GABPosition tinyint(4) NOT NULL DEFAULT '0',
  GABPositionLocked tinyint(1) NOT NULL DEFAULT '0',
  GABDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  FK_GAID int(11) NOT NULL,
  PRIMARY KEY (GABID),
  UNIQUE KEY FK_GAID_GABPosition_UN (FK_GAID, GABPosition),
  KEY FK_GAID (FK_GAID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

/******************************************************************************/
/* Ungültiger Wert varchar(500) für LAction in mc_module_shopplusmgmt_log     */
/******************************************************************************/

ALTER TABLE mc_module_shopplusmgmt_log
CHANGE LAction LAction VARCHAR( 255 ) NOT NULL;

/******************************************************************************/
/*                    ModuleGlobalAreas Zeitsteuerung                         */
/******************************************************************************/

ALTER TABLE mc_module_global_area_box ADD GABShowFromDateTime datetime NOT NULL AFTER GABDisabled;
ALTER TABLE mc_module_global_area_box ADD GABShowUntilDateTime datetime NOT NULL AFTER GABShowFromDateTime;

/******************************************************************************/
/*                 Kosovo in der Länderliste ergänzen                         */
/******************************************************************************/

INSERT INTO mc_country (COID, COName, COSymbol, COCode, COPosition, COActive)
VALUES ('244', 'KOSOVO', 'XK', '381', '744', '0');

/******************************************************************************/
/*                            Mobile Switch Modul                             */
/******************************************************************************/

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`)
VALUES (51, 'mobileswitch', 'ModuleMobileSwitch', 0, 0, 0);

ALTER TABLE mc_contentitem
ADD CMobile TINYINT NOT NULL DEFAULT '0' AFTER CAdditionalTextLevel;

UPDATE mc_contentitem SET CMobile = 1 WHERE FK_CIID IS NULL;