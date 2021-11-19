/******************************************************************************/
/* ModuleMultimediaSidebox: Zufällige Anzeige aus mehreren Kategorien         */
/* unabhängig von der Reihenfolge am Backend                                  */
/******************************************************************************/

ALTER TABLE mc_module_medialibrary ADD MRandomlyShow tinyint(4) NOT NULL DEFAULT '0' AFTER MImageTitles;