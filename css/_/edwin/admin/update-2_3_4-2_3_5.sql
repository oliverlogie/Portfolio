/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Am Frontend wurde die volle Seiten URL globalisiert (ContentBase->site_url) und wird jetzt
 * von jedem ContentItem/Modul verwendet. Fuer die Content Items aendert sich dadurch nichts.
 * Bei den Modulen wurde bei allen Site URL Template Variablen am Beginn http:// und am
 * Ende das Slash entfernt. Die Template Variable c_surl von ModuleGlobalSideBox wird ab 
 * jetzt mit beginnenden http:// und am Ende mit Slash ausgegeben. Bitte alle Links ueberpruefen!
 * 
 * Die urspruenglich in jedem Content Item geparste Template Variable m_recommend_part,
 * sowie die main_recommend vom main.tpl wurden entfernt. Ab jetzt kann das ModuleRecommend
 * wie jedes andere Modul verwendet werden. Das Template ModuleRecommend.tpl wurde umbenannt in
 * ModuleRecommendForm.tpl und das Template main_recommend_part.tpl wurde umbenannt in
 * ModuleRecommend.tpl.
 *
 * Die Backend Konfigurationsvariable $_CONFIG['m_url_protocols'] muss nu den gesamten
 * Protokoll String enthalten (bisher z.B. 'http'), d.h. 'http://', 'ftp://', ..
 * 
 * Standardmäßig werden ab dieser Version (2.3.5) alle Länderkonfigurationen aus
 * der Datenbank gelesen. Um weiterhin die Werte der $_CONFIG Variablen direkt
 * zu verwenden (CC, NL, SU, SC, ...) sollte die Konfigurationsvariable
 * $_CONFIG["m_countries_use_deprecated"] = true gesetzt werden. In 
 * edwin/admin/config.sql finden sich unterschiedliche Konfigurationsmöglichkeiten
 * über die Datenbank.
 *
 * Durch Javascript und Stylesheet Änderungen + weitere Performanceverbesserungen
 * am Backend:
 * - main.tpl: css / js entfernt + js am Ende von <body>
 *             Template genau überprüfen, Initialisierung des TinyMCE Plugins
 *             ohne content_css Option
 * - be.js: neues js File enthält actionsboxes.js, globalfunctions.js, nav.js, 
 *          Previewbox.js (alte Files wurden entfernt)
 * - cms_main.css: enthält nun css Styles aus cms_buttons.css, cms_style_mod-booking.css,
 *                 cms_style_mod-leaguemanager.css, cms_style_mod-mediamanagement.css,
 *                 cms_style_mod-survey.css (alte Files entfernt)
 * - cms_calendar.css: entfernt
 * - cms_editor.css: entfernt
 * - cms_large_screen.css: entfernt + Styles befinden sich nun in custom_config_default.css
 *                         und müssen bei Kundenprojekten ins custom_config.css
 *                         eingetragen werden, wenn das breite Layout aktiviert
 *                         werden soll
 * - cms_editorstyles.css: entfernt + alle Styles müssen nun in
 *                         edwin/prog/tps/tiny_mce/themes/advanced/skins/default/content.css
 *                         eingetragen werden
 * 
 * [/INFO]
 */

/******************************************************************************/
/*               ContentItemCA Position Locked Funktionalitaet                */
/******************************************************************************/

ALTER TABLE `mc_contentitem_ca_area_box` DROP `CAABPositionLocked`;

/******************************************************************************/
/*                        Neues Modul "Newsletter"                            */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` 
(`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`, `MActiveUser`) VALUES 
('47', 'newsletter', 'ModuleNewsletter', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                        DB-Schema Korrekturen                               */
/******************************************************************************/

ALTER TABLE mc_comments ADD INDEX ( FK_CIID );
ALTER TABLE mc_comments ADD INDEX ( FK_UID );
ALTER TABLE mc_comments ADD INDEX ( FK_CID );
DROP TABLE mc_contentitem_be_item_data;
ALTER TABLE mc_contentitem_cb_box_biglink CHANGE BLImageTitles BLImageTitles TEXT NOT NULL;
ALTER TABLE mc_contentitem_cb_box_smalllink CHANGE SLLink SLLink INT( 11 ) NOT NULL;
ALTER TABLE `mc_contentitem_login` ADD INDEX ( `FK_CIID` );
ALTER TABLE `mc_contentitem_ts_block_link` CHANGE `TLLink` `TLLink` INT( 11 ) NOT NULL;
ALTER TABLE `mc_frontend_user` DROP INDEX `CID`;
ALTER TABLE `mc_site` ADD INDEX ( `FK_SID_Language` );
ALTER TABLE `mc_site` ADD INDEX ( `FK_SID_Portal` );