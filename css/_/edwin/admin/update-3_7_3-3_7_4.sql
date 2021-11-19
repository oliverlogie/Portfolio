/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * ModuleMultimediaLibrary:
 * Folgende, fehlende display Klassen wurden hinzugefügt:
 * .display_ms_image4_title
 * .display_ms_image5_title
 * .display_ms_image6_title
 * Event. müssen diese Klassen in der Datei custom_config.css nachgezogen werden,
 * wenn die Bildtitel 4, 5 oder/und 6 sichtbar sein sollen.
 *
 * [/INFO]
 */

/******************************************************************************/
/*          ModuleGlobalArea: Verwaltung der Anzeige am Backend               */
/******************************************************************************/

DROP TABLE IF EXISTS mc_module_globalareamgmt_assignment;
CREATE TABLE mc_module_globalareamgmt_assignment (
  FK_GAID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  GAABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_GAID,FK_CIID)
) ENGINE=MyISAM;