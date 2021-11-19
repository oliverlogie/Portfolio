/******************************/
/* Neues Modul DownloadTicker */
/******************************/

/* Tabelle mc_module_downloadticker erstellen */
CREATE TABLE IF NOT EXISTS mc_module_downloadticker (
  DTID int(11) NOT NULL AUTO_INCREMENT,
  DTDownloadTitle varchar(150) NULL,
  DTLinkTitle varchar(150) NULL,
  DTPosition int(11) NOT NULL,
  FK_FID int(11) NULL,
  FK_DFID int(11) NULL,
  FK_CFID int(11) NULL,
  FK_CIID int(11) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (DTID),
  UNIQUE KEY FK_SID_DTPosition_UN (FK_SID, DTPosition),
  KEY FK_FID (FK_FID),
  KEY FK_DFID (FK_DFID),
  KEY FK_CFID (FK_CFID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

/*************************************************************/
/* Zentrale und dezentrale Files um Änderungsdatum erweitern */
/*************************************************************/

/* Tabelle mc_centralfile erweitern */
ALTER TABLE mc_centralfile ADD CFModified DATETIME NULL AFTER CFCreated;
/* Tabelle mc_file erweitern */
ALTER TABLE mc_file ADD FModified DATETIME NULL AFTER FCreated;

/********************************/
/* Umstellung von ContentItemDL */
/********************************/

/* Downloadbereiche um Position erweitern */
/* Spalte DAPosition NULL hinzufügen */
ALTER TABLE mc_contentitem_dl_area ADD DAPosition INT NULL AFTER DAText, ADD UNIQUE KEY FK_CIID_DAPosition_UN (FK_CIID, DAPosition);
/* Position ermitteln und zuweisen */
CREATE TEMPORARY TABLE mc_contentitem_dl_area_positions(ID INT NOT NULL AUTO_INCREMENT, FK_DAID INT NOT NULL, FK_CIID INT NOT NULL, PRIMARY KEY (ID));
INSERT INTO mc_contentitem_dl_area_positions(FK_DAID, FK_CIID) SELECT DAID, FK_CIID FROM mc_contentitem_dl_area ORDER BY FK_CIID, DAID;
CREATE TEMPORARY TABLE mc_contentitem_dl_area_positions_min AS SELECT FK_CIID, MIN(ID) AS MIN_ID FROM mc_contentitem_dl_area_positions GROUP BY FK_CIID; /* Workaround für MySQL Problem, dass eine temporäre Tabelle nur einmal in einer Abfrage verwendet werden darf */
UPDATE mc_contentitem_dl_area JOIN mc_contentitem_dl_area_positions a ON DAID = FK_DAID SET DAPosition = ID - (SELECT MIN_ID FROM mc_contentitem_dl_area_positions_min b WHERE b.FK_CIID = a.FK_CIID) + 1;
/* Spalte DAPosition NOT NULL */
ALTER TABLE mc_contentitem_dl_area CHANGE DAPosition DAPosition INT NOT NULL;

/* Spalte DAImageTitles hinzufügen */
ALTER TABLE mc_contentitem_dl_area ADD DAImageTitles TEXT NULL AFTER DAImage;

/* Tabelle mc_contentitem_dl_area_file erstellen */
CREATE TABLE IF NOT EXISTS mc_contentitem_dl_area_file (
  DFID int(11) NOT NULL AUTO_INCREMENT,
  DFTitle varchar(150) NOT NULL,
  DFFile varchar(150) NULL,
  DFCreated datetime NOT NULL,
  DFModified datetime NULL,
  DFPosition int(11) NULL,
  FK_CFID int(11) NULL,
  FK_DAID int(11) NOT NULL,
  PRIMARY KEY (DFID),
  UNIQUE KEY FK_DAID_DFPosition_UN (FK_DAID, DFPosition),
  KEY FK_CFID (FK_CFID),
  KEY FK_DAID (FK_DAID)
) ENGINE=MyISAM;
/* ContentItemDL Files von mc_file nach mc_contentitem_dl_area_file verschieben */
INSERT INTO mc_contentitem_dl_area_file(DFTitle, DFFile, DFCreated, DFModified, FK_DAID)
SELECT FTitle, FFile, FCreated, FModified, FK_DAID FROM mc_file WHERE FK_DAID IS NOT NULL;
DELETE FROM mc_file WHERE FK_DAID IS NOT NULL;
/* Position ermitteln und zuweisen */
CREATE TEMPORARY TABLE mc_contentitem_dl_area_file_positions(ID INT NOT NULL AUTO_INCREMENT, FK_DFID INT NOT NULL, FK_DAID INT NOT NULL, PRIMARY KEY (ID));
INSERT INTO mc_contentitem_dl_area_file_positions(FK_DFID, FK_DAID) SELECT DFID, FK_DAID FROM mc_contentitem_dl_area_file ORDER BY FK_DAID, DFID;
CREATE TEMPORARY TABLE mc_contentitem_dl_area_file_positions_min AS SELECT FK_DAID, MIN(ID) AS MIN_ID FROM mc_contentitem_dl_area_file_positions GROUP BY FK_DAID; /* Workaround für MySQL Problem, dass eine temporäre Tabelle nur einmal in einer Abfrage verwendet werden darf */
UPDATE mc_contentitem_dl_area_file JOIN mc_contentitem_dl_area_file_positions a ON DFID = FK_DFID SET DFPosition = ID - (SELECT MIN_ID FROM mc_contentitem_dl_area_file_positions_min b WHERE b.FK_DAID = a.FK_DAID) + 1;
/* Spalte DFPosition NOT NULL */
ALTER TABLE mc_contentitem_dl_area_file CHANGE DFPosition DFPosition INT NOT NULL;

/* Tabelle mc_file korrigieren */
ALTER TABLE mc_file DROP FK_DAID;

/***************************************/
/* Export-Logging bei ModuleNewsletter */
/***************************************/
CREATE TABLE IF NOT EXISTS mc_module_newsletter_export (
 EID int(11) NOT NULL auto_increment,
 EDateTime datetime NOT NULL default '0000-00-00 00:00',
 EName varchar(50) NOT NULL DEFAULT 'NULL',
 FK_UID int(11) NOT NULL,
 KEY EID (EID)
) ENGINE=MyISAM;

/************************************************/
/* Ausgliedern der TS-Links in seperate Tabelle */
/************************************************/

/* Tabelle mc_contentitem_ts_block_link erstellen */
CREATE TABLE IF NOT EXISTS mc_contentitem_ts_block_link (
  TLID int(11) NOT NULL AUTO_INCREMENT,
  TLIdentifier varchar(150) NOT NULL,
  TLTitle varchar(150) NOT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  FK_CIID_TS1 int(11) DEFAULT NULL,
  FK_CIID_TS2 int(11) DEFAULT NULL,
  PRIMARY KEY (TLID),
  KEY FK_CIID_TS1 (FK_CIID_TS1),
  KEY FK_CIID_TS2 (FK_CIID_TS2)
) ENGINE=MyISAM ;

/* TS-Links von mc_internallink nach mc_contentitem_ts_block_link kopieren */
INSERT INTO mc_contentitem_ts_block_link (TLIdentifier, TLTitle, FK_SID, FK_CIID_TS1, FK_CIID_TS2)
SELECT ILIdentifier, ILTitle, FK_SID, FK_CIID_TS1, FK_CIID_TS2
FROM mc_internallink
WHERE FK_CIID_TS1 IS NOT NULL
OR FK_CIID_TS2 IS NOT NULL;

/* TS-Links aus mc_internallink löschen */
DELETE FROM mc_internallink
WHERE FK_CIID_TS1 IS NOT NULL
OR FK_CIID_TS2 IS NOT NULL;

/* Spalten für TS-Links aus mc_internallink entfernen */
ALTER TABLE mc_internallink
DROP FK_CIID_TS1,
DROP FK_CIID_TS2;
/* unnötigen Index aus mc_internallink entfernen  */
ALTER TABLE mc_internallink
DROP INDEX ILURL;
