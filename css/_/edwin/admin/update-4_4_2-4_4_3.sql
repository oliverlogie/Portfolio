/**
 * [INFO]
 *
 * Anleitung: DB-Zeichensatz Umstellung auf *utf8mb4* (nachdem dieses DB-Update
 * Skript ausgeführt wurde)
 *
 * 1. DB-Dump sichern
 * 2. DB-Dump kopieren
 * 3. *utf8* durch *utf8mb4* ersetzen
 *    zur allgemeinen Bereinigung: Achtung bei Kundenprojekten mit kundenspezifischen Entwicklungen
 *    *latin1* durch *utf8mb4* ersetzen
 *    *InnoDB* durch *MyISAM* ersetzen
 * 4. DB-Dump einspielen
 * 5. $_CONFIG['dbcharset'] = 'utf8mb4'; setzen oder entfernen (Standard: utf8mb4)
 *
 * Weitere Informationen:
 * https://mathiasbynens.be/notes/mysql-utf8mb4
 *
 * Bei Fehler "#1071 - Specified key was too long;":
 * Es muss die entsprechende Spalte / die entsprechenden Spalten in ihrer Länge
 * reduziert werden, bzw. ein anderer Typ gewählt werden.
 * Bei VARCHAR wurde VARCHAR(255) in VARCHAR(191) geändert (siehe Statements
 * unten) wenn ein INDEX auf die Spalte erstellt wurde.
 * https://stackoverflow.com/questions/1814532/1071-specified-key-was-too-long-max-key-length-is-767-bytes
 *
 * Der Standardwert für $_CONFIG['qp_ignore_empty_statements'] hat sich auf
 * $_CONFIG['qp_ignore_empty_statements'] = false; geändert. Beim Kundenprojekt
 * muss bei verwendetem QP Inhalt und gewünschter ehemaliger Standardeinstellung
 * $_CONFIG['qp_ignore_empty_statements'] = true; gesetzt werden.
 *
 * Der Standardwert für $_CONFIG['be_allowed_html_level1'] hat sich auf
 * $_CONFIG['be_allowed_html_level1'] = '<br><b><a><ul><li><br><i><u><sub><sup><span><table><thead><tbody><tr><td><th><iframe>';
 * geändert, damit IFRAMEs für Google Maps o.ä. standardmäßig eingebaut werden
 * können. Um beim Kundenprojekt die ehemalige Standardeinstellung wiederherzustellen
 * muss $_CONFIG['be_allowed_html_level1'] = '<br><b><a><ul><li><br><i><u><sub><sup><span><table><thead><tbody><tr><td><th>';
 * gesetzt werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* DB Zeichensatz Umstellung auf *utf8mb4*                                    */
/******************************************************************************/

/* reduce col length to make index work as utf8mb4 requires 4 bytes and the
   maximum myisam index length 5.5 - 5.7 is 1000 bytes */

ALTER TABLE mc_contentitem_words CHANGE WWord WWord varchar(191) NOT NULL DEFAULT '';
ALTER TABLE mc_module_attribute_global CHANGE AIdentifier AIdentifier varchar(191) DEFAULT NULL;

/******************************************************************************/
/* CA Area um externen Link (wie bei Startseite) erweitern                    */
/******************************************************************************/

ALTER TABLE `mc_contentitem_ca_area` ADD `CAAExtlink` VARCHAR(255) NOT NULL AFTER `CAALink`;