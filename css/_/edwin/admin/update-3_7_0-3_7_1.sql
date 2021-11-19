/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Aufgrund der Änderungen bei den Modulberechtigungen für Backend Benutzer
 * sollten bei Kundenprojekten ALLE Benutzer einmal gespeichert werden. Damit
 * auch der Zugriff auch nur für das Hauptmodul ohne die Submodule möglich ist,
 * werden ab jetzt für jedes Modul in der Submodul DB-Tabelle Einträge verfasst.
 *
 * ModuleMultimediaSidebox:
 * Zu allen Template IFs in der LOOP 'sidebox_items' muss die Position hinzugefügt
 * werden, damit diese korrekt geparst werden:
 * a. "_{c_ms_position}" bei IFs deren Name mit einer Ziffer / Zahl endet
 * b. "{c_ms_position}" bei allen anderen IFs
 *
 * ContentItemMB
 * Da das Parsen von Boxen nicht mehr in einer LOOP gemacht wird, müssen die IFs
 * auch ohne "_{c_ms_position}" bzw. "{c_ms_position}" benannt werden, damit sie
 * korrekt geparst werden.
 *
 * [/INFO]
 */