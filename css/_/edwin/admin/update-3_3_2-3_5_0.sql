/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * EDWIN + UTF-8
 * -------------
 *
 * Folgende Dateien müssen bei einem Update manuell in UTF-8 konvertiert
 * werden:
 * - kundenspezifische Entwicklungen ( Module / Inhaltstypen )
 * - Language Dateien ( german, english, ... )
 *
 * Konvertierung der Datenbank:
 * - Export der Datenbank
 * - Konvertierung des DB-Dumps in UTF-8 mit Notepad++
 * - Ersetzen folgender Charset Anweisungen:
 *       DEFAULT CHARSET=latin1 => DEFAULT CHARSET=utf8
 *       CHARACTER SET latin1 => CHARACTER SET utf8
 *       COLLATE latin1_general_ci => COLLATE utf8_general_ci
 * - DB-Dump wieder importieren
 *
 * Änderungen an der Konfiguration:
 * - $_CONFIG['charset'] = 'UTF-8';
 * - $_CONFIG['dbcharset'] = 'utf8';
 * - $_CONFIG["m_language_code"] = array('german' => 'de_DE.UTF-8', ... );
 *
 * Die Datei config.live.php sollte auch zu UTF-8 konvertiert werden.
 *
 * Folgende Änderungen müssen für UTF-8 Kompatibilität bei kundenspezifischen
 * Enwticklungen gemacht werden:
 * - zu Beginn eines Skripts die Einstellung für UTF-8 Multibyte Funktionen:
 *   ------------------------------
 *   mb_internal_encoding('UTF-8');
 *   mb_regex_encoding('UTF-8');
 *   ------------------------------
 * - Multibyte String Funktionen verwenden
 *   http://php.net/manual/en/ref.mbstring.php
 * - Reguläre Ausdrücke bei PCRE Funktionen mit /u Modifikator
 *   http://www.regular-expressions.info/php.html#preg
 *
 * Es wurden Aenderungen in der mail.js bzw. decryptMail Funktion vorgenommen,
 * welche uebernommen werden muessen.
 *
 * FE: In der Datei main.tpl muessen folgende Variablen ersetzt werden:
 * m_nv_selection_level0 -> m_nv_main_selection_level0
 * m_nv_active_level -> m_nv_main_active_level
 *
 * FE & BE: Aus der main.inc.php wurden die Password Functions entfernt:
 * - checkPwQuality
 *   Ersetzen mit z.B.:
 *   $pwHelper = new Password();
 *   $pwHelper->setPassword($password)->getCalculatedQuality();
 * - createFrontendUserPassword, createPassword
 *   Ersetzen mit z.B.:
 *   $pwHelper = new Password();
 *   $pwHelper->setLength(ConfigHelper::get('m_login_password_length')) // BE Passwords: m_password_length
 *            ->setQuality(ConfigHelper::get('m_login_password_quality')) // BE Passwords: m_password_quality
 *            ->setTypes(ConfigHelper::get('m_login_password_types')) // BE Passwords: m_password_types
 *            ->create();
 *   $pwHelper->getPassword()
 * Diese muessen bei projektspezifischen Entwicklungen durch die Funktionen
 * des Password Helper Objektes ersetzt werden.
 *
 * [/INFO]
 */