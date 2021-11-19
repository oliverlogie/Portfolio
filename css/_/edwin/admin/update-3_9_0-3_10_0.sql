/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Bei TG Inhaltstypen müssen folgende Änderungen beachtet werden:
 * - Backend
 *   * Die Language Dateien haben sich geändert
 *   * custom_config.css muss bei Kundenprojekten angepasst werden, wenn Beschreibungstitel und -Text verwendet werden sollen
 * - Frontend
 *   * Folgende Template Variablen wurden ersetzt ( Achtung, die in der ursprünglichen Version werden anderen Werte gepartst )
 *     > c_tg_image_before_title > c_tg_image_before_subtitle
 *     > c_tg_image_after_title  > c_tg_image_after_subtitle
 *
 * [/INFO]
 */

/* CHARACTER SET für Tabellen korrigieren */

ALTER TABLE mc_download_log CONVERT TO CHARACTER SET utf8;
ALTER TABLE mc_module_globalareamgmt_assignment CONVERT TO CHARACTER SET utf8;
ALTER TABLE mc_module_siteindex_compendium_mobile CONVERT TO CHARACTER SET utf8;
ALTER TABLE mc_module_tagcloud_category CONVERT TO CHARACTER SET utf8;

/* Storage ENGINE für Tabellen korrigieren */

ALTER TABLE mc_download_log ENGINE=MyISAM;

/******************************************************************************/
/* TG - Erweiterung der Galleriebilder um Titel, Text und Bilduntertitel wie  */
/*                              auch bei BG                                   */
/******************************************************************************/

ALTER TABLE mc_contentitem_tg_image ADD TGIText TEXT AFTER TGITitle;
ALTER TABLE mc_contentitem_tg_image ADD TGIImageTitle VARCHAR(150) DEFAULT NULL AFTER TGIImage;

UPDATE mc_contentitem_tg_image SET TGIImageTitle = TGITitle, TGITitle = '';

/******************************************************************************/
/*             GlobalArea - Externe Links für Bereiche und Boxen              */
/******************************************************************************/

ALTER TABLE mc_module_global_area
ADD GAExtlink VARCHAR(255) NOT NULL DEFAULT '' AFTER FK_CIID;

ALTER TABLE mc_module_global_area_box
ADD GABExtlink VARCHAR(255) NOT NULL DEFAULT '' AFTER FK_CIID;