/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Die Tabelle mc_site sollte nach der Ausführung des Update-Scripts unbedingt
 * überprüft werden, da das Update-Script aufgrund der Ausgangslage keine 
 * korrekten Daten garantieren kann.
 * [/INFO]
 */

/*************************************************/
/* Portalnavigation und Sprachnavigation trennen */
/*************************************************/

/* Spalten SPosition und FK_SID auf SPositionLanguage und FK_SID_Language umbenennen */
ALTER TABLE mc_site
CHANGE SPosition SPositionLanguage INT NULL,
CHANGE FK_SID FK_SID_Language INT NULL;

/* Hilfstabelle mc_site_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_site_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  FK_SID INT NOT NULL,
  FK_SID_Language INT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_site_positions befüllen */
INSERT INTO mc_site_positions (FK_SID, FK_SID_Language)
SELECT SID, FK_SID_Language
FROM mc_site
ORDER BY FK_SID_Language, SPositionLanguage, SID;
/* Hilfstabelle mc_site_positions_min anlegen und befüllen (temporäre Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_site_positions_min
AS SELECT FK_SID_Language, MIN(ID) AS MIN_ID
FROM mc_site_positions
GROUP BY FK_SID_Language;
/* Spalte SPositionLanguage befüllen bzw. aktualisieren */
UPDATE mc_site
JOIN mc_site_positions a ON SID = FK_SID
SET SPositionLanguage = 1 + ID - (
  SELECT MIN_ID
  FROM mc_site_positions_min b
  WHERE COALESCE(b.FK_SID_Language, 0) = COALESCE(a.FK_SID_Language, 0)
);

/* UNIQUE KEY hinzufügen, alten INDEX FK_SID entfernen */
ALTER TABLE mc_site
ADD UNIQUE KEY FK_SID_Language_SPositionLanguage_UN (FK_SID_Language, SPositionLanguage),
DROP INDEX `FK_SID`;

/* Spalten SPositionPortal und FK_SID_Portal hinzufügen */
ALTER TABLE mc_site
ADD SPositionPortal INT NULL AFTER FK_SID_Language,
ADD FK_SID_Portal INT NULL AFTER SPositionPortal,
ADD UNIQUE KEY FK_SID_Portal_SPositionPortal_UN (FK_SID_Portal, SPositionPortal);

/* SPositionPortal bei Top-Level Sites auf SPositionLanguage setzen */
UPDATE mc_site
SET SPositionPortal = SPositionLanguage
WHERE FK_SID_Language IS NULL;
