/**
 * [INFO]
 *
 * Zustimmung zur Datenverarbeitung
 *
 * Die Client Stammdaten wurde um CDataPrivacyConsent erweitert. Beim Lead-
 * Management und Formularen wird die Position 16 für diese Checkbox verwendet
 * (siehe Hinweise bei der Anleitung zur Konfiguration von Lead-Management)
 * und die Information wird in den Stammdaten vermerkt. Wird das Feld bereits
 * für ein Kampagnendatenfeld verwendet (CGDClientData = 0), dann wird das
 * Datenverarbeitung Feld am Backend in der Verwaltungsoberfläche nicht angezeigt.
 * Das Frontend funktioniert aber weiterhin korrekt. Wird das *Zustimmung zur
 * Datenverarbeitung* Feld nicht benötigt bei der Kampagne müssen beim
 * Kundenprojekt keine Änderungen gemacht werden. Ansonsten muss die
 * Kampagnenkonfiguration umgebaut werden.
 *
 * ---
 *
 * Automatisches Löschen von Kundendaten nach konfigurierbarem Zeitraum
 *
 * Dazu wurden die Standard Event Listener erweitert. Bei Kundenprojekten muss
 * mindestens die folgende Konfiguration vorhanden sein, wenn spezielle Einträge
 * in $_CONFIG['m_event_listeners'] gemacht wurden:
 *
 * $_CONFIG['m_event_listeners'] = array(
 *   array('Core\Events\NewsletterOptInRequested', 'Core\Listeners\NewsletterOptInRequestedMailListener'),
 *   array('Core\Events\BeforeStartContentRequestExecution', 'Core\Listeners\SystemCleanupListener'),
 * );
 *
 * Sollen Kundendaten weiterhin ohne zeitliche Begrenzung gespeichert werden
 * (Achtung - nicht DSGVO konform) muss die Konfigurationsvariable
 * $_CONFIG['m_system_cleanup_preserve_days'] auf einen sehr hohen Wert gesetzt
 * werden z.B.: $_CONFIG['m_system_cleanup_preserve_days'] = 365000; // 1000 Jahre
 *
 * [/INFO]
 */

/******************************************************************************/
/* LeadManagement / Client: Neues Feld für Datenschutz Zustimmung             */
/******************************************************************************/

ALTER TABLE mc_client
ADD CDataPrivacyConsent TINYINT(1) NOT NULL DEFAULT '0' AFTER CTitlePost;

/******************************************************************************/
/* Nicht benötigte Dateien von Uralt-Modul ModuleSiteindexTextOnly vom System */
/* entfernen                                                                  */
/******************************************************************************/

DROP TABLE IF EXISTS mc_module_siteindex_textonly;

/******************************************************************************/
/* Globales E-Mail Logging                                                    */
/******************************************************************************/

DROP TABLE IF EXISTS mc_log_simple;
CREATE TABLE mc_log_simple (
  ID bigint(20) NOT NULL AUTO_INCREMENT,
  Level varchar(10) NOT NULL COMMENT 'PSR-3 Log Levels: emergency, alert, critical, error, warning, notice, info, debug',
  DateTime datetime NOT NULL,
  Identifier varchar(25) NOT NULL COMMENT 'Use this column to define some kind of categorization based on i.e. log origin',
  User varchar(255) NOT NULL COMMENT 'user ID, IP address, ...',
  Data mediumtext NOT NULL COMMENT 'The data to log: serialized, plaintext, json, ...',
  DataType varchar(100) NOT NULL COMMENT 'The log type, to identify the type of this log entry, i.e. CronResult, Mail, ApiErrorResponse, ApplicationConfigurationError, ...',
  PRIMARY KEY (ID),
  KEY Level (Level),
  KEY Identifier (Identifier),
  KEY DataType (DataType)
) ENGINE=MyISAM;

/******************************************************************************/
/* Formulare / Lead Management: Verbesserung für Konfiguration für lange      */
/* Feldnamen                                                                  */
/******************************************************************************/

ALTER TABLE mc_campaign_data 
CHANGE CGDName CGDName TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';