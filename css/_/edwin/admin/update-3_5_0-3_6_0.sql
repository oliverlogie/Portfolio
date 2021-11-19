/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * $_CONFIG['dbcharset'] = 'utf8'; wurde in die Standardkonfigurationswerte
 * eingetragen, d.h. dass der Standardwert, wenn keine Definition mehr gemacht
 * wurde nun immer UTF-8 ist. Sollte das Projekt noch unter latin1 laufen muss
 * das in der Konfiguration eingetragen werden
 *
 * Änderungen bei den Konfigurationsvariablen für GeneralSurvey / ModuleSurvey /
 * ContentItemSU: Das Datumsformat muss wieder für PHP date() konfiguriert
 * werden:
 * $_CONFIG["g_sur_date_format"] = "d.m.Y";
 *
 * ContentItemQP: Neue Standardkonfiguration am FE: Bereiche werden nur
 * ausgegeben wenn Linktitel/-text/-bild und ein Bereichstitel/-text/-bild
 * vorhanden ist. Sollen leere QP Bereiche trotzdem ausgegeben werden, dann kann
 * qp_ignore_empty_statements auf false gesetzt werden.
 *
 * Die Standardbildgröße der großen Bilder hat sich von 320x240 auf 600x450
 * geändert. Wurde diese Standardgröße verwendet, dann muss ab jetzt die
 * spezielle Bildkonfiguration für den jeweiligen Inhaltstyp verwendet werden:
 * $_CONFIG['m_large_image_width'] = 320;
 * $_CONFIG['m_large_image_height'] = 240;
 *
 * [/INFO]
 */
/******************************************************************************/
/*     CP - Prozess bei externen Bezahlungsarten verbessern - MPAY24          */
/******************************************************************************/

ALTER TABLE mc_contentitem_qp_statement
ADD QPSImage5 VARCHAR( 150 ) NOT NULL AFTER QPSImage4,
ADD QPSImage6 VARCHAR( 150 ) NOT NULL AFTER QPSImage5,
ADD QPSImage7 VARCHAR( 150 ) NOT NULL AFTER QPSImage6,
ADD QPSImage8 VARCHAR( 150 ) NOT NULL AFTER QPSImage7,
ADD QPSImage9 VARCHAR( 150 ) NOT NULL AFTER QPSImage8,
ADD QPSImage10 VARCHAR( 150 ) NOT NULL AFTER QPSImage9,
ADD QPSImage11 VARCHAR( 150 ) NOT NULL AFTER QPSImage10;

/******************************************************************************/
/*     CP - Prozess bei externen Bezahlungsarten verbessern - MPAY24          */
/*          Session Daten bei Bestellung mitspeichern                         */
/******************************************************************************/

ALTER TABLE mc_contentitem_cp_order
ADD CPOTransactionSessionData TEXT NOT NULL AFTER CPOTransactionStatus;

/******************************************************************************/
/*                            Mobile Startseite                               */
/******************************************************************************/

ALTER TABLE mc_module_siteindex_compendium ADD SIType VARCHAR( 255 ) NOT NULL AFTER FK_SID;
ALTER TABLE mc_module_siteindex_compendium_area ADD SASiteindexType VARCHAR( 255 ) NOT NULL AFTER FK_SID;
ALTER TABLE mc_module_siteindex_compendium_area DROP INDEX FK_SID_SAPosition_UN;

CREATE TABLE mc_module_siteindex_compendium_mobile (
  SIMID int(11) NOT NULL AUTO_INCREMENT,
  SIMText text NOT NULL,
  SIMType varchar(150) NOT NULL,
  SIMPosition int(11) NOT NULL DEFAULT '0',
  SIMActive tinyint(1) NOT NULL DEFAULT '1',
  FK_SIMID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SIMID),
  KEY FK_SIMID (FK_SIMID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

/******************************************************************************/
/*               Startseite - Interne und externe Links                       */
/******************************************************************************/

ALTER TABLE mc_module_siteindex_compendium ADD SIExtlink VARCHAR( 255 ) NOT NULL AFTER FK_CIID;
ALTER TABLE mc_module_siteindex_compendium_area ADD SAExtlink VARCHAR( 255 ) NOT NULL AFTER FK_CIID;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD SBExtlink VARCHAR( 255 ) NOT NULL AFTER FK_CIID;