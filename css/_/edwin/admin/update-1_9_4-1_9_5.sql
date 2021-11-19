/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
* [INFO]
* Die Tabellen mc_moduletype_frontend und mc_moduletype_backend müssen nach der
* Ausführung des Update-Scripts unbedingt überprüft werden, da das Update-Script
* aufgrund der Ausgangslage keine korrekten Daten garantieren kann (und somit
* alle Module deaktiviert).
*
* Ebenso wurde das doppelt verwendete Modul LeaguemanagerChart auf 
* LeaguemanagerChartSoccerAustria & LeaguemanagerChartBasketballAustria 
* aufgeteilt, hier müssen die Templates umbenannt werden.
* [/INFO]
*/

/*******************************************************/
/* Verbindung zwischen Employees und Sites korrigieren */
/*******************************************************/

/* Spalte FK_SID auf EShowOnSites umbenennen und Typ auf VARCHAR ändern */
ALTER TABLE mc_module_employee
CHANGE FK_SID EShowOnSites VARCHAR(255) NULL DEFAULT NULL;

/*************************************************/
/* Verfügbare Module über Datenbank verwalten    */
/*************************************************/

/* Spalte CTTitle wurde bereits durch $_LANG Array ersetzt */
ALTER TABLE mc_contenttype
DROP CTTitle;
/* Spalte CTSelectable umbenennen, damit es analog zu den Modulen benannt ist */
ALTER TABLE mc_contenttype
CHANGE CTSelectable CTActive TINYINT( 4 ) NOT NULL DEFAULT '1';

/* Neue Tabelle für die BE Module anlegen */
CREATE TABLE IF NOT EXISTS mc_moduletype_backend (
 MID int(11) NOT NULL AUTO_INCREMENT,
 MShortname varchar(50) NOT NULL,
 MClass varchar(50) NOT NULL,
 MActive tinyint(4) NOT NULL DEFAULT '1',
 MPosition tinyint(4) NOT NULL DEFAULT '0',
 PRIMARY KEY (MID),
 KEY MShortname (MShortname)
) ENGINE=MyISAM;

/* Vorhandene BE Module einfügen */
INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition) VALUES
(1, 'attribute', 'ModuleAttribute', 0, 6),
(2, 'booking', 'ModuleBooking', 0, 22),
(3, 'bugtracking', 'ModuleBugtracking', 1, 0),
(4, 'cmsindex', 'ModuleCmsindex', 1, 0),
(5, 'downloadticker', 'ModuleDownloadTicker', 0, 4),
(6, 'employee', 'ModuleEmployee', 0, 24),
(7, 'exportdata', 'ModuleExportData', 0, 7),
(8, 'infoticker', 'ModuleInfoTicker', 0, 3),
(9, 'leaguemanager', 'ModuleLeaguemanager', 0, 20),
(10, 'mediamanagement', 'ModuleMediaManagement', 0, 40),
(11, 'newsletter', 'ModuleNewsletter', 1, 1),
(12, 'newsticker', 'ModuleNewsTicker', 0, 2),
(13, 'realestate', 'ModuleRealEstate', 0, 23),
(14, 'sidebox', 'ModuleSideBox', 0, 5),
(15, 'siteindex', 'ModuleSiteindex', 1, 0),
(16, 'survey', 'ModuleSurvey', 0, 21),
(17, 'usermgmt', 'ModuleUserManagement', 1, 50);

/* Neue Tabelle für die FE Module anlegen */
CREATE TABLE IF NOT EXISTS mc_moduletype_frontend (
 MID int(11) NOT NULL AUTO_INCREMENT,
 MShortname varchar(50) NOT NULL,
 MClass varchar(50) NOT NULL,
 MActive tinyint(4) NOT NULL DEFAULT '1',
 PRIMARY KEY (MID),
 KEY MShortname (MShortname)
) ENGINE=MyISAM;

/* Vorhandene FE Module einfügen */
INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive) VALUES
(1, 'doublenestednavlevel2', 'ModuleDoubleNestedNavLevel2', 0),
(2, 'doublenestednavlevel3', 'ModuleDoubleNestedNavLevel3', 0),
(3, 'downloadticker', 'ModuleDownloadTicker', 0),
(4, 'flashelement', 'ModuleFlashElement', 0),
(5, 'flashelement2', 'ModuleFlashElement2', 0),
(6, 'flashelement3', 'ModuleFlashElement3', 0),
(7, 'flashnav', 'ModuleFlashNav', 0),
(8, 'flashnavlevel1', 'ModuleFlashNavLevel1', 0),
(9, 'flashnavlevel2', 'ModuleFlashNavLevel2', 0),
(10, 'flashnavlevel3', 'ModuleFlashNavLevel3', 0),
(11, 'infoticker', 'ModuleInfoTicker', 0),
(12, 'languageswitch', 'ModuleLanguageSwitch', 0),
(13, 'leaguemanagerchartsocceraustria', 'ModuleLeaguemanagerChartSoccerAustria', 0),
(14, 'leaguemanagerchartbasketballaustria', 'ModuleLeaguemanagerChartBasketballAustria', 0),
(15, 'leaguemanagercountdown', 'ModuleLeaguemanagerCountdown', 0),
(16, 'nestednavlevel2', 'ModuleNestedNavLevel2', 0),
(17, 'nestednavlevel3', 'ModuleNestedNavLevel3', 0),
(18, 'newsticker', 'ModuleNewsTicker', 0),
(19, 'recommend', 'ModuleRecommend', 0),
(20, 'sccart', 'ModuleSCCart', 0),
(21, 'sidebox', 'ModuleSideBox', 0),
(22, 'sitesmenu', 'ModuleSitesMenu', 0); 
