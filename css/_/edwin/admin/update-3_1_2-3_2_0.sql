/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * Es existiert ein PHP-Update-Script, welches nach diesem SQL-Update-Script
 * ausgef�hrt werden muss. Daf�r muss man  �ber den Browser
 * /edwin/admin/update-3_1_2-3_2_0.php aufrufen.
 *
 * WICHTIG: Bis zu dieser Version war in ModuleLeadmanagement / ModuleForm
 * folgender Bug bei Comboboxen vorhanden: Die IDs der L�nder wurden in der
 * Combobox ignoriert, stattdessen hatte Element an der Position 1 auch Wert 1.
 * Bei speziellen Konfigurationen, die sich von der Standardkonfiguration
 * unterscheiden, muss ein manuelles Matching von Datens�tzen vorgenommen werden.
 * Zu beachten ist dabei, dass die Konfiguration f�r L�nder sowohl seiten, als
 * auch inhaltstyp-spezifisch vorgenommen worden sein kann.
 *
 * Hinweis: Ab dieser Version werden auch die aktiven Navigationspunkte von VA, BE und
 * Archive im main.tpl (m_nv_selection_levelX) ausgegeben. Bisher wurde f�r
 * aktive Kindelemente dieser Navigationsebenen immer die Position 0 gesetzt.
 *
 * [BE] ContentItemQP:
 * Achtung im Template ContentItemQP_Statement wurden
 * die Variablen f�r spezielle Bildergroessen umbenannt:
 * qp_statement_image1_tpl_width => qp_statement_image_tpl_width1
 * qp_statement_image2_tpl_width => qp_statement_image_tpl_width2
 * qp_statement_image3_tpl_width => qp_statement_image_tpl_width3
 * qp_statement_image4_tpl_width => qp_statement_image_tpl_width4
 * Anpassung notwendig wenn das Tempalte veraendert wurde oder Custom Templates
 * fuer diesen Inhaltstyp angelegt wurden.
 * Au�erdem werden die Labels f�r Titel, Text, Bild und Bilduntertitel nun aus
 * $_LANG statt $_LANG2 gelesen und im Template geparst. Dabei haben sich auch
 * die Namen der Template Variablen ge�ndert:
 * {qp_statement_title_label} => {qp_statement_title1_label}
 * {qp_statement_image_label} => {qp_statement_image1_label}
 * {qp_statement_text_label} => {qp_statement_text1_label}
 * Folgende Variablen m�ssen manuell gesucht und entsprechend ersetzt werden:
 * {qp_statement_content_title_label} => {qp_statement_title<2|3|4>_label}
 * {qp_statement_content_text_label} => {qp_statement_text<2|3|4>_label}
 * {qp_statement_content_image_label} => {qp_statement_image<2|3|4>_label}
 * Achtung: Obsolete Language Variablen sollte aus Custom-Langfiles entfernt werden
 *
 * EC: Am Frontend sind Templatevariablen nicht mehr verfuegbar, weil ab dieser Version
 * jeder Mitarbeiter zu mehreren Standorten und Abteilungen zugewiesen werden kann.
 * Nun werden LOOPs geparst, welche die Attribute ausgeben.
 * c_ec_type, c_ec_department, IF: c_ec_type_available_{c_ec_position},
 * IF: c_ec_type_not_available_{c_ec_position}
 * Im main.tpl wird ab nun im body tag der Modul Shortname als CSS Klasse ausgeben
 * mod_{main_module_shortname}
 *
 * ContentItemPP - 5 zus�tzliche Bilder ( allgemein + Produkt )
 * Sowohl am Backend als auch am Frontend m�ssen die Templates �berpr�ft werden:
 * [AL] PPImage2 wurde zu PPImage7
 *      PPImage3 wurde zu PPImage8
 * [BE] neue CSS Display Klassen in custom_config.css erg�nzen bzw. �ndern
 * [FE] Die Bildvariablen f�r die Bilder #2 / #3 m�ssen durch #7 / #8 ersetzt
 *      werden.
 * Hinweis: bei der Anzeige der neuen Detailbilder ( #2 - #6 ) m�ssen beim Laden
 *          von Daten per AJAX die entsprechenden �nderungen im Javascript
 *          neu implementiert werden. Zur Zeit wird nur #1 beim Laden neuer Daten
 *          ausgetauscht.
 *
 * [BE] Beim ModuleReseller wurde der Prefix von "rs" auf "rm" ge�ndert.
 * Konfigurationsvariablen, die ge�ndert werden m�ssen:
 * rs_export_glue     => rm_export_glue
 * rm_export_area_ids => rm_export_area_ids
 * Achtung: Das reseller.csv muss unbedingt angepasst werden, da es nun um 2
 *          Spalten mehr enth�lt ( Typ, Bild )
 * Au�erdem k�nnen nun Bilder hochgeladen werden und �ber die Bild Spalte bei
 * beliebigen Resellern referenziert werden.
 *
 * edwin/templates/login.tpl: Character Set Definition eingef�gt. Bei UTF-8
 * Projekten muss das Character set korrekt auf utf-8 ge�ndert werden.
 * <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 *
 * Ab jetzt gibt es f�r die Erstellung der Blogebenenbilder eine eigene
 * Groessenkonfiguration: lo_be_image_width und lo_be_image_height.
 * be_image_width und be_image_height steuern nun nur mehr die Bildgroesse
 * des BE Zusatzdatenbildes. Wurde be_image_width und height fuer die Erstellung
 * des Blogebenenbildes verwendet, dann MUESSEN lo_be_image_width und
 * lo_be_image_height gesetzt werden.
 *
 * Die Standardkonfiguration fuer die Zeitsteuerung wurde angepasst:
 * Neue Inhaltstypen (CTID): 33, 36, 37, 42, 44, 45, 46
 * Entfernte Inhaltstypen (CTID): 23, 25, 38, 39, 77, 78, 79, 80
 * Die Konfiguration sollte ggf. angepasst werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/*                                ModuleSearch                                */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('42', 'search', 'ModuleSearch', '1', '0', '1');

UPDATE mc_user SET UModuleRights = concat(UModuleRights, ',search');

/******************************************************************************/
/*                          ModuleFacebookLikebox                             */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES
('60', 'facebooklikebox', 'ModuleFacebookLikebox', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                          ModuleTwitterWidget                               */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES
('61', 'twitterwidget', 'ModuleTwitterWidget', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                    ContentItemEC Setting: ABC Links                        */
/******************************************************************************/

ALTER TABLE mc_contentitem_ec ADD ECSettingABCLinks tinyint NOT NULL DEFAULT 0 AFTER ECImage;

/******************************************************************************/
/*               ModuleEmployee: Mehrfachzuweisung zu Abteilungen             */
/******************************************************************************/

CREATE TABLE mc_module_employee_department_assignment (
  FK_EID int(11) NOT NULL,
  FK_EDID int(11) NOT NULL,
  UNIQUE KEY FK_EID_FK_EDID (FK_EID,FK_EDID),
  KEY FK_EID (FK_EID),
  KEY FK_EDID (FK_EDID)
) ENGINE=MyISAM;

INSERT INTO mc_module_employee_department_assignment( FK_EID, FK_EDID )
SELECT EID, FK_EDID FROM mc_module_employee WHERE FK_EDID > 0;

ALTER TABLE mc_module_employee DROP FK_EDID;

/******************************************************************************/
/*                          ModuleRecentContentList                           */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES
('62', 'recentcontentlist', 'ModuleRecentContentList', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                              ModuleCopy                                    */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('43', 'copy', 'ModuleCopy', '1', '0', '0');

/******************************************************************************/
/*                   ModuleEmployee / ContentItemEC Aenderung                 */
/******************************************************************************/

ALTER TABLE mc_contentitem_ec ADD ECSettingLocationAddress tinyint NOT NULL DEFAULT 1 COMMENT '1-Hidden 2-ShowOnce 3-PerEmployee' AFTER ECSettingABCLinks;
UPDATE mc_contentitem_ec SET ECSettingLocationAddress = 3;

CREATE TABLE mc_module_employee_attribute (
  FK_EID int(11) NOT NULL,
  FK_AVID int(11) NOT NULL,
  UNIQUE KEY FK_EID_FK_AVID (FK_EID,FK_AVID),
  KEY FK_EID (FK_EID),
  KEY FK_AVID (FK_AVID)
) ENGINE=MyISAM;

ALTER TABLE mc_module_attribute_global MODIFY AImages tinyint(1) NOT NULL DEFAULT '0' AFTER APosition;
ALTER TABLE mc_module_attribute_global ADD AIdentifier varchar(255) NULL UNIQUE AFTER AImages;

INSERT INTO mc_module_attribute_global (ATitle, APosition, AIdentifier, FK_SID)
(SELECT 'Mitarbeiter Standorte', MAX(APosition) + 1, 'en_location', 1 FROM mc_module_attribute_global) UNION
(SELECT 'Mitarbeiter Abteilungen', MAX(APosition) + 2, 'ep_department', 1 FROM mc_module_attribute_global);

/******************************************************************************/
/*            ContentItemPP - billigstes Produkt mitspeichern                 */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp
ADD FK_PPPID_Cheapest INT NOT NULL DEFAULT '0' AFTER PPShippingCosts,
ADD INDEX ( FK_PPPID_Cheapest );

/******************************************************************************/
/*                    ContentItemPB - Product Boxes                           */
/******************************************************************************/

INSERT INTO mc_contenttype
(CTID, CTClass, CTActive, CTPosition, FK_CTID, CTTemplate, CTPageType)
VALUES ('81', 'ContentItemPB', '0', '8', '0', '0', '90');

CREATE TABLE mc_contentitem_pb (
  PBID int(11) NOT NULL AUTO_INCREMENT,
  PBTitle varchar(150) NOT NULL,
  PBText text,
  PBImage varchar(150) NOT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PBID),
  KEY FK_CID (FK_CIID)
);

/******************************************************************************/
/*      ContentItemPP - 5 zus�tzliche Bilder ( allgemein + Produkt )          */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp
CHANGE PPImage2 PPImage7 VARCHAR( 150 ) NOT NULL,
CHANGE PPImage3 PPImage8 VARCHAR( 150 ) NOT NULL,
ADD PPImage2 VARCHAR(150) NOT NULL AFTER PPImage1,
ADD PPImage3 VARCHAR(150) NOT NULL DEFAULT '' AFTER PPImage2,
ADD PPImage4 VARCHAR(150) NOT NULL DEFAULT '' AFTER PPImage3,
ADD PPImage5 VARCHAR(150) NOT NULL DEFAULT '' AFTER PPImage4,
ADD PPImage6 VARCHAR(150) NOT NULL DEFAULT '' AFTER PPImage5;

ALTER TABLE mc_contentitem_pp_product
CHANGE PPPImage PPPImage1 VARCHAR( 150 ) NOT NULL;

ALTER TABLE mc_contentitem_pp_product
ADD PPPImage2 VARCHAR( 150 ) NOT NULL AFTER PPPImage1 ,
ADD PPPImage3 VARCHAR( 150 ) NOT NULL AFTER PPPImage2 ,
ADD PPPImage4 VARCHAR( 150 ) NOT NULL AFTER PPPImage3 ,
ADD PPPImage5 VARCHAR( 150 ) NOT NULL AFTER PPPImage4 ,
ADD PPPImage6 VARCHAR( 150 ) NOT NULL AFTER PPPImage5;

/******************************************************************************/
/* ModuleReseller mit Bildupload + Zuweisung zu Resellern + Bild bei          */
/* ContentItemRS ausgeben                                                     */
/******************************************************************************/

ALTER TABLE mc_module_reseller ADD RType VARCHAR( 255 ) NOT NULL AFTER RNotes ,
ADD RImage VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER RType;
