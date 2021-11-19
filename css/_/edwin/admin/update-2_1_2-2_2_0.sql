/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO] 
 * Die Share Funktion kann nicht mehr auf traditionelle Weise konfiguriert werden
 * und muss ab dieser Version in der Datenbank als Modul aktiviert, sowie auch
 * in der Benutzerverwaltung für die entsprechenden Benutzer aktiviert werden.
 * Die "m_share" Konfigurationsvariable fällt weg.
 *
 * Die Informationenbox am Backend wird für Spezialseiten möglicherweise nicht
 * angezeigt, da diese in älteren Versionen manuell in der Datenbank angelegt
 * wurden und kein Erstelldatum in der log Tabelle (contentitem_log) besitzen.
 * [/INFO]
 */

/*********************************************************************************/
/* ModuleSiteindexCompendium - Zoom Link / großes Bild ausgeben + Bilduntertitel */
/*********************************************************************************/

ALTER TABLE mc_module_siteindex_compendium ADD SIImageTitles text AFTER SIImage3;

/******************************************/
/*              Blog Modul                */
/******************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) 
VALUES ('21', 'blog', 'ModuleBlog', '0', '0', '0');

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('22', 'blogmgmt', 'ModuleBlogManagement', '0', '8', '0');

ALTER TABLE mc_contentitem ADD CBlog tinyint(1) NOT NULL default 0 AFTER CShare;

CREATE TABLE mc_comments (
  CID int(11) NOT NULL auto_increment,
  CTitle varchar(150),
  CShortText text,
  CText text,
  CAuthor varchar(150),
  CEmail varchar(150),
  FK_CIID int(11),
  FK_UID int(11),
  FK_CID int(11) NOT NULL default 0,
  FK_FUID int(11),
  CCreateDateTime datetime NOT NULL default '0000-00-00 00:00:00',
  CChangeDateTime datetime NOT NULL default '0000-00-00 00:00:00',
  CChangedBy int(11),
  CPublished tinyint(1) NOT NULL default 0,
  CCanceled tinyint(1) NOT NULL default 0,
  CDeleted tinyint(1) NOT NULL default 0,
  PRIMARY KEY (CID)
) ENGINE=MyISAM;

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('23', 'share', 'ModuleShare', '0', '0', '0');

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages)
VALUES ('25', 'blog', 'ModuleBlog', '0', '0', '0', '0');

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages)
VALUES ('26', 'blogrecentcomments', 'ModuleBlogRecentComments', '0', '0', '0', '0');

/******************************************/
/*              Modul RssFeed             */
/******************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('24', 'rssfeed', 'ModuleRssFeed', '0', '9', '0');

CREATE TABLE mc_module_rssfeed (
  RID int(11) NOT NULL auto_increment,
  RTitle varchar(150) NOT NULL,
  RText text NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY  (RID)
) ENGINE=MyISAM;

CREATE TABLE mc_module_rssfeed_items (
  FK_SID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  UNIQUE KEY FK_SID_CIID_UN (FK_SID, FK_CIID)
) ENGINE=MyISAM;

/******************************************/
/*          Ankündigungsmodul             */
/******************************************/

CREATE TABLE IF NOT EXISTS `mc_module_announcement` (
 `AID` int(11) NOT NULL AUTO_INCREMENT,
 `ATitle` varchar(255) NOT NULL,
 `ADateTime` datetime DEFAULT NULL,
 `AText` text,
 `APosition` int(11) NOT NULL DEFAULT '0',
 PRIMARY KEY (`AID`)
) ENGINE=MyISAM;

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`) VALUES(25, 'announcement', 'ModuleAnnouncement', 0, 19, 0);
INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`) VALUES(37, 'announcement', 'ModuleAnnouncement', 0, 0, 0, 0); 
