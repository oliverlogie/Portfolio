/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO] 
 * ContentItemTS wurde komplett überarbeitet, alte FE-Templates sind nicht 
 * mehr kompatibel mit Version 1.9.8 und müssen angepasst werden.
 *  
 * ModuleSiteindexCompendium wurde komplett überarbeitet, Config-Variablen 
 * beginnend mit "si_box_*" müssen auf "si_area_box_*" umbenannt werden, 
 * FE-Templates wurden ebenfalls neu strukturiert (siehe Q2E Wiki).
 * [/INFO]
 */

/***********************************/
/* Erweiterung SiteindexCompendium */
/***********************************/

/* Tabelle für Startseitenbereiche hinzufügen */
CREATE TABLE mc_module_siteindex_compendium_area (
  SAID int(11) NOT NULL AUTO_INCREMENT,
  SATitle varchar(100) NOT NULL,
  SAText text NOT NULL,
  SAImage varchar(100) NOT NULL,
  SABoxType ENUM('large', 'medium', 'small') NOT NULL DEFAULT 'large',
  SAPosition tinyint(4) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (SAID),
  UNIQUE KEY FK_SID_SAPosition_UN (FK_SID, SAPosition)
) ENGINE=MyISAM;

/* Für jede Site einen Startseitenbereich anlegen */
INSERT INTO mc_module_siteindex_compendium_area (SABoxType, SAPosition, FK_SID)
SELECT 1, 1, SID
FROM mc_site
ORDER BY SID;

/* Tabelle für Startseitenboxen umbenennen */
RENAME TABLE mc_module_siteindex_compendium_box
TO mc_module_siteindex_compendium_area_box;

/* Parent der Startseitenboxen von Sites auf Startseitenbereiche ändern */
ALTER TABLE mc_module_siteindex_compendium_area_box
ADD FK_SAID int(11) NULL AFTER FK_SID,
ADD INDEX FK_SAID (FK_SAID);

UPDATE mc_module_siteindex_compendium_area_box ab
SET FK_SAID = (
  SELECT SAID
  FROM mc_module_siteindex_compendium_area a
  WHERE a.FK_SID = ab.FK_SID
  AND SAPosition = 1
);

ALTER TABLE mc_module_siteindex_compendium_area_box
DROP INDEX FK_SID_SBPosition_UN,
DROP FK_SID,
CHANGE FK_SAID FK_SAID int(11) NOT NULL,
ADD UNIQUE INDEX FK_SAID_SBPosition_UN (FK_SAID, SBPosition);

/* Tabelle mc_module_siteindex_compendium_area_box um SBPositionLocked erweitern */
ALTER TABLE mc_module_siteindex_compendium_area_box
ADD SBPositionLocked TINYINT(1) NOT NULL DEFAULT 0 AFTER SBPosition;

/******************************/
/* TS-Linkbereiche hinzufügen */
/******************************/

/* Tabelle für Linkbereiche hinzufügen */
CREATE TABLE mc_contentitem_ts_block (
  TBID int(11) NOT NULL AUTO_INCREMENT,
  TBTitle varchar(255) NOT NULL,
  TBText text,
  TBImage varchar(150) NOT NULL,
  TBImageTitles text,
  TBPosition tinyint(4) NOT NULL,
  FK_CIID int(11) NOT NULL,
  PRIMARY KEY (TBID),
  UNIQUE KEY FK_CIID_TBPosition_UN (FK_CIID, TBPosition)
) ENGINE=MyISAM;

/* die bisher 2 fixen Linkbereiche migrieren */
INSERT INTO mc_contentitem_ts_block (TBTitle, TBPosition, FK_CIID)
SELECT TLTitle1, 1, FK_CIID
FROM mc_contentitem_ts;
INSERT INTO mc_contentitem_ts_block (TBTitle, TBPosition, FK_CIID)
SELECT TLTitle2, 2, FK_CIID
FROM mc_contentitem_ts;

/* Parent der Links von Pages auf Linkbereiche ändern */
ALTER TABLE mc_contentitem_ts_block_link
ADD FK_TBID int(11) NULL AFTER FK_SID,
ADD INDEX FK_TBID (FK_TBID);

UPDATE mc_contentitem_ts_block_link bl
SET FK_TBID = (
  SELECT TBID
  FROM mc_contentitem_ts_block b
  WHERE bl.FK_CIID_TS1 = b.FK_CIID
  AND TBPosition = 1
)
WHERE FK_CIID_TS1 IS NOT NULL;

UPDATE mc_contentitem_ts_block_link bl
SET FK_TBID = (
  SELECT TBID
  FROM mc_contentitem_ts_block b
  WHERE bl.FK_CIID_TS2 = b.FK_CIID
  AND TBPosition = 2
)
WHERE FK_CIID_TS2 IS NOT NULL;

ALTER TABLE mc_contentitem_ts_block_link
DROP FK_CIID_TS1,
DROP FK_CIID_TS2;

/* TS-Spalten entfernen/hinzufügen */
ALTER TABLE mc_contentitem_ts
DROP TLTitle1,
DROP TLTitle2,
CHANGE TTitle TTitle1 varchar(150) NOT NULL,
ADD TTitle2 varchar(150) NOT NULL AFTER TTitle1,
ADD TTitle3 varchar(150) NOT NULL AFTER TTitle2,
ADD TImage3 varchar(150) NOT NULL AFTER TImage2,
ADD TText3 text AFTER TText2;

/*********************************************/
/* Identifier bei TS-Links durch FK ersetzen */
/*********************************************/

/* Spalte FK_CIID NULL hinzufügen */
ALTER TABLE mc_contentitem_ts_block_link
ADD FK_CIID INT NULL AFTER FK_SID,
ADD KEY FK_CIID (FK_CIID);

/* Spalte FK_CIID mit Referenzen auf ContentItems befüllen (lebende Links) */
UPDATE mc_contentitem_ts_block_link AS tl
SET FK_CIID = (
  SELECT CIID
  FROM mc_contentitem AS ci
  WHERE CIIdentifier = TLIdentifier
  AND ci.FK_SID = tl.FK_SID
);

/* tote Links aus Tabelle mc_internallink entfernen */
DELETE FROM mc_contentitem_ts_block_link
WHERE FK_CIID IS NULL;

/* Spalte FK_CIID NOT NULL */
ALTER TABLE mc_contentitem_ts_block_link
CHANGE FK_CIID FK_CIID INT NOT NULL;

/* Spalten ILIdentifier und FK_SID entfernen */
ALTER TABLE mc_contentitem_ts_block_link
DROP TLIdentifier,
DROP FK_SID;

/***********************/
/* Sortierung TS-Links */
/***********************/

/* Spalte TLPosition NULL hinzufügen */
ALTER TABLE mc_contentitem_ts_block_link
ADD TLPosition INT NULL AFTER TLTitle,
ADD UNIQUE KEY FK_TBID_TLPosition_UN (FK_TBID, TLPosition);

/* Hilfstabelle mc_contentitem_ts_block_link_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_contentitem_ts_block_link_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_TLID INT NOT NULL,
  FK_TBID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_contentitem_ts_block_link_positions befüllen */
INSERT INTO mc_contentitem_ts_block_link_positions (FK_TLID, FK_TBID)
SELECT TLID, FK_TBID
FROM mc_contentitem_ts_block_link
ORDER BY FK_TBID, TLID;
/* Hilfstabelle mc_contentitem_ts_block_link_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_contentitem_ts_block_link_positions_min
AS SELECT FK_TBID, MIN(ID) AS MIN_ID
FROM mc_contentitem_ts_block_link_positions
GROUP BY FK_TBID;
/* Spalte TLPosition befüllen */
UPDATE mc_contentitem_ts_block_link
JOIN mc_contentitem_ts_block_link_positions a ON TLID = FK_TLID
SET TLPosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_contentitem_ts_block_link_positions_min b
  WHERE b.FK_TBID = a.FK_TBID
);

/* Spalte TLPosition NOT NULL */
ALTER TABLE mc_contentitem_ts_block_link
CHANGE TLPosition TLPosition INT NOT NULL;

/*****************************************/
/* FK-System bei ModuleSideBox umstellen */
/*****************************************/

/* Tabelle mc_module_sidebox_assignment erstellen */
CREATE TABLE mc_module_sidebox_assignment (
  FK_BID INT NOT NULL ,
  FK_CIID INT NOT NULL ,
  PRIMARY KEY (FK_BID , FK_CIID)
) ENGINE = MYISAM;

/* korrekte FKs befüllen */
INSERT INTO mc_module_sidebox_assignment (FK_BID, FK_CIID)
SELECT BID, (SELECT CIID FROM mc_contentitem WHERE FK_SID = mc_module_sidebox.FK_SID AND CIIdentifier = '')
FROM mc_module_sidebox
WHERE BShowOnPages REGEXP '(^|,)999999(,|$)';
INSERT INTO mc_module_sidebox_assignment (FK_BID, FK_CIID)
SELECT BID, CIID
FROM mc_module_sidebox
JOIN mc_contentitem ON BShowOnPages REGEXP CONCAT("(^|,)", CIID, "(,|$)");

/* falsche FKs entfernen */
ALTER TABLE mc_module_sidebox
DROP BShowOnPages;

/***********************************/
/* Sortierung ModuleSurvey Answers */
/***********************************/

/* Spalte APosition NULL erlauben */
ALTER TABLE mc_module_survey_answer
CHANGE APosition APosition INT(11) NULL;

/* Position von gelöschten Fragen auf NULL setzen */
UPDATE mc_module_survey_answer
SET APosition = NULL
WHERE ADeleted != 0;

/* Hilfstabelle mc_module_survey_answer_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_module_survey_answer_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_AID INT NOT NULL,
  FK_QID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_module_survey_answer_positions befüllen */
INSERT INTO mc_module_survey_answer_positions (FK_AID, FK_QID)
SELECT AID, FK_QID
FROM mc_module_survey_answer
WHERE ADeleted = 0
ORDER BY FK_QID, APosition, AID;
/* Hilfstabelle mc_module_survey_answer_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_module_survey_answer_positions_min
AS SELECT FK_QID, MIN(ID) AS MIN_ID
FROM mc_module_survey_answer_positions
GROUP BY FK_QID;
/* Spalte APosition befüllen */
UPDATE mc_module_survey_answer
JOIN mc_module_survey_answer_positions a ON AID = FK_AID
SET APosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_module_survey_answer_positions_min b
  WHERE b.FK_QID = a.FK_QID
);

/* UNIQUE KEY für Position hinzufügen */
ALTER TABLE mc_module_survey_answer
ADD UNIQUE KEY FK_QID_APosition_UN (FK_QID, APosition);

/*************************************/
/* Sortierung ModuleSurvey Questions */
/*************************************/

/* Spalte QPosition NULL erlauben */
ALTER TABLE mc_module_survey_question
CHANGE QPosition QPosition INT(11) NULL;

/* Position von gelöschten Fragen auf NULL setzen */
UPDATE mc_module_survey_question
SET QPosition = NULL
WHERE QDeleted != 0;

/* Hilfstabelle mc_module_survey_question_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_module_survey_question_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_QID INT NOT NULL,
  FK_SID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_module_survey_question_positions befüllen */
INSERT INTO mc_module_survey_question_positions (FK_QID, FK_SID)
SELECT QID, FK_SID
FROM mc_module_survey_question
WHERE QDeleted = 0
ORDER BY FK_SID, QPosition, QID;
/* Hilfstabelle mc_module_survey_question_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_module_survey_question_positions_min
AS SELECT FK_SID, MIN(ID) AS MIN_ID
FROM mc_module_survey_question_positions
GROUP BY FK_SID;
/* Spalte QPosition befüllen */
UPDATE mc_module_survey_question
JOIN mc_module_survey_question_positions a ON QID = FK_QID
SET QPosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_module_survey_question_positions_min b
  WHERE b.FK_SID = a.FK_SID
);

/* UNIQUE KEY für Position hinzufügen */
ALTER TABLE mc_module_survey_question
ADD UNIQUE KEY FK_SID_QPosition_UN (FK_SID, QPosition);
