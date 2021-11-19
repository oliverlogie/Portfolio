/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Edwin ist ab dieser Version nur noch mir Universal Analytics verwendbar. Bei
 * kundenspezifischen Entwicklungen, die spezielle Analytics Funktionen verwenden,
 * müssen die entsprechenden Codeteile an die neue Universal Analytics API
 * angepasst werden: https://developers.google.com/analytics/devguides/collection/analyticsjs/
 *
 * $_CONFIG['m_shorttext_cut_exact'] = false; konfiguriert nun die Kurztexte für
 * Inhalte bei Inhaltstypen und Modulen so, dass nur zwischen den Worten getrennt
 * wird. Sollen die Kurztexte einfach nur wieder nach den definierten x Zeichen
 * auch innerhalb eines Wortes abgeschnitten werden muss der Wert auf true
 * gestellt werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* ModuleMultimediaLibrary mit Zeitsteuerung / On- bzw. Offlineschalter       */
/******************************************************************************/

ALTER TABLE mc_module_medialibrary
ADD MShowFromDateTime DATETIME NULL AFTER MRandomlyShow ,
ADD MShowUntilDateTime DATETIME NULL AFTER MShowFromDateTime ,
ADD MDisabled TINYINT NOT NULL DEFAULT '0' AFTER MShowUntilDateTime;

/******************************************************************************/
/* moduletype_backend: MPosition Typ auf Integer ändern                       */
/******************************************************************************/

ALTER TABLE mc_moduletype_backend CHANGE MPosition MPosition INT NOT NULL DEFAULT '0';

/******************************************************************************/
/* Tracking von Downloads, Mail- und Externen-Links mit Google Analytics      */
/******************************************************************************/

CREATE TABLE mc_download_log (
  DLID int(11) NOT NULL AUTO_INCREMENT,
  DLDatetime datetime NOT NULL,
  DLFile varchar(150) NOT NULL,
  DLFiletypeType varchar(150) NOT NULL,
  DLFileableId int(11) NOT NULL DEFAULT 0,
  FK_FUID int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (DLID),
  KEY DLFileableId (DLFileableId),
  KEY FK_FUID (FK_FUID)
);