/**
 * [INFO]
 *
 * Die Konfigurationsvariable $_CONFIG['m_sitemap_active'] wurde vom System
 * entfernt. Sitemaps werden immer ausgeliefert.
 *
 * $_CONFIG['m_sitemap_cache_refresh_time'] ist nun nicht mehr seitenbasiert
 * zu konfigurieren. Bei Verwendung bitte auf
 *
 *     $_CONFIG['m_sitemap_cache_refresh_time'] = ...;
 *
 * ändern ( = Array Index für Seiten-ID entfernen ).
 *
 * Standardmäßig werden Seitentitel nun im Format <Inhalt> / <Ebene> / <Webseite>
 * ausgegeben, d.h. $_CONFIG['m_page_title_specific_to_general'] = true; Soll
 * die Reihenfolge beim Kundenprojekt wie bisher umgekehrt bleiben, muss in der
 * config.php $_CONFIG['m_page_title_specific_to_general'] = false; eingetragen
 * werden.
 *
 * [/INFO]
 */