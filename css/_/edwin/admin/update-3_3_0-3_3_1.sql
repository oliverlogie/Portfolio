/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * Am Frontend wurde jQuery von 1.5.1 auf die Version 1.8.3 aktualisiert.
 * Im Q2E-jQuery wurden Kommentare vereinheitlicht und aktualisiert.
 * Entfernete PlugIns (muessen wiederhergestellt werden, wenn in Verwendung):
 * - ie6.pngfix
 * - nivoslider
 * - wz_tooltip
 * Neu:
 * - jquery-tools.tabs_slideshow
 * Aktualisiert:
 * - jquery 1.8.3
 * - localscroll & scrollTo
 * Das JavaScript der Website sollte nach dem Update durchgetestet werden,
 * falls die neue jQuery Version verwendet werden soll.
 *
 * Shop Plus: Steuersätze pro Produkt einzeln definierbar
 * - $_CONFIG['cp_tax_rates']
 *   IDs müssen nun bei 1 beginnen, wobei 1 der Standardsteuersatz für Produkte
 *   = Wert von 'cp_tax_percentage' = sein muss
 * - [BE] $_LANG
 *   * pp_tax_rate_shortname: IDs müssen nun bei 1 beginnen
 *   * Soll bei Produkten direkt auch der Steuersatz angegeben werden können
 *     muss die Combobox am Backend durch .display_pp_product_tax_rate im
 *     custom_config.css eingeblendet werden
 * - [FE] $_LANG
 *   c_pp_product_tax_rate: IDs müssen nun bei 1 beginnen
 * Beim PP Datensatz ist nun immer ein Steuersatz definiert ( default = 1 ), bei
 * den Produkten wird bei 0 der PP Steuersatz übernommen, ansonsten der
 * produktspezifische Steuersatz verwendet.
 *
 * Templatesets werden nun nicht mehr im ContentRequest ausgelesen, sondern in
 * index.php gesetzt. Hier können kundenspezifische Änderungen gemacht werden.
 *
 * BE: ModuleMultimediaLibrary: Fuer Bild, Titel, Text und Video gibt es jetzt
 * jeweils eigene LANG2 Variablen.
 *
 * ModuleMultimediaLibrary, ContentItemVC: Die Standardkonfiguration wurde erweitert:
 * Es ist nun auch moeglich fuer die diversen Videoportale auch https Urls anzugeben.
 * Eventuell muss die Konfiguration angepasst werden, wenn in Verwendung.
 *
 * Neue Konfigurationsvariable $_CONFIG['charset'] muss bei Projekten korrekt
 * gesetzt werden:
 * - Standard: ISO-8859-1
 * - bei UTF-8 Projekten: UTF-8
 *
 * FE: ContentItemMB Performanceverbesserung.
 * Die LOOP fuer die MB Items wurde vom Template ContentItemMB_part.tpl entfernt.
 * Alle Items (ContentItemMB_part.tpl) werden gemeinsam in das Template ContentItemMB.tpl
 * geparst (Variable: c_mb_library_items). Auch im ContentItemMB.js wurden wichtige
 * Aenderungen umgesetzt.
 * Nicht mehr unterstetzte Variablen:
 * Template IF:
 *   library_site_info (wird ab dieser Version nicht mehr geparst)
 * Variablen:
 * - c_mb_category_title
 * - c_mb_category_url
 *
 * FE: ContentItemDL: Standardwert von $_CONFIG['dl_area_file_size_display_threshold'] auf 1KB
 * gesenkt. Layout sollte ueberprueft werden, moeglicherweise wurde nicht getestet
 * wie die Titelausgabe aussieht, wenn auch die Dateigroesse dabei steht.
 *
 * FE: Das Form Modul verwendet nun bei m_countries_use_deprecated=true
 * $_CONFIG['countries'], wenn $_CONFIG['c_fo_countries'] nicht konfiguriert
 * wurde.
 * BE: Lead Mgmt: Auch hier wird fuer vor folgenden Konfigurationsvariablen
 * $_CONFIG['countries'] als Fallback genutzt:
 * ln_countries, lh_countries, la_countries, lb_countries
 *
 * $_CONFIG['m_zoom_lightbox'] ist jetzt standardmaessig auf true gesetzt. Kann
 * also somit aus der config.php auch entfernt werden.
 * (Ausgabe von Bildern über ein Popup wird ohnehin nicht mehr verwendet.)
 *
 * [/INFO]
 */

/******************************************************************************/
/*                       Modul zur Lookup Verwaltung                          */
/******************************************************************************/

INSERT INTO mc_moduletype_backend
(MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
('52', 'lookupmgmt', 'ModuleLookupManagement', '0', '61', '0');

/******************************************************************************/
/*         Shop Plus - Steuersätze für PP Produkte einzeln definierbar        */
/******************************************************************************/

ALTER TABLE mc_contentitem_pp_product
ADD PPPTaxRate TINYINT( 4 ) NOT NULL DEFAULT '0' AFTER PPPShippingCosts;

UPDATE mc_contentitem_cp_order_item SET CPOITaxRate = CPOITaxRate + 1 WHERE 1;
UPDATE mc_contentitem_pp SET PPTaxRate = PPTaxRate + 1 WHERE 1;

ALTER TABLE mc_contentitem_pp
CHANGE PPTaxRate PPTaxRate TINYINT( 4 ) NOT NULL DEFAULT '1';

/******************************************************************************/
/*                     Newsletter Standardwert auf 0 setzen                   */
/******************************************************************************/

ALTER TABLE mc_client CHANGE CNewsletter CNewsletter TINYINT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE mc_frontend_user CHANGE FUNewsletter FUNewsletter TINYINT( 1 ) NOT NULL DEFAULT '0';

/******************************************************************************/
/*                         Fulltext Index fuer Words                          */
/******************************************************************************/

ALTER TABLE mc_contentitem_words ADD FULLTEXT (`WWord`);

/******************************************************************************/
/*                         Inhaltstyp QR entfernen                            */
/******************************************************************************/

DELETE FROM mc_contenttype WHERE CTID = 37;
