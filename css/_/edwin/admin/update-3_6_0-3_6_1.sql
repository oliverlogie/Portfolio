/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Die CMS Funktion zur Anzeige von großen Bildern in einem Popup wurde vom
 * System entfernt. Die Konfigurationsvariable 'm_zoom_lightbox' kann deshalb
 * entfernt werden ( keine Auswirkung mehr ).
 *
 * Der Template Cache für Inhaltstypen und Module am Frontend liegt nun unter
 * storage/cache/templates: Alte Cache Verzeichnisse unter
 * - templates/content_types/cache
 * - templates/mdoules/cache
 * sollten beim Kundenprojekt entfernt werden ( SVN löscht diese Verzeichnisse
 * nicht, wenn sich Dateien darin befinden ).
 *
 * [/INFO]
 */

/******************************************************************************/
/*     Multimediaboxen: Anzahl der möglichen Bilder auf 6 erhöhen             */
/******************************************************************************/

ALTER TABLE mc_module_medialibrary
ADD MImage4 VARCHAR( 150 ) NOT NULL AFTER MImage3,
ADD MImage5 VARCHAR( 150 ) NOT NULL AFTER MImage4,
ADD MImage6 VARCHAR( 150 ) NOT NULL AFTER MImage5;