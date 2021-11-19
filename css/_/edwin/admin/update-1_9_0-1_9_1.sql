/***********************/
/* Neues ContentItemVC */
/***********************/

/* Tabelle mc_contentitem_vc erstellen */
DROP TABLE IF EXISTS mc_contentitem_vc;
CREATE TABLE mc_contentitem_vc (
  VID int(11) NOT NULL AUTO_INCREMENT,
  VTitle1 varchar(150) NOT NULL,
  VTitle2 varchar(150) NOT NULL,
  VTitle3 varchar(150) NOT NULL,
  VText1 text,
  VText2 text,
  VText3 text,
  VImage1 varchar(150) NOT NULL,
  VImage2 varchar(150) NOT NULL,
  VImage3 varchar(150) NOT NULL,
  VImage4 varchar(150) NOT NULL,
  VImageTitles text,
  VVideoType1 varchar(50) NOT NULL,
  VVideo1 varchar(200) NOT NULL,
  VVideoType2 varchar(50) NOT NULL,
  VVideo2 varchar(200) NOT NULL,
  VVideoType3 varchar(50) NOT NULL,
  VVideo3 varchar(200) NOT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (VID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

/* ContentType anlegen */
INSERT INTO mc_contenttype (CTID, CTTitle, CTClass, CTSelectable, CTPosition)
VALUES (27, 'Inhalt-Videodarstellung', 'ContentItemVC', 0, 31);

/***********************/
/* Neues ContentItemCB */
/***********************/

/* Tabelle mc_contentitem_cb erstellen */
DROP TABLE IF EXISTS mc_contentitem_cb;
CREATE TABLE mc_contentitem_cb (
  CBID int(11) NOT NULL AUTO_INCREMENT,
  CBTitle varchar(255) NOT NULL,
  CBText1 text,
  CBText2 text,
  CBText3 text,
  CBImage varchar(150) NOT NULL,
  CBImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CBID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

/* Tabelle mc_contentitem_cb_box erstellen */
DROP TABLE IF EXISTS mc_contentitem_cb_box;
CREATE TABLE mc_contentitem_cb_box (
  CBBID int(11) NOT NULL AUTO_INCREMENT,
  CBBTitle varchar(255) NOT NULL,
  CBBText text,
  CBBImage varchar(150) NOT NULL,
  CBBImageTitles text,
  CBBPosition int(11) NOT NULL,
  FK_CIID_Link int(11) DEFAULT NULL,
  FK_CIID int(11) NOT NULL,
  PRIMARY KEY (CBBID),
  UNIQUE KEY FK_CIID_CBBPosition_UN (FK_CIID,CBBPosition),
  KEY FK_CIID_Link (FK_CIID_Link),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

/* Tabelle mc_contentitem_cb_box_biglink erstellen */
DROP TABLE IF EXISTS mc_contentitem_cb_box_biglink;
CREATE TABLE mc_contentitem_cb_box_biglink (
  BLID int(11) NOT NULL AUTO_INCREMENT,
  BLTitle varchar(255) NOT NULL,
  BLText text,
  BLImage varchar(150) NOT NULL,
  BLImageTitles text,
  BLPosition int(11) NOT NULL,
  FK_CIID_Link int(11) DEFAULT NULL,
  FK_CBBID int(11) NOT NULL,
  PRIMARY KEY (BLID),
  UNIQUE KEY FK_CBBID_BLPosition_UN (FK_CBBID,BLPosition),
  KEY FK_CIID_Link (FK_CIID_Link),
  KEY FK_CBBID (FK_CBBID)
) ENGINE=MyISAM;

/* Tabelle mc_contentitem_cb_box_smalllink erstellen */
DROP TABLE IF EXISTS mc_contentitem_cb_box_smalllink;
CREATE TABLE mc_contentitem_cb_box_smalllink (
  SLID int(11) NOT NULL AUTO_INCREMENT,
  SLTitle varchar(255) NOT NULL,
  SLPosition int(11) NOT NULL,
  FK_CIID_Link int(11) DEFAULT NULL,
  FK_CBBID int(11) NOT NULL,
  PRIMARY KEY (SLID),
  UNIQUE KEY FK_CBBID_SLPosition_UN (FK_CBBID,SLPosition),
  KEY FK_CIID (FK_CBBID),
  KEY FK_CIID_Link (FK_CIID_Link)
) ENGINE=MyISAM;

/* ContentType anlegen */
INSERT INTO mc_contenttype (CTID, CTTitle, CTClass, CTSelectable, CTPosition)
VALUES (28, 'Inhalt-Compendiumseite', 'ContentItemCB', 0, 32);

/********************************/
/* Sortierung bei ContentItemQS */
/********************************/

/* Spalte QSPosition NULL hinzufügen */
ALTER TABLE mc_contentitem_qs_statement ADD QSPosition INT NULL AFTER QSImageTitles, ADD UNIQUE KEY FK_CIID_QSPosition_UN (FK_CIID,QSPosition);

/* Position ermitteln und zuweisen */
CREATE TEMPORARY TABLE mc_contentitem_qs_statement_positions(ID INT NOT NULL AUTO_INCREMENT, FK_QSID INT NOT NULL, FK_CIID INT NOT NULL, PRIMARY KEY (ID));
INSERT INTO mc_contentitem_qs_statement_positions(FK_QSID, FK_CIID) SELECT QSID, FK_CIID FROM mc_contentitem_qs_statement ORDER BY FK_CIID, QSID;
CREATE TEMPORARY TABLE mc_contentitem_qs_statement_positions_min AS SELECT FK_CIID, MIN(ID) AS MIN_ID FROM mc_contentitem_qs_statement_positions GROUP BY FK_CIID; /* Workaround für MySQL Problem, dass eine temporäre Tabelle nur einmal in einer Abfrage verwendet werden darf */
UPDATE mc_contentitem_qs_statement JOIN mc_contentitem_qs_statement_positions a ON QSID = FK_QSID SET QSPosition = ID - (SELECT MIN_ID FROM mc_contentitem_qs_statement_positions_min b WHERE b.FK_CIID = a.FK_CIID) + 1;

/* Spalte QSPosition NOT NULL */
ALTER TABLE mc_contentitem_qs_statement CHANGE QSPosition QSPosition INT NOT NULL;
