/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * ModuleFacebookLikebox hat sich geändert. Es wurde das Facebook Like Page Plugin
 * durch das neue Facebook Page Plugin ersetzt. Die Konfigurationsvariablen in
 * $_CONFIG['fb_api'] haben sich dadurch geändert ( siehe
 * includes/config.defaults.php ).
 *
 * ContentItemES_EXT02 Youtube Videos wurde auf die Youtube API V3 umgestellt.
 * Wird der Contenttyp beim Kundenprojekt verwendet, müssen alle ES Inhalte am
 * Backend neu konfinguriert und gespeichert werden, da sich die Optionen
 * geändert haben. Außerdem muss über ein Google Konto des Kunden ein Youtube
 * API Key über https://console.developers.google.com/project erstellt werden.
 * Dannach muss $_CONFIG['m_youtube_data_api_key'] eingetragen werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/*            ContentItemCA: Feld für Externe Links bei Boxen                 */
/******************************************************************************/

ALTER TABLE mc_contentitem_ca_area_box
ADD CAABExtlink VARCHAR( 255 ) NOT NULL AFTER CAABLink;