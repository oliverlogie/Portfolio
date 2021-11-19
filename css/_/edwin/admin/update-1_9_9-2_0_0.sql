/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Es existiert ein PHP-Update-Script, welches nach diesem SQL-Update-Script 
 * ausgeführt werden muss. Dafür muss man sich am BE als Admin (User mit 
 * Rechten für ModuleUserManagement) anmelden und dann über den Browser 
 * /edwin/admin/update-1_9_9-2_0_0.php aufrufen.
 *  
 * Bei der Verbesserung von ContentItemBG wurden Änderungen am Anreißerbild
 * vorgenommen, deshalb sollte es bei jedem BG neu hochgeladen werden.
 * 
 * Alle ContentItemIG werden auf ContentItemBG konvertiert, dabei gehen die 
 * Bilduntertitel verloren, da es diese im ContentItemBG nicht gibt. Auch hier
 * müssen die Anreißerbilder neu hochgeladen werden. Und natürlich sollten die
 * FE-Templates für ContentItemBG angepasst werden.
 * 
 * Alle ContentItemTA und ContentItemIG Levels werden durch die neue logische
 * Archiv-Ebene ersetzt (ContentItemArchive). Alle Konfigurationsvariablen, 
 * die mit "le_" beginnen müssen auf "ar_" umbenannt werden.
 * Die Language-Files und Templates für ContentItemLevel (FE und BE) wurden auf 
 * ContentItemArchive umbenannt.
 * Das ContentItemTA-Template am FE gibt es nicht mehr (wurde für die Ausgabe 
 * von TIs innerhalb von TAs verwendet), stattdessen gibt es jetzt zwei globale 
 * IFs, die in jedem ContentItem verwendet werden können: "inside_archive" und 
 * "outside_archive".
 *
 * Der Tabelle mc_site wird mit diesem Updateskript eine Spalte SLanguage mit dem
 * Defaultwert 'german' hinzugefügt. Hier werden durch das PHP-Update-Script die
 * Werte aus der CONFIG Variable 'site_languages' übernommen. Sie sollten jedoch
 * manuell überprüft und falls nötig korrigiert werden. Die CONFIG Variable sollte
 * danach auf alle Fälle entfernt werden, da sie immer in der index.php mit Werten
 * aus der Datenbank befüllt wird.
 * [/INFO]
 */

/***********************************************/
/* Sperrsystem für ContentItems implementieren */
/***********************************************/

ALTER TABLE mc_contentitem
ADD CDisabledLocked TINYINT(1) NOT NULL DEFAULT 0 AFTER CDisabled;

/****************************/
/* ContentItemBG verbessern */
/****************************/

/* Tabelle mc_contentitem_bg_image erstellen */
CREATE TABLE mc_contentitem_bg_image (
  BIID int(11) NOT NULL AUTO_INCREMENT,
  BITitle varchar(150) DEFAULT NULL,
  BIText text DEFAULT NULL,
  BIImage varchar(150) NOT NULL,
  BIPosition int(11) DEFAULT NULL,
  FK_CIID int(11) NOT NULL,
  PRIMARY KEY (BIID),
  UNIQUE KEY FK_CIID_BIPosition_UN (FK_CIID, BIPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

/* Tally Table erstellen und befüllen (enthält einfach Werte von 1 bis X in der Spalte N) */
CREATE TEMPORARY TABLE mc_contentitem_bg_image_tally (
  N INT NOT NULL AUTO_INCREMENT,
  X INT NOT NULL,
  PRIMARY KEY (N)
) ENGINE=MyISAM;
INSERT INTO mc_contentitem_bg_image_tally(X)
SELECT SID * CTID * MID * UID
FROM mc_site, mc_contenttype, mc_moduletype_frontend, mc_user;
ALTER TABLE mc_contentitem_bg_image_tally
DROP COLUMN X;

/* Spalten GImage und GMoreImages zusammenführen */
CREATE TEMPORARY TABLE mc_contentitem_bg_image_images AS
SELECT FK_CIID, CONCAT(',', GImage, IF(LENGTH(GImage) > 0 && LENGTH(GMoreImages) > 0, ',', ''), GMoreImages, ',') AS GImages
FROM mc_contentitem_bg
WHERE LENGTH(GImage) > 0
OR LENGTH(GMoreImages) > 0;

/* Spalten GImage und GMoreImages mit Hilfe der Tally Table auf einzelne Zeilen aufteilen */
INSERT INTO mc_contentitem_bg_image (FK_CIID, BIImage)
SELECT FK_CIID, SUBSTRING(GImages, N + 1, LOCATE(',', GImages, N+1) - N - 1)
FROM mc_contentitem_bg_image_tally, mc_contentitem_bg_image_images
WHERE N < LENGTH(GImages)
AND SUBSTRING(GImages, N, 1) = ','
ORDER BY FK_CIID, N;

/* Spalte BIPosition befüllen */
/* Hilfstabelle mc_contentitem_bg_image_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_contentitem_bg_image_positions_min
AS SELECT FK_CIID, MIN(BIID) AS MIN_ID
FROM mc_contentitem_bg_image
GROUP BY FK_CIID;
/* Spalte BIPosition befüllen */
UPDATE mc_contentitem_bg_image a
SET BIPosition = 1 + BIID - (
  SELECT MIN_ID
  FROM mc_contentitem_bg_image_positions_min b
  WHERE b.FK_CIID = a.FK_CIID
);

/* Spalte BIPosition NOT NULL */
ALTER TABLE mc_contentitem_bg_image
CHANGE BIPosition BIPosition int(11) NOT NULL;

/* Spalten GImage, GImageTitles und GMoreImages entfernen */
ALTER TABLE mc_contentitem_bg
DROP COLUMN GImage,
DROP COLUMN GImageTitles,
DROP COLUMN GMoreImages;

/* Index FK_GID korrekt umbenennen auf FK_CIID */
ALTER TABLE mc_contentitem_bg
DROP INDEX FK_GID,
ADD INDEX FK_CIID (FK_CIID);

/* Bestehende Spalten korrigieren und fehlende Spalten ergänzen */
ALTER TABLE mc_contentitem_bg
CHANGE GText1 GText1 text DEFAULT NULL,
ADD GImage varchar(150) NOT NULL AFTER GText3;


/********************************************/
/* ContentItemIG in ContentItemBG umwandeln */
/********************************************/

/* Für jeden ContentItemIG-Level einen ContentItemBG erstellen */
INSERT INTO mc_contentitem_bg (FK_CIID)
SELECT CIID
FROM mc_contentitem
WHERE FK_CTID = 55;

/* Typ aller bestehenden ContentItemIG-Levels auf ContentItemBG ändern */
UPDATE mc_contentitem
SET CType = 1,
    FK_CTID = 11
WHERE FK_CTID = 55;

/* ContentItemBG-Images aus ContentItemIG-Images erstellen */
INSERT INTO mc_contentitem_bg_image (BITitle, BIText, BIImage, BIPosition, FK_CIID)
SELECT ITitle, IText, IImage, CPosition, ci.FK_CIID
FROM mc_contentitem_ig ciig
JOIN mc_contentitem ci ON CIID = ciig.FK_CIID
ORDER BY ci.FK_CIID, CPosition;

/* ContentItemIG-Images aus mc_contenttype entfernen */
DELETE FROM mc_contentitem
WHERE FK_CTID = 5;

/* ContentItemBG in mc_contenttype aktivieren, wenn ContentItemIG-Levels aktiv sind */
UPDATE mc_contenttype ct1
JOIN mc_contenttype ct2 ON ct2.CTID = 55
SET ct1.CTActive = IF(ct2.CTActive = 1, 1, ct1.CTActive)
WHERE ct1.CTID = 11;

/* ContentItemIG-Levels und ContentItemIG-Images aus mc_contenttype entfernen */
DELETE FROM mc_contenttype
WHERE CTID IN (5, 55);

/* Tabelle mc_contentitem_ig entfernen */
DROP TABLE mc_contentitem_ig;

/*************************************************/
/* ContentItemTA in ContentItemArchive umwandeln */
/*************************************************/

/* neuen ContentType für ContentItemArchive in mc_contenttype anlegen */
INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition)
SELECT 76, 'ContentItemArchive', CTActive, 3
FROM mc_contenttype
WHERE CTID = 51;

/* Typ aller bestehenden ContentItemTA-Levels auf ContentItemArchive ändern */
UPDATE mc_contentitem
SET CType = 90,
    FK_CTID = 76
WHERE FK_CTID = 51;

/* Page-Type aller bestehenden Archivseiten von ARCHIVE auf NORMAL ändern */
UPDATE mc_contentitem
SET CType = 1
WHERE CType = 4
AND FK_CTID = 1;

/* ContentItemTA-Level aus mc_contenttype entfernen */
DELETE FROM mc_contenttype
WHERE CTID = 51;

/*************************************************/
/* ContentItemBI in ContentItemArchive umwandeln */
/*************************************************/

/* Typ aller bestehenden ContentItemBI-Levels auf ContentItemArchive ändern */
UPDATE mc_contentitem
SET CType = 90,
    FK_CTID = 76
WHERE FK_CTID = 57;

/* Page-Type aller bestehenden ContentItemBI-Pages von ARCHIVE auf NORMAL ändern */
UPDATE mc_contentitem
SET CType = 1
WHERE CType = 4
AND FK_CTID = 7;

/* ContentItemBI-Level aus mc_contenttype entfernen */
DELETE FROM mc_contenttype
WHERE CTID = 57;

/*****************************************/
/* InternalLink-Spalten korrekt benennen */
/*****************************************/

/* mc_contentitem_cb_box */
ALTER TABLE mc_contentitem_cb_box
ADD CBBLink int(11) NULL AFTER CBBImageTitles;
UPDATE mc_contentitem_cb_box
SET CBBLink = FK_CIID_Link;
ALTER TABLE mc_contentitem_cb_box
DROP FK_CIID_Link;

/* mc_contentitem_cb_box_biglink */
ALTER TABLE mc_contentitem_cb_box_biglink
ADD BLLink int(11) NULL AFTER BLImageTitles;
UPDATE mc_contentitem_cb_box_biglink
SET BLLink = FK_CIID_Link;
ALTER TABLE mc_contentitem_cb_box_biglink
DROP FK_CIID_Link;

/* mc_contentitem_cb_box_smalllink */
ALTER TABLE mc_contentitem_cb_box_smalllink
ADD SLLink int(11) NULL AFTER SLTitle;
UPDATE mc_contentitem_cb_box_smalllink
SET SLLink = FK_CIID_Link;
ALTER TABLE mc_contentitem_cb_box_smalllink
DROP FK_CIID_Link,
CHANGE SLLink SLLink int(11) NOT NULL;

/* mc_contentitem_ts_block_link */
ALTER TABLE mc_contentitem_ts_block_link
ADD TLLink int(11) NULL AFTER TLTitle;
UPDATE mc_contentitem_ts_block_link
SET TLLink = FK_CIID;
ALTER TABLE mc_contentitem_ts_block_link
DROP FK_CIID,
CHANGE TLLink TLLink int(11) NOT NULL;

/***************************************************/
/* Links/E-Mail-Adressen im Fließtext überarbeiten */
/***************************************************/

/* BE-Modul EdwinLinksTinyMCE hinzufügen */
INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition)
VALUES (18, 'edwinlinkstinymce', 'ModuleEdwinLinksTinyMCE', 1, 0);

/* BE-Modul EdwinLinksTinyMCE für jeden User erlauben */
UPDATE mc_user_rights
SET UModules = CONCAT(UModules, ',edwinlinkstinymce')
WHERE UModules IS NOT NULL
AND UModules != '';

/*************************************************************************************************/
/* Inhalte nur für einen bestimmten Zeitraum bzw. ab/bis zu einem bestimmten Zeitpunkt anzeigen  */
/*************************************************************************************************/
/* mc_contentitem */
ALTER TABLE mc_contentitem
MODIFY CShowFromDate DATETIME NULL,
MODIFY CShowUntilDate DATETIME NULL;

/**************************************************/
/* Image-Spalten in mc_contentitem_sp korrigieren */
/**************************************************/
ALTER TABLE mc_contentitem_sp
CHANGE PImage1 PImage1 VARCHAR(150) NOT NULL,
CHANGE PImage2 PImage2 VARCHAR(150) NOT NULL,
CHANGE PImage3 PImage3 VARCHAR(150) NOT NULL;

/************************************************/
/* FE Module für Ostliga & Jugendliga Tabelle   */
/************************************************/
INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`) VALUES(33, 'leaguemanagerchartsocceraustriaostliga', 'ModuleLeaguemanagerChartSoccerAustriaOstliga', 0);
INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`) VALUES(34, 'leaguemanagerchartsocceraustriajugendliga', 'ModuleLeaguemanagerChartSoccerAustriaJugendliga', 0); 

/***************************************************************/
/* ContentitemBG Anzahl der speziellen Texte auf drei erhöht   */
/***************************************************************/
ALTER TABLE mc_contentitem_bg_image 
ADD BIImageTitle varchar(150) DEFAULT NULL AFTER BIImage;

/****************************************************************************/
/* Neues Modul zum nachladen spezieller Daten von Bildern im ContentItemBG  */
/****************************************************************************/
INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition) 
VALUES (19, 'imagecustomdata', 'ModuleImageCustomData', 1, 0);

UPDATE mc_user_rights SET UModules = CONCAT(UModules, ',imagecustomdata') WHERE FK_UID IN (1,2,3,4,5,6);

/****************************************************************************/
/* Webseitennavigation oben neu                                             */
/****************************************************************************/
ALTER TABLE mc_user ADD UPreferredLanguage VARCHAR(20) NOT NULL DEFAULT 'german' AFTER ULanguage;
ALTER TABLE mc_site ADD SLanguage VARCHAR(20) NOT NULL DEFAULT 'german';

/****************************************************************************/
/* ModuleUserManagement - Beim Editieren Hilfsmodul per DB ausschließen     */
/****************************************************************************/

ALTER TABLE mc_moduletype_backend ADD MRequired BOOL NOT NULL DEFAULT 0;
UPDATE mc_moduletype_backend SET MRequired = 1 WHERE MID IN (3, 4, 18, 19);

/****************************************************************************/
/* ModuleRealEstate - Tabelle fehlt in Datenbankstruktur                    */
/****************************************************************************/

CREATE TABLE IF NOT EXISTS mc_module_realestate (
  IID INT(11) NOT NULL auto_increment,
  IObject VARCHAR(100) default NULL,
  IPrice DOUBLE default NULL,
  IPurchaseDate DATE default NULL,
  IPriceOfSale DOUBLE default NULL,
  IPrivateSale TINYINT(4) NOT NULL default '0',
  IAgent VARCHAR(100) default NULL,
  IDeleted TINYINT(4) NOT NULL default '0',
  FK_EID INT(11) NOT NULL default '0',
  PRIMARY KEY  (IID)
) ENGINE=MyISAM;


/****************************************************************************/
/* Aktivieren / Deaktivieren von Inhalten über mehrere Ebenen               */
/****************************************************************************/

ALTER TABLE mc_contentitem ADD CHasContent TINYINT(1) NOT NULL DEFAULT 0;
