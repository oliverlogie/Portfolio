/**
 * [INFO]
 *
 * $_LANG['global_mail_sender_label'] wurde entfernt und wird nun in
 * $_CONFIG['m_mail_sender_label'] konfiguriert. Bei Kundenprojekten müssen die
 * konfigurierten E-Mail Adressen von $_LANG in $_CONFIG übertragen und aus
 * den lang.core.php Dateien entfernt werden. Außerdem muss das gesamte
 * Kundenprojekt nach global_mail_sender_label durchsucht und durch über
 * $_CONFIG bzw. den ConfigHelper ausgelesen werden.
 *
 * [/INFO]
 */