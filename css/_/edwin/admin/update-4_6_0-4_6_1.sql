/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
* [INFO]
*
* $_CONFIG['m_debug'] = true; hat sich als Standardwert der
* Konfigurationsvariable geändert. Deshalb muss zumindest in config.live.php die
* Einstellung  $_CONFIG['m_debug'] = false; gesetzt werden. Notwendig wurde
* diese Änderung um im EDWIN standardmäßig auch die Verwendung von v_debug() in
* PHP Templates zu aktivieren.
*
* Der Inhaltstyp *PB* (ContentItemPB) wurde in seiner Funktionalität komplett
* umgebaut. Er ist nun keine Produkt-Anreißerebene mehr, sondern vielmehr eine
* Anreißerebene für beliebige Beiträge mit Mehr-Laden Funktionalität. Zu jeder
* Teaser-Box wird außerdem das "Haupt-Schlagwort" ausgegeben. Außerdem wird, bei
* der Verwendung des ModuleProductBoxLevelTagFilter Modules mit Ebenen-Tagging
* Funktionalität, auch der Tag-Filter für die Ebene dazu ausgegeben.
*
* [/INFO]
*/

/******************************************************************************/
/* Modul zur Anzeige von Beiträgen aus neuer *PB* Ebene                       */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser, MAvailableOnSites) VALUES
(70, 'productboxteaser', 'ModuleProductBoxTeaser', 0, 0, 0, 0, 0, '0');