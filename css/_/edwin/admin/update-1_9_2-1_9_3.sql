/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO] 
 * Das erste ALTER TABLE Statement entfernt die Spalten CDownloads,
 * CInternalLinks und CExternalLinks aus der Tabelle mc_contentitem. Manchmal
 * funktioniert das aber nicht, da die Spalten nicht vorhanden sind (in den
 * Versionen ab 1.9 waren diese Spalten nicht mehr im Install-Script enthalten).
 * In so einem Fall einfach das erste ALTER TABLE Statement weglassen.
 * [/INFO]
 */

/*******************************************************************************/
/* FK-System Umstellung bei Downloads, internen und externen Links abschließen */
/*******************************************************************************/

/* nicht mehr verwendete Spalten aus Tabelle mc_contentitem entfernen */
ALTER TABLE mc_contentitem
DROP CDownloads,
DROP CInternalLinks,
DROP CExternalLinks;

/***************************************************/
/* Identifier bei internen Links durch FK ersetzen */
/***************************************************/

/* Spalte FK_CIID_Link NULL hinzufügen */
ALTER TABLE mc_internallink
ADD FK_CIID_Link INT NULL AFTER FK_SID,
ADD KEY FK_CIID_Link (FK_CIID_Link);

/* Spalte FK_CIID_Link mit Referenzen auf ContentItems befüllen (lebende Links) */
UPDATE mc_internallink AS il
SET FK_CIID_Link = (
  SELECT CIID
  FROM mc_contentitem AS ci
  WHERE CIIdentifier = ILIdentifier
  AND ci.FK_SID = il.FK_SID
);

/* tote Links aus Tabelle mc_internallink entfernen */
DELETE FROM mc_internallink
WHERE FK_CIID_Link IS NULL;

/* Spalte FK_CIID_Link NOT NULL */
ALTER TABLE mc_internallink
CHANGE FK_CIID_Link FK_CIID_Link INT NOT NULL;

/* Spalten ILIdentifier und FK_SID entfernen */
ALTER TABLE mc_internallink
DROP ILIdentifier,
DROP FK_SID;

/****************************/
/* Sortierung interne Links */
/****************************/

/* Spalte ILPosition NULL hinzufügen */
ALTER TABLE mc_internallink
ADD ILPosition INT NULL AFTER ILTitle,
ADD UNIQUE KEY FK_CIID_ILPosition_UN (FK_CIID, ILPosition);

/* Hilfstabelle mc_internallink_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_internallink_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_ILID INT NOT NULL,
  FK_CIID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_internallink_positions befüllen */
INSERT INTO mc_internallink_positions (FK_ILID, FK_CIID)
SELECT ILID, FK_CIID
FROM mc_internallink
ORDER BY FK_CIID, ILID;
/* Hilfstabelle mc_internallink_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_internallink_positions_min
AS SELECT FK_CIID, MIN(ID) AS MIN_ID
FROM mc_internallink_positions
GROUP BY FK_CIID;
/* Spalte ILPosition befüllen */
UPDATE mc_internallink
JOIN mc_internallink_positions a ON ILID = FK_ILID
SET ILPosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_internallink_positions_min b
  WHERE b.FK_CIID = a.FK_CIID
);

/* Spalte ILPosition NOT NULL */
ALTER TABLE mc_internallink CHANGE ILPosition ILPosition INT NOT NULL;

/****************************/
/* Sortierung externe Links */
/****************************/

/* Spalte ELPosition NULL hinzufügen */
ALTER TABLE mc_externallink
ADD ELPosition INT NULL AFTER ELUrl,
ADD UNIQUE KEY FK_CIID_ELPosition_UN (FK_CIID, ELPosition);

/* Hilfstabelle mc_externallink_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_externallink_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_ELID INT NOT NULL,
  FK_CIID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_externallink_positions befüllen */
INSERT INTO mc_externallink_positions (FK_ELID, FK_CIID)
SELECT ELID, FK_CIID
FROM mc_externallink
ORDER BY FK_CIID, ELID;
/* Hilfstabelle mc_externallink_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_externallink_positions_min
AS SELECT FK_CIID, MIN(ID) AS MIN_ID
FROM mc_externallink_positions
GROUP BY FK_CIID;
/* Spalte ILPosition befüllen */
UPDATE mc_externallink
JOIN mc_externallink_positions a ON ELID = FK_ELID
SET ELPosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_externallink_positions_min b
  WHERE b.FK_CIID = a.FK_CIID
);

/* Spalte ELPosition NOT NULL */
ALTER TABLE mc_externallink CHANGE ELPosition ELPosition INT NOT NULL;

/************************/
/* Sortierung Downloads */
/************************/

/* Korrektur: Spalte FK_CIID darf nicht null sein */
DELETE FROM mc_file
WHERE FK_CIID IS NULL;
ALTER TABLE mc_file
CHANGE FK_CIID FK_CIID INT NOT NULL;

/* Tabelle mc_centralfile_relation und mc_file vereinen (analog zu mc_contentitem_dl_area_file) */
/* Tabelle mc_file erweitern */
ALTER TABLE mc_file
ADD FK_CFID INT NULL AFTER FModified,
CHANGE FFile FFile VARCHAR(150) NULL;
/* Verknüpfungen von Seiten zu zentralen downloads von mc_centralfile_relation nach mc_file kopieren */
INSERT INTO mc_file (FTitle, FCreated, FK_CFID, FK_CIID)
SELECT CRTitle, CRCreated, FK_CFID, FK_CIID
FROM mc_centralfile_relation;
/* nicht mehr verwendete Tabelle mc_centralfile_relation entfernen */
DROP TABLE mc_centralfile_relation;

/* Spalte FPosition NULL hinzufügen */
ALTER TABLE mc_file
ADD FPosition INT NULL AFTER FModified,
ADD UNIQUE KEY FK_CIID_FPosition_UN (FK_CIID, FPosition);

/* Hilfstabelle mc_file_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_file_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_FID INT NOT NULL,
  FK_CIID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_file_positions befüllen */
INSERT INTO mc_file_positions (FK_FID, FK_CIID)
SELECT FID, FK_CIID
FROM mc_file
ORDER BY FK_CIID, COALESCE(FModified, FCreated);
/* Hilfstabelle mc_file_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_file_positions_min
AS SELECT FK_CIID, MIN(ID) AS MIN_ID
FROM mc_file_positions
GROUP BY FK_CIID;
/* Spalte FPosition befüllen */
UPDATE mc_file
JOIN mc_file_positions a ON FID = FK_FID
SET FPosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_file_positions_min b
  WHERE b.FK_CIID = a.FK_CIID
);

/* Spalte FPosition NOT NULL */
ALTER TABLE mc_file CHANGE FPosition FPosition INT NOT NULL;

/********************************/
/* Properties bei ContentItemES */
/********************************/

/* Tabelle mc_contentitem_es erweitern */
ALTER TABLE mc_contentitem_es
ADD EProperties TEXT NULL DEFAULT NULL AFTER EImageTitles; 

/*******************************/
/* Sortierung Startseitenboxen */
/*******************************/

/* UNIQUE KEY zu mc_module_siteindex_compendium hinzufügen */
ALTER TABLE mc_module_siteindex_compendium
ADD UNIQUE KEY FK_SID_SBPosition_UN (FK_SID, SBPosition);

/***********************************/
/* Korrektur Tabelle ContentItemBO */
/***********************************/

/* Spalte BText6 korrigieren und Spalten BTitle7, BText7 und BImage7 hinzufügen */
ALTER TABLE `mc_contentitem_bo`
CHANGE BText6 BText6 TEXT NULL DEFAULT NULL,
ADD BTitle7 VARCHAR( 150 ) NULL DEFAULT NULL AFTER BTitle6,
ADD BText7 TEXT NULL DEFAULT NULL AFTER BText6,
ADD BImage7 VARCHAR( 150 ) NOT NULL AFTER BImage6;
