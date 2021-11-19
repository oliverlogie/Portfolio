/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * BE: Beim Modul MultimediaLibrary (ab 3.0.0) wurde der Praefix von mb auf ms geaendert, da es
 * sonst zu Konflikten (bei Config Variablen, Language Variablen,...) mit dem dazugehoerigen
 * ContentItemMB kommt. Eventuell muessen CSS Display Klassen, Konfigurationsvariablen
 * (mb_video_types, mb_video_types_available) und Language Dateien angepasst werden.
 * Im Normalfall sollte nichts zu aendern sein, weil das Modul noch nicht haeufig verwendet
 * wird und bereits gepatcht sein sollte bei Projekten mit aktiviertem MultimediaLibrary Modul.
 * Die Display Klassen display_ms_link_container und display_ms_show_on_page_container
 * wurden hinzugefuegt, welche standardmaessig die Bereiche "Verknuepfung zu Seiten" und
 * "Zielseite" ausblenden. Event. muessen diese Bereiche wieder eingeblendet werden.
 *
 * BE: jquery.autocomplete.min.js wurde geloescht bzw. ersetzt durch jquery.autocomplete.pack.js
 * Daher wurden die Templates main.tpl, main_leadmgmt.tpl, ModuleLeadManagementSearch.tpl und
 * dialog.htm (tinymce) angepasst.
 *
 * [/INFO]
 */

/******************************************************************************/
/*                       Neues Modul - LanguageDetection                      */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('54', 'languagedetection', 'ModuleLanguageDetection', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                          ContentItemFD Erweiterung                         */
/******************************************************************************/

ALTER TABLE mc_contentitem_fd_trainer_pdf ADD FDTPPosition INT NOT NULL DEFAULT '0' FIRST;

/******************************************************************************/
/*                              ContentItemQP                                 */
/******************************************************************************/

CREATE TABLE IF NOT EXISTS mc_contentitem_qp (
  QPID int(11) NOT NULL AUTO_INCREMENT,
  QPTitle1 varchar(255) NOT NULL,
  QPTitle2 varchar(255) NOT NULL,
  QPTitle3 varchar(255) NOT NULL,
  QPText1 text,
  QPText2 text,
  QPText3 text,
  QPImage1 varchar(150) NOT NULL,
  QPImage2 varchar(150) NOT NULL,
  QPImage3 varchar(150) NOT NULL,
  QPImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (QPID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS mc_contentitem_qp_statement (
  QPSID int(11) NOT NULL AUTO_INCREMENT,
  QPSTitle1 varchar(255) NOT NULL,
  QPSTitle2 varchar(255) NOT NULL,
  QPSTitle3 varchar(255) NOT NULL,
  QPSTitle4 varchar(255) NOT NULL,
  QPSText1 text,
  QPSText2 text,
  QPSText3 text,
  QPSText4 text,
  QPSImage1 varchar(150) NOT NULL,
  QPSImage2 varchar(150) NOT NULL,
  QPSImage3 varchar(150) NOT NULL,
  QPSImage4 varchar(150) NOT NULL,
  QPSImageTitles text,
  QPSPosition int(11) NOT NULL,
  QPSDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (QPSID),
  UNIQUE KEY FK_CIID_QPSPosition_UN (FK_CIID,QPSPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition)
VALUES (
'45', 'ContentItemQP', '0', '63'
);

/******************************************************************************/
/*                         ModuleMediaLibrary                                 */
/******************************************************************************/

ALTER TABLE mc_module_medialibrary ADD MVideoDuration1 int(11) DEFAULT NULL AFTER MVideoType1;
ALTER TABLE mc_module_medialibrary ADD MVideoDuration2 int(11) DEFAULT NULL AFTER MVideoType2;
ALTER TABLE mc_module_medialibrary ADD MVideoDuration3 int(11) DEFAULT NULL AFTER MVideoType3;

ALTER TABLE mc_module_medialibrary ADD MVideoThumbnail1 varchar(150) NOT NULL AFTER MVideoType1;
ALTER TABLE mc_module_medialibrary ADD MVideoThumbnail2 varchar(150) NOT NULL AFTER MVideoType2;
ALTER TABLE mc_module_medialibrary ADD MVideoThumbnail3 varchar(150) NOT NULL AFTER MVideoType3;

ALTER TABLE mc_module_medialibrary ADD MVideoPublishedDate1 datetime DEFAULT NULL AFTER MVideoType1;
ALTER TABLE mc_module_medialibrary ADD MVideoPublishedDate2 datetime DEFAULT NULL AFTER MVideoType2;
ALTER TABLE mc_module_medialibrary ADD MVideoPublishedDate3 datetime DEFAULT NULL AFTER MVideoType3;

ALTER TABLE mc_module_medialibrary_category ADD MCIdentifier VARCHAR( 100 ) NOT NULL AFTER MCTitle;

/******************************************************************************/
/*                            ModuleTagcloud                                  */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('55', 'tagcloud', 'ModuleTagcloud', '0', '0', '0', '0', '0');

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('39', 'tagcloud', 'ModuleTagcloud', '0', '56', '0');

CREATE TABLE mc_module_tagcloud (
  TCID int(11) NOT NULL AUTO_INCREMENT,
  TCTitle varchar(255) NOT NULL,
  TCSize tinyint NOT NULL DEFAULT 0,
  TCInternalUrl varchar(255) NOT NULL,
  TCUrl varchar(255) NOT NULL,
  TCPosition tinyint NOT NULL DEFAULT 0,
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (TCID),
  KEY FK_CIID (FK_CIID),
  KEY FK_SID (FK_SID),
  UNIQUE KEY TCID_TCPosition_UN (TCID, TCPosition)
) ENGINE=MyISAM;

/******************************************************************************/
/*            FrontendUser - "Fax" und "Department" Feld ergaenzen            */
/******************************************************************************/

ALTER TABLE mc_frontend_user ADD FUFax VARCHAR( 50 ) NOT NULL AFTER FUMobilePhone;
ALTER TABLE mc_frontend_user ADD FUDepartment varchar(255) NOT NULL AFTER FUUID;

/******************************************************************************/
/*                  Download Tracking für User                                */
/******************************************************************************/

CREATE TABLE IF NOT EXISTS mc_frontend_user_history_download (
  FK_FUID int(11) NOT NULL,
  FUHDDatetime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FUHDFile varchar(150) NOT NULL DEFAULT '',
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM;

/******************************************************************************/
/*       [NEU] Tagging Level Ebene + Tagging von Inhaltstypen User            */
/******************************************************************************/

ALTER TABLE mc_contentitem ADD CTaggable TINYINT( 1 ) NOT NULL DEFAULT '0';

CREATE TABLE mc_contentitem_tag (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_TAID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_CIID_FK_TAID (FK_CIID, FK_TAID)
) ENGINE=MyISAM;

ALTER TABLE mc_module_tag_global
ADD TAGContent TINYINT( 1 ) NOT NULL DEFAULT '0'
AFTER TAGPosition;

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('40', 'taglevel', 'ModuleTagLevel', '0', '0', '0');
