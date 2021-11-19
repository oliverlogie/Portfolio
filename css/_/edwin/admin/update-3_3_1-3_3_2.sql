/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * Es existiert ein PHP-Update-Script, welches nach diesem SQL-Update-Script
 * ausgeführt werden muss. Dafür muss man über den Browser
 * /edwin/admin/update-3_3_1-3_3_2.php aufrufen. Das Skript führt folgende
 * Aktionen durch:
 * - Aktualisieren der Versandart-Positionen im Shop Plus
 *
 * Es gibt nun Selection Arrays fuer alle Trees. Die Templatevariable
 * {m_nv_selection_level<0,1,2,...>} wurde umbenannt in
 * {m_nv_main_selection_level<0,1,2,...>}.
 * Suche nach '{m_nv_selection_level' und ersetze mit '{m_nv_main_selection_level'.
 * {m_nv_active_level} wurde umbenannt in {m_nv_main_active_level}
 *
 * [/INFO]
 */

/******************************************************************************/
/*                   Shop Plus - Position fuer Versandarten                   */
/******************************************************************************/

ALTER TABLE `mc_contentitem_cp_shipment_mode` ADD `CPSPosition` INT NOT NULL DEFAULT '0' AFTER `CPSPrice`;
