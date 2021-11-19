/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Erweiterung und Umbau von ModuleHtmlCreator: Ist das Modul beim Kundenprojekt
 * in Verwendung, muss das Layout und die Konfiguration für die neue
 * Funktionsweise übernommen werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* ModuleHtmlCreator: Erweiterung für Newslettervorlagenerstellung für        */
/* Rapidmail & Co.                                                            */
/******************************************************************************/

ALTER TABLE mc_module_html_creator_box
ADD HCBTemplate VARCHAR( 255 ) NOT NULL AFTER HCBImageTitles;

ALTER TABLE mc_module_html_creator CHANGE HCImageTitles HCUrl VARCHAR( 255 ) NOT NULL;
ALTER TABLE mc_module_html_creator_box CHANGE HCBImageTitles HCBUrl VARCHAR( 255 ) NOT NULL;

ALTER TABLE mc_module_html_creator ADD FK_SID INT NOT NULL AFTER HCCopiedFromID ,
ADD INDEX ( FK_SID );