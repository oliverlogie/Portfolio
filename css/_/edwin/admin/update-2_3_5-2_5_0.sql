/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 
 * Im lang.core.php wurden die HTML Tags (DIV) der Fehlernachrichten der Funktion
 * ContentBase::_storeFile entfernt. Die Funktion wurde nur im ContenItemCC verwendet.
 * Das DIV mit der Error CSS Klasse wird nun direkt im Message-IF des Templates verwendet.
 
 * [BE] lang.core.php bindet nun keine weiteren Language Files ein, wenn die
 *      Variable $init gesetzt wird (edwin/index.php). Es muss in allen custom 
 *      Language Files der Code im unteren Bereich angepasst werden, damit auch
 *      das custom lang.core.php File auf die $init Option reagieren kann.
 *
 * Am Frontend werden Daten, deren Format konfigurierbar ist / war, mittels
 * strftime() ausgegeben. (http://php.net/manual/de/function.strftime.php)
 * Hier eine Liste der Konfigurationsvariablen, die im Zuge der Umstellung /
 * Vereinheitlichung geändert wurden, und möglicherweise angepasst werden müssen:
 * // modules + content items
 * - $_CONFIG['dl_area_file_date_format']
 * - $_CONFIG["sc_date_format"]
 * - $_CONFIG["sp_date_format"]
 * - $_CONFIG["su_date_format"]
 * - $_CONFIG["vj_date_format"]
 * - $_CONFIG["vj_time_format"]
 * - $_CONFIG["vy_date_format"]
 * - $_CONFIG["vy_time_format"]
 * - $_CONFIG["m_downloads_created_format"]
 * - $_CONFIG["m_downloads_modified_format"]
 * - $_CONFIG["m_downloads_date_format"]
 * - $_CONFIG["m_metainfo_date_format"]
 * - $_CONFIG["lm_date_format"];
 * // generals
 * - $_CONFIG["g_boo_date_format"] = "%d.%m.%Y";
 * - $_CONFIG["g_boo_datetime_format"] = "%d.%m.%Y %H:%M";
 * - $_CONFIG["g_boo_timeline_date_format"] = "%d.%m.";
 * - $_CONFIG["g_sur_date_format"] = "%d.%m.%Y";
 * // generals - neue CONFIG am Backend zur Unterscheidung von Frontend
 * - $_CONFIG["g_boo_be_date_format"] = "d.m.Y";
 * - $_CONFIG["g_boo_be_datetime_format"] = "d.m.Y H:i";
 * - $_CONFIG["g_boo_be_timeline_date_format"] = "d.m.";
 * - $_CONFIG["g_sur_be_date_format"] = "d.m.Y";
 * Änderungen bei der Datumskonfiguration über $_LANG:
 * - $_LANG["c_an_announcement_date_format"]
 * - $_LANG["c_lc_game_date_format"] 
 *
 * custom_config_default.css Anpassungen für Zusatzdaten in logischen Ebenen.
 * Ein - und Ausblenden von Titel, Text und Bild für jeden Inhaltstypen getrennt
 * möglich (BE, IB, IP, VA). Die "display" Klassen sollten überprüft und
 * angepasst werden. Außerdem müssen die Tabs im oberen Bereich von Contentitems
 * "Inhalt", "Downloads", "Interne Links", "Externe Links", "Verknüpfungen", 
 * "Kommentare" im custom_config extra eingeblendet werden (wie auch andere
 * Elemente).
 * 
 * [BE] Update Fancybox
 * [FE] Update von jQuery UI: v 1.8.12, jQuery: v 1.5.1, Fancybox: v 1.3.4
 * Versionsnummer aus Ordnern bzw. Dateinamen entfernt - im main.tpl muss der 
 * Pfad zu den files kontrolliert werden.
 * 
 * [FE] Einige Javascript-files wurden gelöscht bzw. in andere files integriert,
 * Ordner und Dateien wurden umbenannt
 *
 * Benutzer 'bis' über die Backend-Benutzer-Verwaltung entfernen.
 *
 * [/INFO]
 */

/******************************************************************************/
/*               Neues Modul "NestedFooterNavLevel1"                          */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES ('48', 'nestedfooternavlevel1', 'ModuleNestedFooterNavLevel1', '0', '0', '0', '0', '0');

/******************************************************************************/
/*               Verschieben von Seiten bzw. Ästen im BE                      */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) 
VALUES ('31', 'treemgmtall', 'ModuleTreeManagementAll', '0', '0', '0');
INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) 
VALUES ('32', 'treemgmtleafonly', 'ModuleTreeManagementLeafOnly', '0', '0', '0');

/******************************************************************************/
/*                              Inhaltstyp DU                                 */
/******************************************************************************/

ALTER TABLE `mc_client_uploads` ADD `CUViewed` TINYINT( 1 ) NOT NULL DEFAULT '0';

CREATE TABLE mc_contentitem_du (
  DUID int(11) NOT NULL AUTO_INCREMENT,
  DURecipient varchar(150) NOT NULL,
  DUTitle varchar(150) NOT NULL,
  DUText1 text,
  DUText2 text,
  DUText3 text,
  DUImage varchar(150) NOT NULL,
  DUType tinyint(4) NOT NULL DEFAULT '1',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DUID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES
(40, 'ContentItemDU', 0, 58);

ALTER TABLE `mc_client_uploads` CHANGE `CUFile` `CUFile` VARCHAR( 150 ) NOT NULL;
